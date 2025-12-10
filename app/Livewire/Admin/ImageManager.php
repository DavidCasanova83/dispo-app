<?php

namespace App\Livewire\Admin;

use App\Models\Author;
use App\Models\BrochureReport;
use App\Models\Category;
use App\Models\Image;
use App\Models\Sector;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
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
    use WithFileUploads, WithPagination;

    public $images = [];               // Images à uploader
    public $search = '';               // Recherche par nom
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
    public $categoryIds = [];          // Catégories pour chaque image
    public $authorIds = [];            // Auteurs pour chaque image
    public $sectorIds = [];            // Secteurs pour chaque image
    public $pdfFiles = [];              // PDFs pour chaque image

    // Propriétés pour l'édition
    public $showEditModal = false;
    public $editingImage = null;
    public $editTitle = '';
    public $editAltText = '';
    public $editDescription = '';
    public $editLinkUrl = '';
    public $editLinkText = '';
    public $editCalameoLinkUrl = '';
    public $editCalameoLinkText = '';
    public $editQuantityAvailable = null;
    public $editMaxOrderQuantity = null;
    public $editPrintAvailable = false;
    public $editEditionYear = null;
    public $editDisplayOrder = null;
    public $editCategoryId = null;
    public $editAuthorId = null;
    public $editSectorId = null;
    public $editPdfFile = null;         // Nouveau PDF lors de l'édition
    public $removePdf = false;          // Flag pour supprimer le PDF existant

    // Propriétés pour la gestion des entités (CRUD)
    public $newCategoryName = '';
    public $newAuthorName = '';
    public $newSectorName = '';

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

    // Whitelist des MIME types PDF autorisés
    private const ALLOWED_PDF_MIME_TYPES = [
        'application/pdf',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    // Validation des uploads
    protected $rules = [
        'images.*' => 'image|max:10240', // 10MB max par image
        'pdfFiles.*' => 'nullable|mimes:pdf|max:51200', // 50MB max par PDF
    ];

    protected $messages = [
        'images.*.image' => 'Le fichier doit être une image.',
        'images.*.max' => 'L\'image ne doit pas dépasser 10 MB.',
        'pdfFiles.*.mimes' => 'Le fichier doit être un PDF.',
        'pdfFiles.*.max' => 'Le PDF ne doit pas dépasser 50 MB.',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    /**
     * Upload des images
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

        if (empty($this->images)) {
            session()->flash('error', 'Veuillez sélectionner au moins une brochure.');
            return;
        }

        $uploadedCount = 0;
        $errors = [];

        foreach ($this->images as $index => $image) {
            try {
                // Validation MIME type avec whitelist
                if (!in_array($image->getMimeType(), self::ALLOWED_MIME_TYPES)) {
                    $errors[] = "{$image->getClientOriginalName()}: Type de fichier non autorisé.";
                    continue;
                }

                // Validation de l'extension réelle
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $extension = strtolower($image->getClientOriginalExtension());
                if (!in_array($extension, $allowedExtensions)) {
                    $errors[] = "{$image->getClientOriginalName()}: Extension non autorisée.";
                    continue;
                }

                // Sanitiser le nom original
                $originalName = Str::limit(Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)), 100);
                if (empty($originalName)) {
                    $originalName = 'image';
                }

                // Générer un nom basé sur le slug uniquement (URL stable)
                $baseFilename = $originalName . '.' . $extension;

                // Si un fichier existe déjà avec ce nom, ajouter un suffixe numérique
                $filename = $baseFilename;
                $counter = 1;
                while (Storage::disk('public')->exists('images/' . $filename)) {
                    $filename = $originalName . '-' . $counter . '.' . $extension;
                    $counter++;
                }

                // Stocker l'image temporairement
                $path = $image->storeAs('images', $filename, 'public');
                $fullPath = Storage::disk('public')->path($path);

                // Créer l'instance Intervention Image
                $manager = new InterventionImageManager(new Driver());
                $img = $manager->read($fullPath);

                // Extraire les dimensions originales
                $width = $img->width();
                $height = $img->height();

                // Compression automatique - qualité 85%
                $img->save($fullPath, quality: 85);

                // Générer le thumbnail (300x300)
                $thumbnailFilename = 'thumb_' . $filename;
                $thumbnailPath = 'images/thumbnails/' . $thumbnailFilename;
                $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);

                // Créer le dossier thumbnails si il n'existe pas
                $thumbnailDir = dirname($thumbnailFullPath);
                if (!file_exists($thumbnailDir)) {
                    mkdir($thumbnailDir, 0755, true);
                }

                // Créer et sauvegarder le thumbnail
                $thumbnail = $manager->read($fullPath);
                $thumbnail->cover(300, 300);
                $thumbnail->save($thumbnailFullPath, quality: 80);

                // Obtenir la taille finale du fichier après compression
                $finalSize = filesize($fullPath);

                // Gérer l'upload PDF si fourni
                $pdfPath = null;
                if (isset($this->pdfFiles[$index]) && $this->pdfFiles[$index]) {
                    $pdfFile = $this->pdfFiles[$index];

                    // Valider le MIME type PDF
                    if (!in_array($pdfFile->getMimeType(), self::ALLOWED_PDF_MIME_TYPES)) {
                        $errors[] = "{$pdfFile->getClientOriginalName()}: Type de fichier PDF non autorisé.";
                    } else {
                        // Valider l'extension
                        $pdfExtension = strtolower($pdfFile->getClientOriginalExtension());
                        if ($pdfExtension !== 'pdf') {
                            $errors[] = "{$pdfFile->getClientOriginalName()}: Extension PDF invalide.";
                        } else {
                            // Utiliser le titre pour le nom du PDF (fallback sur le nom de fichier)
                            $pdfSlug = Str::slug($this->titles[$index] ?? '') ?: $originalName;
                            $basePdfFilename = $pdfSlug . '.pdf';

                            // Si un fichier existe déjà avec ce nom, ajouter un suffixe numérique
                            $pdfFilename = $basePdfFilename;
                            $pdfCounter = 1;
                            while (Storage::disk('public')->exists('pdfs/' . $pdfFilename)) {
                                $pdfFilename = $pdfSlug . '-' . $pdfCounter . '.pdf';
                                $pdfCounter++;
                            }

                            // Stocker le PDF
                            $pdfPath = $pdfFile->storeAs('pdfs', $pdfFilename, 'public');
                        }
                    }
                }

                // Créer l'entrée en base
                Image::create([
                    'name' => $image->getClientOriginalName(),
                    'title' => $this->titles[$index] ?? null,
                    'filename' => $filename,
                    'path' => $path,
                    'thumbnail_path' => $thumbnailPath,
                    'pdf_path' => $pdfPath,
                    'url' => $path, // Stocker le chemin, pas l'URL complète
                    'alt_text' => $this->altTexts[$index] ?? null,
                    'description' => $this->descriptions[$index] ?? null,
                    'link_url' => $this->linkUrls[$index] ?? null,
                    'link_text' => $this->linkTexts[$index] ?? null,
                    'calameo_link_url' => $this->calameoLinkUrls[$index] ?? null,
                    'calameo_link_text' => $this->calameoLinkTexts[$index] ?? null,
                    'mime_type' => $image->getMimeType(),
                    'size' => $finalSize,
                    'width' => $width,
                    'height' => $height,
                    'uploaded_by' => auth()->id(),
                    'quantity_available' => $this->quantitiesAvailable[$index] ?? null,
                    'max_order_quantity' => $this->maxOrderQuantities[$index] ?? null,
                    'print_available' => isset($this->printAvailables[$index]) ? (bool) $this->printAvailables[$index] : false,
                    'edition_year' => $this->editionYears[$index] ?? null,
                    'display_order' => $this->displayOrders[$index] ?? null,
                    'category_id' => $this->categoryIds[$index] ?? null,
                    'author_id' => $this->authorIds[$index] ?? null,
                    'sector_id' => $this->sectorIds[$index] ?? null,
                ]);

                $uploadedCount++;
            } catch (\Exception $e) {
                $errors[] = "{$image->getClientOriginalName()}: {$e->getMessage()}";
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

        $this->reset(['images', 'titles', 'altTexts', 'descriptions', 'linkUrls', 'linkTexts', 'calameoLinkUrls', 'calameoLinkTexts', 'quantitiesAvailable', 'maxOrderQuantities', 'printAvailables', 'editionYears', 'displayOrders', 'categoryIds', 'authorIds', 'sectorIds', 'pdfFiles']);
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
     * Ouvrir le modal d'édition
     */
    public function openEditModal($imageId)
    {
        $this->editingImage = Image::findOrFail($imageId);

        // Charger les valeurs actuelles
        $this->editTitle = $this->editingImage->title ?? '';
        $this->editAltText = $this->editingImage->alt_text ?? '';
        $this->editDescription = $this->editingImage->description ?? '';
        $this->editLinkUrl = $this->editingImage->link_url ?? '';
        $this->editLinkText = $this->editingImage->link_text ?? '';
        $this->editCalameoLinkUrl = $this->editingImage->calameo_link_url ?? '';
        $this->editCalameoLinkText = $this->editingImage->calameo_link_text ?? '';
        $this->editQuantityAvailable = $this->editingImage->quantity_available;
        $this->editMaxOrderQuantity = $this->editingImage->max_order_quantity;
        $this->editPrintAvailable = (bool) $this->editingImage->print_available;
        $this->editEditionYear = $this->editingImage->edition_year;
        $this->editDisplayOrder = $this->editingImage->display_order;
        $this->editCategoryId = $this->editingImage->category_id;
        $this->editAuthorId = $this->editingImage->author_id;
        $this->editSectorId = $this->editingImage->sector_id;
        $this->editPdfFile = null;
        $this->removePdf = false;

        $this->showEditModal = true;
    }

    /**
     * Fermer le modal d'édition
     */
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingImage = null;
        $this->reset([
            'editTitle', 'editAltText', 'editDescription',
            'editLinkUrl', 'editLinkText', 'editCalameoLinkUrl', 'editCalameoLinkText',
            'editQuantityAvailable', 'editMaxOrderQuantity', 'editPrintAvailable', 'editEditionYear', 'editDisplayOrder',
            'editCategoryId', 'editAuthorId', 'editSectorId',
            'editPdfFile', 'removePdf'
        ]);
    }

    /**
     * Mettre à jour une image
     */
    public function updateImage()
    {
        if (!$this->editingImage) {
            return;
        }

        // Vérifier l'autorisation
        $this->authorize('update', $this->editingImage);

        // Validation
        $this->validate([
            'editTitle' => 'nullable|string|max:255',
            'editAltText' => 'nullable|string|max:255',
            'editDescription' => 'nullable|string|max:1000',
            'editLinkUrl' => 'nullable|url|max:500',
            'editLinkText' => 'nullable|string|max:255',
            'editCalameoLinkUrl' => 'nullable|url|max:500',
            'editCalameoLinkText' => 'nullable|string|max:255',
            'editQuantityAvailable' => 'nullable|integer|min:0',
            'editMaxOrderQuantity' => 'nullable|integer|min:0',
            'editEditionYear' => 'nullable|integer|min:1900|max:2100',
            'editDisplayOrder' => 'nullable|integer|min:0',
            'editCategoryId' => 'nullable|exists:categories,id',
            'editAuthorId' => 'nullable|exists:authors,id',
            'editSectorId' => 'nullable|exists:sectors,id',
            'editPdfFile' => 'nullable|mimes:pdf|max:51200',
        ]);

        // Gérer la mise à jour du PDF
        $pdfPath = $this->editingImage->pdf_path; // Garder l'existant par défaut

        // Supprimer le PDF si demandé
        if ($this->removePdf && $pdfPath) {
            if (Storage::disk('public')->exists($pdfPath)) {
                Storage::disk('public')->delete($pdfPath);
            }
            $pdfPath = null;
        }

        // Uploader un nouveau PDF si fourni
        if ($this->editPdfFile && !$this->removePdf) {
            // Valider le MIME type
            if (!in_array($this->editPdfFile->getMimeType(), self::ALLOWED_PDF_MIME_TYPES)) {
                session()->flash('error', 'Type de fichier PDF non autorisé.');
                return;
            }

            // Si un PDF existe déjà, écraser avec le même nom (URL stable)
            if ($this->editingImage->pdf_path) {
                $pdfFilename = basename($this->editingImage->pdf_path);
                Storage::disk('public')->putFileAs('pdfs', $this->editPdfFile, $pdfFilename);
                $pdfPath = $this->editingImage->pdf_path; // Garder le même chemin
            } else {
                // Nouveau PDF : utiliser le titre (fallback sur le nom de l'image)
                $imageSlug = Str::limit(Str::slug($this->editingImage->title) ?: Str::slug(pathinfo($this->editingImage->name, PATHINFO_FILENAME)), 100);
                if (empty($imageSlug)) {
                    $imageSlug = 'document';
                }

                // Si un fichier existe déjà avec ce nom, ajouter un suffixe numérique
                $basePdfFilename = $imageSlug . '.pdf';
                $pdfFilename = $basePdfFilename;
                $pdfCounter = 1;
                while (Storage::disk('public')->exists('pdfs/' . $pdfFilename)) {
                    $pdfFilename = $imageSlug . '-' . $pdfCounter . '.pdf';
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
            'author_id' => $this->editAuthorId ?: null,
            'sector_id' => $this->editSectorId ?: null,
            'pdf_path' => $pdfPath,
        ]);

        // Régénérer le fichier JSON
        Artisan::call('images:generate-json');

        session()->flash('success', "La brochure a été mise à jour avec succès.");
        $this->closeEditModal();
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
        Image::where('category_id', $id)->update(['category_id' => null]);
        $category->delete();
        session()->flash('success', 'Catégorie supprimée.');
    }

    /**
     * Ajouter un nouvel auteur
     */
    public function addAuthor()
    {
        $this->validate(['newAuthorName' => 'required|string|max:255|unique:authors,name']);
        Author::create(['name' => $this->newAuthorName]);
        $this->newAuthorName = '';
        session()->flash('success', 'Auteur ajouté avec succès.');
    }

    /**
     * Supprimer un auteur
     */
    public function deleteAuthor($id)
    {
        $author = Author::findOrFail($id);
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
        if ($this->selectedReport) {
            // Marquer comme lu
            if (!$this->selectedReport->is_read) {
                $this->selectedReport->markAsRead();
            }
            $this->resolutionNote = '';
            $this->showReportModal = true;
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

    public function render()
    {
        $query = Image::with(['uploader', 'category', 'author', 'sector'])
            ->orderByRaw('display_order IS NULL, display_order ASC')
            ->orderBy('created_at', 'desc');

        // Recherche
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('filename', 'like', '%' . $this->search . '%');
        }

        $imagesList = $query->paginate(12);

        $stats = [
            'total' => Image::count(),
            'total_size' => Image::sum('size'),
            'today' => Image::whereDate('created_at', today())->count(),
        ];

        // Récupérer les signalements non résolus pour les admins
        $pendingReports = BrochureReport::with(['image', 'user'])
            ->unresolved()
            ->orderBy('created_at', 'desc')
            ->get();

        $unreadReportsCount = BrochureReport::unread()->unresolved()->count();

        return view('livewire.admin.image-manager', [
            'imagesList' => $imagesList,
            'stats' => $stats,
            'categories' => Category::orderBy('name')->get(),
            'authors' => Author::orderBy('name')->get(),
            'sectors' => Sector::orderBy('name')->get(),
            'pendingReports' => $pendingReports,
            'unreadReportsCount' => $unreadReportsCount,
        ])->layout('components.layouts.app');
    }
}
