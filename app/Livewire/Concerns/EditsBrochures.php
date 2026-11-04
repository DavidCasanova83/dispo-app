<?php

namespace App\Livewire\Concerns;

use App\Jobs\CompressBrochurePdf;
use App\Models\Image;
use App\Services\BrochureVersionService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Trait partagé pour le modal d'édition des brochures (admin).
 *
 * Utilisé par App\Livewire\Admin\ImageManager et App\Livewire\PublicBrochuresOtiVt.
 *
 * Versions linguistiques : sur une brochure française, la partie « Versions
 * linguistiques » ajoute, rattache, ouvre ou supprime ses versions EN et IT
 * (App\Services\BrochureVersionService). Ouverte sur une version, la fenêtre ne
 * montre pas le classement (catégorie, secteur, auteur, responsable, ordre),
 * repris de la brochure française.
 *
 * Le composant hôte doit :
 *  - Utiliser le trait Livewire\Features\SupportFileUploads\WithFileUploads
 *  - Exposer dans render() les variables: $categories, $subCategories, $authors, $sectors, $responsables, $usedDisplayOrders
 */
trait EditsBrochures
{
    // Whitelist des MIME types pour fichiers téléchargeables (PDF + images)
    private const EDITS_BROCHURES_ALLOWED_DOWNLOAD_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    // État du modal
    public bool $showEditModal = false;
    public ?Image $editingImage = null;

    // Champs du formulaire d'édition
    public string $editTitle = '';
    public string $editAltText = '';
    public string $editDescription = '';
    public string $editLinkUrl = '';
    public string $editLinkText = '';
    public string $editCalameoLinkUrl = '';
    public string $editCalameoLinkText = '';
    public ?int $editQuantityAvailable = null;
    public ?int $editMaxOrderQuantity = null;
    public bool $editPrintAvailable = false;
    public ?int $editEditionYear = null;
    public ?int $editDisplayOrder = null;
    public ?int $editCategoryId = null;
    public ?int $editSubCategoryId = null;
    public ?int $editAuthorId = null;
    public ?int $editSectorId = null;
    public ?int $editResponsableId = null;
    public $editPdfFile = null;
    public bool $removePdf = false;

    /** Autres langues contenues dans le document français (carte bilingue FR/EN). */
    public array $editExtraLanguages = [];

    // Ajout d'une version linguistique ('' = formulaire fermé, sinon 'en' ou 'it')
    public string $versionFormLanguage = '';
    public string $versionTitle = '';
    public ?int $versionEditionYear = null;
    public string $versionCalameoLinkUrl = '';
    public $versionContentFile = null;
    public $versionCoverFile = null;
    public ?int $versionAttachId = null;

    /**
     * Ouvrir le modal d'édition
     */
    public function openEditModal($imageId): void
    {
        $image = Image::find($imageId);
        if (!$image) {
            return;
        }

        // Vérifier l'autorisation (policy : Super-admin ou propriétaire)
        $this->authorize('update', $image);

        $this->editingImage = $image;

        // Charger les valeurs actuelles
        $this->editTitle = $image->title ?? '';
        $this->editAltText = $image->alt_text ?? '';
        $this->editDescription = $image->description ?? '';
        $this->editLinkUrl = $image->link_url ?? '';
        $this->editLinkText = $image->link_text ?? '';
        $this->editCalameoLinkUrl = $image->calameo_link_url ?? '';
        $this->editCalameoLinkText = $image->calameo_link_text ?? '';
        $this->editQuantityAvailable = $image->quantity_available;
        $this->editMaxOrderQuantity = $image->max_order_quantity;
        $this->editPrintAvailable = (bool) $image->print_available;
        $this->editEditionYear = $image->edition_year;
        $this->editDisplayOrder = $image->display_order;
        $this->editCategoryId = $image->category_id;
        $this->editSubCategoryId = $image->sub_category_id;
        $this->editAuthorId = $image->author_id;
        $this->editSectorId = $image->sector_id;
        $this->editResponsableId = $image->responsable_id;
        $this->editPdfFile = null;
        $this->removePdf = false;
        $this->editExtraLanguages = (array) ($image->extra_languages ?? []);
        $this->resetVersionForm();

        $this->showEditModal = true;
    }

