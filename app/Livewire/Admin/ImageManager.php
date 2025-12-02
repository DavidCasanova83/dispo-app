<?php

namespace App\Livewire\Admin;

use App\Models\Image;
use Illuminate\Support\Facades\Artisan;
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

    // Whitelist des MIME types autorisés
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    // Validation des uploads
    protected $rules = [
        'images.*' => 'image|max:10240', // 10MB max par image
    ];

    protected $messages = [
        'images.*.image' => 'Le fichier doit être une image.',
        'images.*.max' => 'L\'image ne doit pas dépasser 10 MB.',
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

                // Générer un nom unique sécurisé
                $filename = $originalName . '_' . time() . '_' . Str::random(10) . '.' . $extension;

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

                // Créer l'entrée en base
                Image::create([
                    'name' => $image->getClientOriginalName(),
                    'title' => $this->titles[$index] ?? null,
                    'filename' => $filename,
                    'path' => $path,
                    'thumbnail_path' => $thumbnailPath,
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

        $this->reset(['images', 'titles', 'altTexts', 'descriptions', 'linkUrls', 'linkTexts', 'calameoLinkUrls', 'calameoLinkTexts', 'quantitiesAvailable', 'maxOrderQuantities', 'printAvailables', 'editionYears', 'displayOrders']);
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
            'editQuantityAvailable', 'editMaxOrderQuantity', 'editPrintAvailable', 'editEditionYear', 'editDisplayOrder'
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
        ]);

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
        ]);

        // Régénérer le fichier JSON
        Artisan::call('images:generate-json');

        session()->flash('success', "La brochure a été mise à jour avec succès.");
        $this->closeEditModal();
    }

    public function render()
    {
        $query = Image::with('uploader')
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

        return view('livewire.admin.image-manager', [
            'imagesList' => $imagesList,
            'stats' => $stats,
        ])->layout('components.layouts.app');
    }
}
