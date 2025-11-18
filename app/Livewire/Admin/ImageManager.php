<?php

namespace App\Livewire\Admin;

use App\Models\Image;
use Illuminate\Support\Facades\Storage;
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
        $this->validate();

        if (empty($this->images)) {
            session()->flash('error', 'Veuillez sélectionner au moins une image.');
            return;
        }

        $uploadedCount = 0;

        foreach ($this->images as $image) {
            try {
                // Générer un nom unique
                $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

                // Stocker dans storage/app/public/images
                $path = $image->storeAs('images', $filename, 'public');

                // Créer l'entrée en base
                Image::create([
                    'name' => $image->getClientOriginalName(),
                    'filename' => $filename,
                    'path' => $path,
                    'url' => Storage::disk('public')->url($path),
                    'mime_type' => $image->getMimeType(),
                    'size' => $image->getSize(),
                    'uploaded_by' => auth()->id(),
                ]);

                $uploadedCount++;
            } catch (\Exception $e) {
                session()->flash('error', 'Erreur lors de l\'upload : ' . $e->getMessage());
                return;
            }
        }

        session()->flash('success', "{$uploadedCount} image(s) uploadée(s) avec succès.");
        $this->reset('images');
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

        // Le fichier physique sera supprimé par le model event
        $image->delete();

        session()->flash('success', "L'image {$image->name} a été supprimée.");
        $this->closeDeleteModal();
    }

    public function render()
    {
        $query = Image::with('uploader')->latest();

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
