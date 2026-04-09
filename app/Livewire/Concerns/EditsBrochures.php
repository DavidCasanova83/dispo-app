<?php

namespace App\Livewire\Concerns;

use App\Models\Image;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Trait partagé pour le modal d'édition des brochures (admin).
 *
 * Utilisé par App\Livewire\Admin\ImageManager et App\Livewire\PublicBrochuresOtiVt.
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
            'editPdfFile', 'removePdf',
        ]);
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

        if (empty($this->editTitle)) return false;
        if (empty($this->editEditionYear)) return false;
        if (empty($this->editAuthorId)) return false;
        if (empty($this->editResponsableId)) return false;

        return true;
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
            'editAuthorId' => 'required|exists:authors,id',
            'editSectorId' => 'nullable|exists:sectors,id',
            'editResponsableId' => 'required|exists:users,id',
            'editPdfFile' => 'nullable|mimes:pdf,jpg,jpeg,png|max:51200',
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

        // Mettre à jour
        $this->editingImage->update([
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
            'display_order' => $this->editDisplayOrder,
            'category_id' => $this->editCategoryId ?: null,
            'sub_category_id' => $this->editSubCategoryId ?: null,
            'author_id' => $this->editAuthorId ?: null,
            'sector_id' => $this->editSectorId ?: null,
            'responsable_id' => $this->editResponsableId ?: null,
            'pdf_path' => $pdfPath,
        ]);

        // Régénérer le fichier JSON
        Artisan::call('images:generate-json');

        session()->flash('success', 'La brochure a été mise à jour avec succès.');
        $this->closeEditModal();
    }
}