    /**
     * Fermer le modal d'édition
     */
    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingImage = null;
        $this->reset([
            'editTitle', 'editAltText', 'editDescription',
            'editLinkUrl', 'editLinkText', 'editCalameoLinkUrl', 'editCalameoLinkText',
            'editQuantityAvailable', 'editMaxOrderQuantity', 'editPrintAvailable',
            'editEditionYear', 'editDisplayOrder',
            'editCategoryId', 'editSubCategoryId', 'editAuthorId', 'editSectorId', 'editResponsableId',
            'editPdfFile', 'removePdf', 'editExtraLanguages',
        ]);
        $this->resetVersionForm();
        $this->resetValidation();
    }

    /**
     * Réinitialiser la sous-catégorie quand la catégorie change
     */
    public function updatedEditCategoryId(): void
    {
        $this->editSubCategoryId = null;
    }

    /**
     * Vérifie si tous les champs obligatoires du modal d'édition sont renseignés.
     */
    public function getCanSaveEditProperty(): bool
    {
        if (!$this->editingImage) {
            return false;
        }

        return $this->missingEditFields === [];
    }

    /**
     * Liste des champs obligatoires manquants dans le modal d'édition.
     */
    public function getMissingEditFieldsProperty(): array
    {
        if (!$this->editingImage) {
            return [];
        }

        $missing = [];
        if (empty($this->editTitle)) {
            $missing[] = 'Titre';
        }
        if (empty($this->editEditionYear)) {
            $missing[] = "Année d'édition";
        }

        // Une version reprend l'auteur et le responsable de sa brochure.
        if ($this->editingImage->isVersion()) {
            return $missing;
        }

        if (empty($this->editAuthorId)) {
            $missing[] = 'Auteur';
        }
        if (empty($this->editResponsableId)) {
            $missing[] = 'Responsable';
        }

        return $missing;
    }

    /**
     * Mettre à jour la brochure.
     */
    public function updateImage(): void
    {
        if (!$this->editingImage) {
            return;
        }

        // Vérifier l'autorisation
        $this->authorize('update', $this->editingImage);

        $isVersion = $this->editingImage->isVersion();

        // Validation
        $this->validate([
            'editTitle' => 'required|string|max:255',
            'editAltText' => 'nullable|string|max:255',
            'editDescription' => 'nullable|string|max:1000',
            'editLinkUrl' => 'nullable|url|max:500',
            'editLinkText' => 'nullable|string|max:255',
            'editCalameoLinkUrl' => 'nullable|url|max:500',
            'editCalameoLinkText' => 'nullable|string|max:255',
            'editQuantityAvailable' => 'nullable|integer|min:0',
            'editMaxOrderQuantity' => 'nullable|integer|min:0',
            'editEditionYear' => 'required|integer|min:1900|max:2100',
            'editDisplayOrder' => 'nullable|integer|min:0',
            'editCategoryId' => 'nullable|exists:categories,id',
            'editSubCategoryId' => 'nullable|exists:sub_categories,id',
            'editAuthorId' => ($isVersion ? 'nullable' : 'required') . '|exists:authors,id',
            'editSectorId' => 'nullable|exists:sectors,id',
            'editResponsableId' => ($isVersion ? 'nullable' : 'required') . '|exists:users,id',
            'editPdfFile' => 'nullable|mimes:pdf,jpg,jpeg,png|max:51200',
            'editExtraLanguages' => 'array',
            'editExtraLanguages.*' => 'in:' . implode(',', Image::VERSION_LANGUAGES),
        ], [
            'editTitle.required' => 'Le titre est obligatoire.',
            'editEditionYear.required' => "L'année d'édition est obligatoire.",
            'editAuthorId.required' => "L'auteur est obligatoire.",
            'editResponsableId.required' => 'Le responsable est obligatoire.',
        ]);

        // Garde supplémentaire (cohérence avec le bouton désactivé côté UI)
        if (!$this->canSaveEdit) {
            session()->flash('error', 'Champs obligatoires manquants : ' . implode(', ', $this->missingEditFields));
            return;
        }

        // Gérer la mise à jour du PDF
        $pdfPath = $this->editingImage->pdf_path;

        // Supprimer le PDF si demandé
        if ($this->removePdf && $pdfPath) {
            if (Storage::disk('public')->exists($pdfPath)) {
                Storage::disk('public')->delete($pdfPath);
            }
            $pdfPath = null;
        }

        // Uploader un nouveau fichier (PDF ou image) si fourni
        if ($this->editPdfFile && !$this->removePdf) {
            if (!in_array($this->editPdfFile->getMimeType(), self::EDITS_BROCHURES_ALLOWED_DOWNLOAD_MIME_TYPES)) {
                session()->flash('error', 'Type de fichier non autorisé.');
                return;
            }

            $downloadExtension = strtolower($this->editPdfFile->getClientOriginalExtension());
            $existingExtension = $this->editingImage->pdf_path
                ? strtolower(pathinfo($this->editingImage->pdf_path, PATHINFO_EXTENSION))
                : null;

            if ($this->editingImage->pdf_path && $existingExtension === $downloadExtension) {
                $pdfFilename = basename($this->editingImage->pdf_path);
                Storage::disk('public')->putFileAs('pdfs', $this->editPdfFile, $pdfFilename);
                $pdfPath = $this->editingImage->pdf_path;
            } else {
                if ($this->editingImage->pdf_path && Storage::disk('public')->exists($this->editingImage->pdf_path)) {
                    Storage::disk('public')->delete($this->editingImage->pdf_path);
                }

                $imageSlug = Str::limit(
                    Str::slug($this->editingImage->title) ?: Str::slug(pathinfo($this->editingImage->name, PATHINFO_FILENAME)),
                    100
                );
                if (empty($imageSlug)) {
                    $imageSlug = 'document';
                }
                // Fichier d'une version : nom de la brochure suivi de la langue (guide-pratique-en.pdf)
                if ($isVersion && $this->editingImage->versionOf?->slug) {
                    $imageSlug = Str::limit($this->editingImage->versionOf->slug, 100, '') . '-' . $this->editingImage->language;
                }

                $basePdfFilename = $imageSlug . '.' . $downloadExtension;
                $pdfFilename = $basePdfFilename;
                $pdfCounter = 1;
                while (Storage::disk('public')->exists('pdfs/' . $pdfFilename)) {
                    $pdfFilename = $imageSlug . '-' . $pdfCounter . '.' . $downloadExtension;
                    $pdfCounter++;
                }
                $pdfPath = $this->editPdfFile->storeAs('pdfs', $pdfFilename, 'public');
            }
        }

        // Mettre à jour (le classement d'une version est repris de sa brochure)
        $classification = $isVersion ? [] : [
            'display_order' => $this->editDisplayOrder,
            'category_id' => $this->editCategoryId ?: null,
            'sub_category_id' => $this->editSubCategoryId ?: null,
            'author_id' => $this->editAuthorId ?: null,
            'sector_id' => $this->editSectorId ?: null,
            'responsable_id' => $this->editResponsableId ?: null,
            'extra_languages' => array_values(array_intersect(Image::VERSION_LANGUAGES, $this->editExtraLanguages)),
        ];

        $this->editingImage->update($classification + [
            'title' => $this->editTitle ?: null,
            'alt_text' => $this->editAltText ?: null,
            'description' => $this->editDescription ?: null,
            'link_url' => $this->editLinkUrl ?: null,
            'link_text' => $this->editLinkText ?: null,
            'calameo_link_url' => $this->editCalameoLinkUrl ?: null,
            'calameo_link_text' => $this->editCalameoLinkText ?: null,
            'quantity_available' => $this->editQuantityAvailable,
            'max_order_quantity' => $this->editMaxOrderQuantity,
            'print_available' => $this->editPrintAvailable,
            'edition_year' => $this->editEditionYear,
            'pdf_path' => $pdfPath,
        ]);

        // Dispatch PDF compression after HTTP response (Ghostscript) — runs in PHP-FPM
        // (www-panelverdon), donc a les droits d'écrire dans storage/app/public/pdfs/.
        // Uniquement pour les fichiers .pdf (le trait accepte aussi jpg/png).
        if ($this->editPdfFile
            && !$this->removePdf
            && $pdfPath
            && str_ends_with(strtolower($pdfPath), '.pdf')
            && config('brochures.pdf_compression.enabled')
        ) {
            CompressBrochurePdf::dispatchAfterResponse($this->editingImage->id, $pdfPath);
        }

        // Régénérer le fichier JSON
        Artisan::call('images:generate-json');

        // Une version enregistrée : retour à sa brochure française.
        if ($isVersion) {
            session()->flash('success', 'La version ' . Image::LANGUAGE_ADJECTIVES[$this->editingImage->language] . ' a été mise à jour.');
            $this->openEditModal($this->editingImage->version_of_id);

            return;
        }

        session()->flash('success', 'La brochure a été mise à jour avec succès.');
        $this->closeEditModal();
    }

    // =====================================================================
    // Versions linguistiques (EN, IT)
    // =====================================================================

    /**
     * Brochures qu'on peut rattacher comme version de la brochure ouverte.
     */
    public function getVersionCandidatesProperty()
    {
        if (!$this->editingImage || $this->editingImage->isVersion() || $this->versionFormLanguage === '') {
            return collect();
        }

        return app(BrochureVersionService::class)->candidates($this->editingImage);
    }

    /**
     * Ouvre le formulaire d'ajout de la version dans une langue.
     */
    public function openVersionForm(string $language): void
    {
        if (!$this->editingImage || !in_array($language, Image::VERSION_LANGUAGES, true)) {
            return;
        }

        $this->resetVersionForm();
        $this->versionFormLanguage = $language;
        $this->versionTitle = $this->editingImage->title ?? '';
        $this->versionEditionYear = $this->editingImage->edition_year;
    }

    public function cancelVersionForm(): void
    {
        $this->resetVersionForm();
    }

    /**
     * Ajoute la version (fichier ou lien Calaméo, couverture facultative).
     */
    public function addVersion(): void
    {
        $main = $this->versionMainBrochure();
        if (!$main) {
            return;
        }

        $this->validate([
            'versionTitle' => 'nullable|string|max:255',
            'versionEditionYear' => 'required|integer|min:1900|max:2100',
            'versionCalameoLinkUrl' => 'nullable|url|max:500',
            'versionContentFile' => 'nullable|required_without:versionCalameoLinkUrl|mimes:pdf,jpg,jpeg,png|max:51200',
            'versionCoverFile' => 'nullable|image|max:10240',
        ], [
            'versionEditionYear.required' => "L'année d'édition est obligatoire.",
            'versionContentFile.required_without' => 'Ajoutez le fichier de la version ou son lien Calaméo.',
            'versionContentFile.mimes' => 'Le fichier doit être un PDF, un JPG ou un PNG.',
            'versionCoverFile.image' => 'La couverture doit être une image.',
        ]);

        $language = $this->versionFormLanguage;

        try {
            app(BrochureVersionService::class)->create($main, $language, [
                'title' => $this->versionTitle,
                'edition_year' => $this->versionEditionYear,
                'calameo_link_url' => $this->versionCalameoLinkUrl,
            ], $this->versionContentFile, $this->versionCoverFile, Auth::id());
        } catch (InvalidArgumentException $e) {
            $this->addError('versionContentFile', $e->getMessage());

            return;
        }

        Artisan::call('images:generate-json');
        $this->resetVersionForm();
        $this->editingImage = $main->fresh();
        session()->flash('success', 'La version ' . Image::LANGUAGE_ADJECTIVES[$language] . ' a été ajoutée.');
    }

    /**
     * Rattache un document existant comme version dans la langue du formulaire.
     */
    public function attachVersion(): void
    {
        $main = $this->versionMainBrochure();
        if (!$main) {
            return;
        }

        $this->validate(
            ['versionAttachId' => 'required|integer|exists:images,id'],
            ['versionAttachId.required' => 'Choisissez le document à rattacher.']
        );

        $document = Image::find($this->versionAttachId);
        $this->authorize('update', $document);
        $language = $this->versionFormLanguage;

        try {
            app(BrochureVersionService::class)->attach($main, $document, $language);
        } catch (InvalidArgumentException $e) {
            $this->addError('versionAttachId', $e->getMessage());

            return;
        }

        Artisan::call('images:generate-json');
        $this->resetVersionForm();
        $this->editingImage = $main->fresh();
        session()->flash('success', '« ' . ($document->title ?? $document->name) . ' » est maintenant la version ' . Image::LANGUAGE_ADJECTIVES[$language] . '.');
    }

    /**
     * Supprime une version de la brochure ouverte (ses fichiers sont effacés).
     */
    public function removeVersion(int $versionId): void
    {
        if (!$this->editingImage || $this->editingImage->isVersion()) {
            return;
        }

        $this->authorize('update', $this->editingImage);

        $version = $this->editingImage->versions()->whereKey($versionId)->first();
        if (!$version) {
            return;
        }

        app(BrochureVersionService::class)->remove($version);
        Artisan::call('images:generate-json');

        $this->editingImage = $this->editingImage->fresh();
        session()->flash('success', 'La version ' . Image::LANGUAGE_ADJECTIVES[$version->language] . ' a été supprimée.');
    }

    /**
     * Depuis une version : revenir à sa brochure française.
     */
    public function openMainBrochureEdit(): void
    {
        if ($this->editingImage && $this->editingImage->isVersion()) {
            $this->openEditModal($this->editingImage->version_of_id);
        }
    }

    private function versionMainBrochure(): ?Image
    {
        if (!$this->editingImage || $this->editingImage->isVersion()
            || !in_array($this->versionFormLanguage, Image::VERSION_LANGUAGES, true)) {
            return null;
        }

        $this->authorize('update', $this->editingImage);

        return $this->editingImage;
    }

    private function resetVersionForm(): void
    {
        $this->reset([
            'versionFormLanguage', 'versionTitle', 'versionEditionYear',
            'versionCalameoLinkUrl', 'versionContentFile', 'versionCoverFile', 'versionAttachId',
        ]);
        $this->resetValidation([
            'versionTitle', 'versionEditionYear', 'versionCalameoLinkUrl',
            'versionContentFile', 'versionCoverFile', 'versionAttachId',
        ]);
    }
}
