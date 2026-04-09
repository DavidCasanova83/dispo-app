<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\EditsBrochures;
use App\Models\Author;
use App\Models\BrochureReport;
use App\Models\Category;
use App\Models\Image;
use App\Models\Sector;
use App\Models\SubCategory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager as InterventionImageManager;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\WithPagination;

class ImageManager extends Component
{
    use WithFileUploads, WithPagination, EditsBrochures;

    public $contentFiles = [];          // Fichiers de contenu principal (PDF ou images) - OBLIGATOIRE
    public $presentationImages = [];    // Images de présentation (optionnelles)
    public $search = '';               // Recherche par nom

    // Filtres et tri pour la liste des brochures
    public $filterCategoryId = '';
    public $filterSubCategoryId = '';
    public $filterAuthorId = '';
    public $filterSectorId = '';
    public $filterResponsableId = '';
    public $filterEditionYear = '';
    public $filterPrintAvailable = '';   // '', '1', '0'
    public $sortField = 'display_order'; // display_order|created_at|title|size|edition_year
    public $sortDirection = 'asc';       // asc|desc

    public $showDeleteModal = false;
    public $selectedImage = null;
    public $titles = [];               // Titres pour chaque image
    public $altTexts = [];             // Alt texts pour chaque image
    public $descriptions = [];         // Descriptions pour chaque image
    public $linkUrls = [];             // URLs de liens pour chaque image
    public $linkTexts = [];            // Textes de liens pour chaque image
    public $calameoLinkUrls = [];      // URLs de liens Calameo pour chaque image
    public $calameoLinkTexts = [];     // Textes de liens Calameo pour chaque image
    public $quantitiesAvailable = [];  // Quantités disponibles pour chaque image
    public $maxOrderQuantities = [];   // Quantités max de commande pour chaque image
    public $printAvailables = [];      // Disponibilité impression pour chaque image
    public $editionYears = [];         // Années d'édition pour chaque image
    public $displayOrders = [];        // Ordre d'affichage pour chaque image
    public $usedDisplayOrders = [];    // Liste des ordres déjà utilisés
    public $categoryIds = [];          // Catégories pour chaque image
    public $subCategoryIds = [];       // Sous-catégories pour chaque image
    public $authorIds = [];            // Auteurs pour chaque image
    public $sectorIds = [];            // Secteurs pour chaque image
    public $responsableIds = [];        // Responsables pour chaque image

    // Propriétés pour la gestion des entités (CRUD)
    public $newCategoryName = '';
    public $newSubCategoryName = '';
    public $newSubCategoryCategoryId = null;
    public $newAuthorName = '';
    public $newAuthorDefaultImage = null;  // Image par défaut pour le nouvel auteur
    public $newSectorName = '';

    // Propriétés pour les images par défaut
    public $useDefaultImages = [];          // Checkbox pour utiliser image par défaut pour chaque upload
    public $globalDefaultImage = null;      // Upload pour l'image par défaut globale
    public $showDefaultImageConfig = false; // Toggle pour la section configuration
    public $editingAuthorId = null;         // ID de l'auteur en cours d'édition pour image par défaut
    public $editAuthorDefaultImage = null;  // Upload pour modifier l'image par défaut d'un auteur existant

    // Propriétés pour les signalements
    public bool $showReportModal = false;
    public ?BrochureReport $selectedReport = null;
    public string $resolutionNote = '';

    // Whitelist des MIME types autorisés
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    // Whitelist des MIME types pour fichiers téléchargeables (PDF + images)
    private const ALLOWED_DOWNLOAD_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'filterCategoryId' => ['except' => ''],
        'filterSubCategoryId' => ['except' => ''],
        'filterAuthorId' => ['except' => ''],
        'filterSectorId' => ['except' => ''],
        'filterResponsableId' => ['except' => ''],
        'filterEditionYear' => ['except' => ''],
        'filterPrintAvailable' => ['except' => ''],
        'sortField' => ['except' => 'display_order'],
        'sortDirection' => ['except' => 'asc'],
    ];

    // Validation des uploads
    protected $rules = [
        'contentFiles.*' => 'required|mimes:pdf,jpg,jpeg,png|max:51200', // 50MB max - PDF ou image (obligatoire)
        'presentationImages.*' => 'nullable|image|max:10240', // 10MB max - image de présentation (optionnel)
    ];

    protected $messages = [
        'contentFiles.*.required' => 'Le fichier de contenu est obligatoire.',
        'contentFiles.*.mimes' => 'Le fichier doit être un PDF ou une image (JPG, PNG).',
        'contentFiles.*.max' => 'Le fichier ne doit pas dépasser 50 MB.',
        'presentationImages.*.image' => 'L\'image de présentation doit être une image.',
        'presentationImages.*.max' => 'L\'image de présentation ne doit pas dépasser 10 MB.',
    ];

    /**
     * Si la page est ouverte avec ?editId=X, ouvrir directement le modal d'édition
     * de cette brochure (utilisé depuis les liens admin sur la page publique).
     */
    public function mount(): void
    {
        $editId = (int) request()->query('editId');
        if ($editId > 0 && Image::whereKey($editId)->exists()) {
            $this->openEditModal($editId);
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterCategoryId()
    {
        $this->resetPage();
        $this->filterSubCategoryId = '';
    }

    public function updatingFilterSubCategoryId()
    {
        $this->resetPage();
    }

    public function updatingFilterAuthorId()
    {
        $this->resetPage();
    }

    public function updatingFilterSectorId()
    {
        $this->resetPage();
    }

    public function updatingFilterResponsableId()
    {
        $this->resetPage();
    }

    public function updatingFilterEditionYear()
    {
        $this->resetPage();
    }

    public function updatingFilterPrintAvailable()
    {
        $this->resetPage();
    }

    public function updatingSortField()
    {
        $this->resetPage();
    }

    public function updatingSortDirection()
    {
        $this->resetPage();
    }

    /**
     * Réinitialiser tous les filtres de la liste des brochures
     */
    public function resetBrochureFilters()
    {
        $this->reset([
            'search',
            'filterCategoryId',
            'filterSubCategoryId',
            'filterAuthorId',
            'filterSectorId',
            'filterResponsableId',
            'filterEditionYear',
            'filterPrintAvailable',
        ]);
        $this->sortField = 'display_order';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    /**
     * Quand la checkbox "utiliser image par défaut" change, nettoyer l'image de présentation uploadée
     */
    public function updatedUseDefaultImages($value, $key)
    {
        if ($value) {
            unset($this->presentationImages[$key]);
        }
    }

    /**
     * Upload des brochures (PDF ou images comme contenu principal)
     */
    public function uploadImages()
    {
        // Rate limiting: max 10 uploads par minute
        $executed = RateLimiter::attempt(
            'upload-images:' . auth()->id(),
            10,
            function() {},
            60
        );

        if (!$executed) {
            session()->flash('error', 'Trop de tentatives d\'upload. Veuillez attendre avant de réessayer.');
            return;
        }

        // Autorisation
        $this->authorize('create', Image::class);

        $this->validate();

        if (empty($this->contentFiles)) {
            session()->flash('error', 'Veuillez sélectionner au moins un fichier de contenu (PDF ou image).');
            return;
        }

        // Validation des champs obligatoires (titre, année, auteur, responsable, image de présentation pour PDF)
        if (!$this->canUpload) {
            $missing = $this->missingRequiredFields;
            session()->flash('error', 'Champs obligatoires manquants : ' . implode(' | ', $missing));
            return;
        }

        $uploadedCount = 0;
        $errors = [];

        foreach ($this->contentFiles as $index => $contentFile) {
            try {
                // Validation MIME type avec whitelist
                if (!in_array($contentFile->getMimeType(), self::ALLOWED_DOWNLOAD_MIME_TYPES)) {
                    $errors[] = "{$contentFile->getClientOriginalName()}: Type de fichier non autorisé.";
                    continue;
                }

                // Validation de l'extension réelle
                $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
                $extension = strtolower($contentFile->getClientOriginalExtension());
                if (!in_array($extension, $allowedExtensions)) {
                    $errors[] = "{$contentFile->getClientOriginalName()}: Extension non autorisée.";
                    continue;
                }

                $isPdf = ($extension === 'pdf');

                // Sanitiser le nom original
                $originalName = Str::limit(Str::slug($this->titles[$index] ?? '') ?: Str::slug(pathinfo($contentFile->getClientOriginalName(), PATHINFO_FILENAME)), 100);
                if (empty($originalName)) {
                    $originalName = 'brochure';
                }

                // === STOCKER LE FICHIER DE CONTENU (PDF ou image) ===
                $baseContentFilename = $originalName . '.' . $extension;
                $contentFilename = $baseContentFilename;
                $contentCounter = 1;
                while (Storage::disk('public')->exists('pdfs/' . $contentFilename)) {
                    $contentFilename = $originalName . '-' . $contentCounter . '.' . $extension;
                    $contentCounter++;
                }
                $pdfPath = $contentFile->storeAs('pdfs', $contentFilename, 'public');
                $contentFullPath = Storage::disk('public')->path($pdfPath);
                $contentSize = filesize($contentFullPath);

                // === GÉRER L'IMAGE DE PRÉSENTATION ===
                $path = null;
                $thumbnailPath = null;
                $width = null;
                $height = null;
                $mimeType = $contentFile->getMimeType();
                $displayFilename = $contentFilename;

                // Si "utiliser image par défaut" est coché, ignorer toute image de présentation uploadée
                $useDefault = $isPdf && isset($this->useDefaultImages[$index]) && $this->useDefaultImages[$index];

                // Vérifier si une image de présentation a été fournie (et qu'on n'utilise pas l'image par défaut)
                $hasPresentationImage = !$useDefault && isset($this->presentationImages[$index]) && $this->presentationImages[$index];

                if ($hasPresentationImage) {
                    // === IMAGE DE PRÉSENTATION FOURNIE ===
                    $presentationFile = $this->presentationImages[$index];

                    // Valider le MIME type
                    if (!in_array($presentationFile->getMimeType(), self::ALLOWED_MIME_TYPES)) {
                        $errors[] = "{$presentationFile->getClientOriginalName()}: Image de présentation - type non autorisé.";
                        // Continuer sans image de présentation
                    } else {
                        $presentationExtension = strtolower($presentationFile->getClientOriginalExtension());
                        $basePresentationFilename = $originalName . '.' . $presentationExtension;
                        $presentationFilename = $basePresentationFilename;
                        $presentationCounter = 1;
                        while (Storage::disk('public')->exists('images/' . $presentationFilename)) {
                            $presentationFilename = $originalName . '-' . $presentationCounter . '.' . $presentationExtension;
                            $presentationCounter++;
                        }

                        $path = $presentationFile->storeAs('images', $presentationFilename, 'public');
                        $fullPath = Storage::disk('public')->path($path);

                        // Traiter l'image de présentation
                        $manager = new InterventionImageManager(new Driver());
                        $img = $manager->read($fullPath);
                        $width = $img->width();
                        $height = $img->height();
                        $img->save($fullPath, quality: 85);
                        $mimeType = $presentationFile->getMimeType();
                        $displayFilename = $presentationFilename;

                        // Générer le thumbnail
                        $thumbnailFilename = 'thumb_' . $presentationFilename;
                        $thumbnailPath = 'images/thumbnails/' . $thumbnailFilename;
                        $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);
                        $thumbnailDir = dirname($thumbnailFullPath);
                        if (!file_exists($thumbnailDir)) {
                            mkdir($thumbnailDir, 0755, true);
                        }
                        $thumbnail = $manager->read($fullPath);
                        $thumbnail->cover(300, 300);
                        $thumbnail->save($thumbnailFullPath, quality: 80);
                    }
                }

                // Si pas d'image de présentation et contenu est un PDF
                if (!$path && $isPdf) {
                    if ($useDefault) {
                        // Récupérer le chemin de l'image par défaut
                        $defaultImagePath = $this->getActiveDefaultImagePath($index);

                        if (!$defaultImagePath || !Storage::disk('public')->exists($defaultImagePath)) {
                            $errors[] = "{$contentFile->getClientOriginalName()}: Aucune image par défaut disponible. Veuillez configurer une image par défaut ou en uploader une.";
                            Storage::disk('public')->delete($pdfPath);
                            continue;
                        }

                        // Copier l'image par défaut vers le dossier images
                        $defaultExtension = pathinfo($defaultImagePath, PATHINFO_EXTENSION);
                        $basePresentationFilename = $originalName . '.' . $defaultExtension;
                        $presentationFilename = $basePresentationFilename;
                        $presentationCounter = 1;
                        while (Storage::disk('public')->exists('images/' . $presentationFilename)) {
                            $presentationFilename = $originalName . '-' . $presentationCounter . '.' . $defaultExtension;
                            $presentationCounter++;
                        }

                        // Copier le fichier
                        Storage::disk('public')->copy($defaultImagePath, 'images/' . $presentationFilename);
                        $path = 'images/' . $presentationFilename;
                        $fullPath = Storage::disk('public')->path($path);

                        // Traiter l'image
                        $manager = new InterventionImageManager(new Driver());
                        $img = $manager->read($fullPath);
                        $width = $img->width();
                        $height = $img->height();
                        $img->save($fullPath, quality: 85);
                        $mimeType = mime_content_type($fullPath);
                        $displayFilename = $presentationFilename;

                        // Générer le thumbnail
                        $thumbnailFilename = 'thumb_' . $presentationFilename;
                        $thumbnailPath = 'images/thumbnails/' . $thumbnailFilename;
                        $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);
                        $thumbnailDir = dirname($thumbnailFullPath);
                        if (!file_exists($thumbnailDir)) {
                            mkdir($thumbnailDir, 0755, true);
                        }
                        $thumbnail = $manager->read($fullPath);
                        $thumbnail->cover(300, 300);
                        $thumbnail->save($thumbnailFullPath, quality: 80);
                    } else {
                        $errors[] = "{$contentFile->getClientOriginalName()}: Une image de présentation est obligatoire pour les fichiers PDF.";
                        // Supprimer le fichier PDF uploadé
                        Storage::disk('public')->delete($pdfPath);
                        continue;
                    }
                }

                // Si pas d'image de présentation et contenu est une image, l'utiliser comme présentation
                if (!$path && !$isPdf) {
                    // Copier l'image de contenu vers images/ pour la présentation
                    $imageExtension = $extension;
                    $basePresentationFilename = $originalName . '.' . $imageExtension;
                    $presentationFilename = $basePresentationFilename;
                    $presentationCounter = 1;
                    while (Storage::disk('public')->exists('images/' . $presentationFilename)) {
                        $presentationFilename = $originalName . '-' . $presentationCounter . '.' . $imageExtension;
                        $presentationCounter++;
                    }

                    // Copier le fichier
                    Storage::disk('public')->copy($pdfPath, 'images/' . $presentationFilename);
                    $path = 'images/' . $presentationFilename;
                    $fullPath = Storage::disk('public')->path($path);

                    // Traiter l'image
                    $manager = new InterventionImageManager(new Driver());
                    $img = $manager->read($fullPath);
                    $width = $img->width();
                    $height = $img->height();
                    $img->save($fullPath, quality: 85);
                    $displayFilename = $presentationFilename;

                    // Générer le thumbnail
                    $thumbnailFilename = 'thumb_' . $presentationFilename;
                    $thumbnailPath = 'images/thumbnails/' . $thumbnailFilename;
                    $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);
                    $thumbnailDir = dirname($thumbnailFullPath);
                    if (!file_exists($thumbnailDir)) {
                        mkdir($thumbnailDir, 0755, true);
                    }
                    $thumbnail = $manager->read($fullPath);
                    $thumbnail->cover(300, 300);
                    $thumbnail->save($thumbnailFullPath, quality: 80);
                }

                // Créer l'entrée en base
                Image::create([
                    'name' => $contentFile->getClientOriginalName(),
                    'title' => $this->titles[$index] ?? null,
                    'filename' => $displayFilename,
                    'path' => $path,
                    'thumbnail_path' => $thumbnailPath,
                    'pdf_path' => $pdfPath,
                    'url' => $path,
                    'alt_text' => $this->altTexts[$index] ?? null,
                    'description' => $this->descriptions[$index] ?? null,
                    'link_url' => $this->linkUrls[$index] ?? null,
                    'link_text' => $this->linkTexts[$index] ?? null,
                    'calameo_link_url' => $this->calameoLinkUrls[$index] ?? null,
                    'calameo_link_text' => $this->calameoLinkTexts[$index] ?? null,
                    'mime_type' => $mimeType,
                    'size' => $contentSize,
                    'width' => $width,
                    'height' => $height,
                    'uploaded_by' => auth()->id(),
                    'quantity_available' => $this->quantitiesAvailable[$index] ?? null,
                    'max_order_quantity' => $this->maxOrderQuantities[$index] ?? null,
                    'print_available' => isset($this->printAvailables[$index]) ? (bool) $this->printAvailables[$index] : false,
                    'edition_year' => $this->editionYears[$index] ?? null,
                    'display_order' => $this->displayOrders[$index] ?? null,
                    'category_id' => $this->categoryIds[$index] ?? null,
                    'sub_category_id' => $this->subCategoryIds[$index] ?? null,
                    'author_id' => $this->authorIds[$index] ?? null,
                    'sector_id' => $this->sectorIds[$index] ?? null,
                    'responsable_id' => $this->responsableIds[$index] ?? null,
                ]);

                $uploadedCount++;
            } catch (\Exception $e) {
                $errors[] = "{$contentFile->getClientOriginalName()}: {$e->getMessage()}";
            }
        }

        // Messages de résultat
        if ($uploadedCount > 0) {
            session()->flash('success', "{$uploadedCount} brochure(s) uploadée(s) avec succès.");

            // Régénérer le fichier JSON
            Artisan::call('images:generate-json');
        }

        if (!empty($errors)) {
            session()->flash('error', 'Erreurs : ' . implode(' | ', $errors));
        }

        $this->reset(['contentFiles', 'presentationImages', 'titles', 'altTexts', 'descriptions', 'linkUrls', 'linkTexts', 'calameoLinkUrls', 'calameoLinkTexts', 'quantitiesAvailable', 'maxOrderQuantities', 'printAvailables', 'editionYears', 'displayOrders', 'categoryIds', 'subCategoryIds', 'authorIds', 'sectorIds', 'responsableIds', 'useDefaultImages']);
    }

    /**
     * Ouvrir le modal de suppression
     */
    public function openDeleteModal($imageId)
    {
        $this->selectedImage = Image::findOrFail($imageId);
        $this->showDeleteModal = true;
    }

    /**
     * Fermer le modal
     */
    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->selectedImage = null;
    }

    /**
     * Supprimer une image
     */
    public function deleteImage($imageId)
    {
        $image = Image::findOrFail($imageId);

        // Vérifier l'autorisation
        $this->authorize('delete', $image);

        // Le fichier physique sera supprimé par le model event
        $image->delete();

        // Régénérer le fichier JSON
        Artisan::call('images:generate-json');

        session()->flash('success', "La brochure {$image->name} a été supprimée.");
        $this->closeDeleteModal();
    }


    /**
     * Ajouter une nouvelle catégorie
     */
    public function addCategory()
    {
        $this->validate(['newCategoryName' => 'required|string|max:255|unique:categories,name']);
        Category::create(['name' => $this->newCategoryName]);
        $this->newCategoryName = '';
        session()->flash('success', 'Catégorie ajoutée avec succès.');
    }

    /**
     * Supprimer une catégorie
     */
    public function deleteCategory($id)
    {
        $category = Category::findOrFail($id);
        // Mettre à null les images associées
        Image::where('category_id', $id)->update(['category_id' => null, 'sub_category_id' => null]);
        $category->delete();
        session()->flash('success', 'Catégorie supprimée.');
    }

    /**
     * Ajouter une nouvelle sous-catégorie
     */
    public function addSubCategory()
    {
        $this->validate([
            'newSubCategoryName' => 'required|string|max:255',
            'newSubCategoryCategoryId' => 'required|exists:categories,id',
        ]);

        // Vérifier l'unicité dans la catégorie
        $exists = SubCategory::where('name', $this->newSubCategoryName)
            ->where('category_id', $this->newSubCategoryCategoryId)
            ->exists();

        if ($exists) {
            $this->addError('newSubCategoryName', 'Cette sous-catégorie existe déjà dans cette catégorie.');
            return;
        }

        SubCategory::create([
            'name' => $this->newSubCategoryName,
            'category_id' => $this->newSubCategoryCategoryId,
        ]);

        $this->newSubCategoryName = '';
        $this->newSubCategoryCategoryId = null;
        session()->flash('success', 'Sous-catégorie ajoutée avec succès.');
    }

    /**
     * Supprimer une sous-catégorie
     */
    public function deleteSubCategory($id)
    {
        $subCategory = SubCategory::findOrFail($id);
        Image::where('sub_category_id', $id)->update(['sub_category_id' => null]);
        $subCategory->delete();
        session()->flash('success', 'Sous-catégorie supprimée.');
    }

    /**
     * Récupère les sous-catégories d'une catégorie (pour les selects dynamiques)
     */
    public function getSubCategoriesForCategory($categoryId): array
    {
        if (!$categoryId) {
            return [];
        }
        return SubCategory::where('category_id', $categoryId)->orderBy('name')->get()->toArray();
    }

    /**
     * Quand la catégorie change dans le formulaire d'upload, réinitialiser la sous-catégorie
     */
    public function updatedCategoryIds($value, $key)
    {
        unset($this->subCategoryIds[$key]);
    }

    /**
     * Quand l'auteur change dans le formulaire d'upload, cocher automatiquement
     * "utiliser l'image par défaut" si une image par défaut est disponible (auteur ou globale).
     * S'applique uniquement aux fichiers PDF.
     */
    public function updatedAuthorIds($value, $key)
    {
        if (!isset($this->contentFiles[$key])) {
            return;
        }

        $file = $this->contentFiles[$key];
        $isPdf = strtolower($file->getClientOriginalExtension()) === 'pdf';

        if (!$isPdf) {
            return;
        }

        if ($value && $this->getActiveDefaultImagePath($key)) {
            $this->useDefaultImages[$key] = true;
        } else {
            // Pas de défaut disponible, on décoche pour forcer l'upload manuel
            $this->useDefaultImages[$key] = false;
        }
    }

    /**
     * Vérifie si tous les champs obligatoires du formulaire d'upload sont renseignés.
     * Utilisé pour activer/désactiver le bouton "Uploader".
     */
    public function getCanUploadProperty(): bool
    {
        if (empty($this->contentFiles)) {
            return false;
        }

        foreach ($this->contentFiles as $index => $file) {
            // Champs texte / select obligatoires
            if (empty($this->titles[$index] ?? null)) {
                return false;
            }
            if (empty($this->editionYears[$index] ?? null)) {
                return false;
            }
            if (empty($this->authorIds[$index] ?? null)) {
                return false;
            }
            if (empty($this->responsableIds[$index] ?? null)) {
                return false;
            }

            // Image de présentation : obligatoire pour les PDF (sauf si image par défaut active)
            $isPdf = strtolower($file->getClientOriginalExtension()) === 'pdf';
            if ($isPdf) {
                $useDefault = !empty($this->useDefaultImages[$index] ?? null);
                $hasPresentationImage = !empty($this->presentationImages[$index] ?? null);

                if ($useDefault) {
                    if (!$this->getActiveDefaultImagePath($index)) {
                        return false;
                    }
                } elseif (!$hasPresentationImage) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Liste des champs obligatoires manquants pour affichage à l'utilisateur.
     */
    public function getMissingRequiredFieldsProperty(): array
    {
        if (empty($this->contentFiles)) {
            return [];
        }

        $missing = [];
        foreach ($this->contentFiles as $index => $file) {
            $brochureNum = $index + 1;
            if (empty($this->titles[$index] ?? null)) {
                $missing[] = "Brochure {$brochureNum} : titre";
            }
            if (empty($this->editionYears[$index] ?? null)) {
                $missing[] = "Brochure {$brochureNum} : année d'édition";
            }
            if (empty($this->authorIds[$index] ?? null)) {
                $missing[] = "Brochure {$brochureNum} : auteur";
            }
            if (empty($this->responsableIds[$index] ?? null)) {
                $missing[] = "Brochure {$brochureNum} : responsable";
            }

            $isPdf = strtolower($file->getClientOriginalExtension()) === 'pdf';
            if ($isPdf) {
                $useDefault = !empty($this->useDefaultImages[$index] ?? null);
                $hasPresentationImage = !empty($this->presentationImages[$index] ?? null);

                if ($useDefault && !$this->getActiveDefaultImagePath($index)) {
                    $missing[] = "Brochure {$brochureNum} : image par défaut indisponible";
                } elseif (!$useDefault && !$hasPresentationImage) {
                    $missing[] = "Brochure {$brochureNum} : image de présentation";
                }
            }
        }

        return $missing;
    }

    /**
     * Ajouter un nouvel auteur
     */
    public function addAuthor()
    {
        $this->validate([
            'newAuthorName' => 'required|string|max:255|unique:authors,name',
            'newAuthorDefaultImage' => 'nullable|image|max:10240',
        ]);

        $defaultImagePath = null;

        // Gérer l'upload de l'image par défaut si fournie
        if ($this->newAuthorDefaultImage) {
            $author = Author::create(['name' => $this->newAuthorName]);

            // Créer le dossier si nécessaire
            $defaultsDir = storage_path('app/public/images/defaults/authors');
            if (!file_exists($defaultsDir)) {
                mkdir($defaultsDir, 0755, true);
            }

            $filename = 'author-' . $author->id . '-default.' . $this->newAuthorDefaultImage->getClientOriginalExtension();
            $this->newAuthorDefaultImage->storeAs('images/defaults/authors', $filename, 'public');
            $defaultImagePath = 'images/defaults/authors/' . $filename;

            // Mettre à jour l'auteur avec le chemin de l'image
            $author->update(['default_image_path' => $defaultImagePath]);
        } else {
            Author::create(['name' => $this->newAuthorName]);
        }

        $this->newAuthorName = '';
        $this->newAuthorDefaultImage = null;
        session()->flash('success', 'Auteur ajouté avec succès.');
    }

    /**
     * Supprimer un auteur
     */
    public function deleteAuthor($id)
    {
        $author = Author::findOrFail($id);

        // Supprimer l'image par défaut de l'auteur du disque
        if ($author->default_image_path && Storage::disk('public')->exists($author->default_image_path)) {
            Storage::disk('public')->delete($author->default_image_path);
        }

        Image::where('author_id', $id)->update(['author_id' => null]);
        $author->delete();
        session()->flash('success', 'Auteur supprimé.');
    }

    /**
     * Ajouter un nouveau secteur
     */
    public function addSector()
    {
        $this->validate(['newSectorName' => 'required|string|max:255|unique:sectors,name']);
        Sector::create(['name' => $this->newSectorName]);
        $this->newSectorName = '';
        session()->flash('success', 'Secteur ajouté avec succès.');
    }

    /**
     * Supprimer un secteur
     */
    public function deleteSector($id)
    {
        $sector = Sector::findOrFail($id);
        Image::where('sector_id', $id)->update(['sector_id' => null]);
        $sector->delete();
        session()->flash('success', 'Secteur supprimé.');
    }

    /**
     * Ouvrir le modal de détail d'un signalement
     */
    public function openReportModal(int $reportId): void
    {
        $this->selectedReport = BrochureReport::with(['image', 'user'])->find($reportId);
        if ($this->selectedReport && $this->selectedReport->image) {
            // Marquer comme lu
            if (!$this->selectedReport->is_read) {
                $this->selectedReport->markAsRead();
            }
            $this->resolutionNote = '';
            $this->showReportModal = true;
        } else {
            // La brochure a été supprimée entre-temps
            if ($this->selectedReport) {
                $this->selectedReport->resolve(Auth::user(), 'Résolu automatiquement : brochure supprimée.');
            }
            session()->flash('info', 'Ce signalement a été résolu car la brochure a été supprimée.');
        }
    }

    /**
     * Fermer le modal de signalement
     */
    public function closeReportModal(): void
    {
        $this->showReportModal = false;
        $this->selectedReport = null;
        $this->resolutionNote = '';
    }

    /**
     * Résoudre un signalement
     */
    public function resolveReport(): void
    {
        if (!$this->selectedReport) {
            return;
        }

        $this->selectedReport->resolve(Auth::user(), $this->resolutionNote ?: null);
        session()->flash('success', 'Le signalement a été marqué comme résolu.');
        $this->closeReportModal();
    }

    /**
     * Toggle la section de configuration des images par défaut
     */
    public function toggleDefaultImageConfig(): void
    {
        $this->showDefaultImageConfig = !$this->showDefaultImageConfig;
    }

    /**
     * Uploader l'image par défaut globale
     */
    public function uploadGlobalDefaultImage(): void
    {
        $this->validate([
            'globalDefaultImage' => 'required|image|max:10240',
        ]);

        // Créer le dossier si nécessaire
        $defaultsDir = storage_path('app/public/images/defaults');
        if (!file_exists($defaultsDir)) {
            mkdir($defaultsDir, 0755, true);
        }

        // Supprimer l'ancienne image si elle existe
        $oldPath = Setting::get('default_brochure_image_path');
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        // Stocker la nouvelle image
        $filename = 'global-default.' . $this->globalDefaultImage->getClientOriginalExtension();
        $this->globalDefaultImage->storeAs('images/defaults', $filename, 'public');
        $path = 'images/defaults/' . $filename;

        // Sauvegarder le chemin dans les settings
        Setting::set('default_brochure_image_path', $path);

        $this->globalDefaultImage = null;
        session()->flash('success', 'Image par défaut globale mise à jour.');
    }

    /**
     * Supprimer l'image par défaut globale
     */
    public function deleteGlobalDefaultImage(): void
    {
        $path = Setting::get('default_brochure_image_path');
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
        Setting::set('default_brochure_image_path', null);
        session()->flash('success', 'Image par défaut globale supprimée.');
    }

    /**
     * Ouvrir/fermer le formulaire d'édition de l'image par défaut d'un auteur
     */
    public function toggleEditAuthorDefaultImage($authorId): void
    {
        if ($this->editingAuthorId === $authorId) {
            $this->editingAuthorId = null;
            $this->editAuthorDefaultImage = null;
        } else {
            $this->editingAuthorId = $authorId;
            $this->editAuthorDefaultImage = null;
        }
    }

    /**
     * Mettre à jour l'image par défaut d'un auteur
     */
    public function updateAuthorDefaultImage($authorId): void
    {
        $author = Author::findOrFail($authorId);

        $this->validate([
            'editAuthorDefaultImage' => 'required|image|max:10240',
        ]);

        // Supprimer l'ancienne image si elle existe
        if ($author->default_image_path && Storage::disk('public')->exists($author->default_image_path)) {
            Storage::disk('public')->delete($author->default_image_path);
        }

        // Créer le dossier si nécessaire
        $defaultsDir = storage_path('app/public/images/defaults/authors');
        if (!file_exists($defaultsDir)) {
            mkdir($defaultsDir, 0755, true);
        }

        // Stocker la nouvelle image
        $filename = 'author-' . $author->id . '-default.' . $this->editAuthorDefaultImage->getClientOriginalExtension();
        $this->editAuthorDefaultImage->storeAs('images/defaults/authors', $filename, 'public');
        $path = 'images/defaults/authors/' . $filename;

        $author->update(['default_image_path' => $path]);

        // Reset
        $this->editingAuthorId = null;
        $this->editAuthorDefaultImage = null;

        session()->flash('success', 'Image par défaut de l\'auteur mise à jour.');
    }

    /**
     * Supprimer l'image par défaut d'un auteur
     */
    public function deleteAuthorDefaultImage($authorId): void
    {
        $author = Author::findOrFail($authorId);

        if ($author->default_image_path && Storage::disk('public')->exists($author->default_image_path)) {
            Storage::disk('public')->delete($author->default_image_path);
        }

        $author->update(['default_image_path' => null]);
        session()->flash('success', 'Image par défaut de l\'auteur supprimée.');
    }

    /**
     * Récupère le chemin de l'image par défaut à utiliser pour un index donné
     */
    public function getActiveDefaultImagePath(int $index): ?string
    {
        // Priorité: Image de l'auteur > Image globale
        if (isset($this->authorIds[$index]) && $this->authorIds[$index]) {
            $author = Author::find($this->authorIds[$index]);
            if ($author && $author->hasDefaultImage()) {
                return $author->default_image_path;
            }
        }

        // Sinon utiliser l'image globale (vérifier qu'elle existe sur le disque)
        $globalPath = Setting::get('default_brochure_image_path');
        if ($globalPath && Storage::disk('public')->exists($globalPath)) {
            return $globalPath;
        }

        return null;
    }

    /**
     * Récupère l'URL de l'image par défaut à afficher pour un index donné
     */
    public function getActiveDefaultImageUrl(int $index): ?string
    {
        $path = $this->getActiveDefaultImagePath($index);
        return $path ? asset('storage/' . $path) : null;
    }

    public function render()
    {
        // Charger les ordres d'affichage déjà utilisés
        $this->usedDisplayOrders = Image::whereNotNull('display_order')
            ->orderBy('display_order')
            ->pluck('display_order')
            ->unique()
            ->values()
            ->toArray();

        $query = Image::with(['uploader', 'category', 'subCategory', 'author', 'sector', 'responsable']);

        // Recherche (groupée pour ne pas casser les filtres)
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('filename', 'like', '%' . $this->search . '%')
                  ->orWhere('title', 'like', '%' . $this->search . '%');
            });
        }

        // Filtres
        if ($this->filterCategoryId !== '' && $this->filterCategoryId !== null) {
            $query->where('category_id', $this->filterCategoryId);
        }
        if ($this->filterSubCategoryId !== '' && $this->filterSubCategoryId !== null) {
            $query->where('sub_category_id', $this->filterSubCategoryId);
        }
        if ($this->filterAuthorId !== '' && $this->filterAuthorId !== null) {
            $query->where('author_id', $this->filterAuthorId);
        }
        if ($this->filterSectorId !== '' && $this->filterSectorId !== null) {
            $query->where('sector_id', $this->filterSectorId);
        }
        if ($this->filterResponsableId !== '' && $this->filterResponsableId !== null) {
            $query->where('responsable_id', $this->filterResponsableId);
        }
        if ($this->filterEditionYear !== '' && $this->filterEditionYear !== null) {
            $query->where('edition_year', $this->filterEditionYear);
        }
        if ($this->filterPrintAvailable !== '' && $this->filterPrintAvailable !== null) {
            $query->where('print_available', (bool) $this->filterPrintAvailable);
        }

        // Tri
        $allowedSortFields = ['display_order', 'created_at', 'title', 'size', 'edition_year'];
        $sortField = in_array($this->sortField, $allowedSortFields, true) ? $this->sortField : 'display_order';
        $sortDirection = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        if ($sortField === 'display_order') {
            $query->orderByRaw('display_order IS NULL, display_order ' . $sortDirection)
                  ->orderBy('created_at', 'desc');
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        $imagesList = $query->paginate(12);

        // Liste des années d'édition distinctes pour le filtre
        $editionYears = Image::whereNotNull('edition_year')
            ->orderBy('edition_year', 'desc')
            ->pluck('edition_year')
            ->unique()
            ->values()
            ->toArray();

        $stats = [
            'total' => Image::count(),
            'total_size' => Image::sum('size'),
            'today' => Image::whereDate('created_at', today())->count(),
        ];

        // Récupérer les signalements non résolus dont la brochure existe encore
        $pendingReports = BrochureReport::with(['image', 'user'])
            ->unresolved()
            ->whereHas('image')
            ->orderBy('created_at', 'desc')
            ->get();

        // Auto-résoudre les signalements dont la brochure a été supprimée
        BrochureReport::unresolved()
            ->whereDoesntHave('image')
            ->update([
                'is_resolved' => true,
                'resolution_note' => 'Résolu automatiquement : brochure supprimée.',
                'resolved_at' => now(),
            ]);

        $unreadReportsCount = BrochureReport::unread()->unresolved()->whereHas('image')->count();

        // Récupérer le chemin de l'image par défaut globale
        $globalDefaultImagePath = Setting::get('default_brochure_image_path');

        return view('livewire.admin.image-manager', [
            'imagesList' => $imagesList,
            'stats' => $stats,
            'categories' => Category::orderBy('name')->get(),
            'subCategories' => SubCategory::with('category')->orderBy('name')->get(),
            'authors' => Author::orderBy('name')->get(),
            'sectors' => Sector::orderBy('name')->get(),
            'responsables' => User::where('approved', true)->orderBy('name')->get(),
            'pendingReports' => $pendingReports,
            'unreadReportsCount' => $unreadReportsCount,
            'globalDefaultImagePath' => $globalDefaultImagePath,
            'editionYears' => $editionYears,
        ])->layout('components.layouts.app');
    }
}
