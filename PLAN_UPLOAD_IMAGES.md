# 📸 Plan d'Implémentation - Gestion des Images Admin

## 📋 Vue d'ensemble

Création d'une page admin pour uploader, gérer et supprimer des images, accessible uniquement aux **Super-admin**.

### Contraintes
- ✅ Suivre l'architecture existante (Livewire 3 + Spatie Permission)
- ✅ Respecter les conventions du projet
- ✅ Fonctionnement en environnement local (dev)
- ✅ Solution la plus simple possible
- ✅ Accès réservé aux Super-admin

---

## 🎯 Options Disponibles

### Option 1 : Upload Simple ⭐ **RECOMMANDÉ**

**Fonctionnalités** :
- Upload d'images (jpg, png, gif, webp)
- Liste des images uploadées
- Aperçu miniature
- Suppression
- Statistiques basiques

**Avantages** :
- ✅ Simple et rapide à implémenter
- ✅ Minimal (pas de surcharge)
- ✅ Parfait pour démarrer

**Fichiers à créer** : 6 fichiers
**Temps estimé** : 30-45 min

---

### Option 2 : Upload avec Catégories

**Fonctionnalités** :
- Tout de l'Option 1 +
- Catégories d'images (Hébergements, Qualifications, Général, etc.)
- Filtres par catégorie
- Tags optionnels

**Avantages** :
- ✅ Organisation des images
- ✅ Recherche facilitée

**Inconvénients** :
- ⚠️ Plus complexe
- ⚠️ Migration supplémentaire

**Fichiers à créer** : 8 fichiers
**Temps estimé** : 1h-1h30

---

### Option 3 : Galerie Complète avec Éditeur

**Fonctionnalités** :
- Tout de l'Option 2 +
- Modal de prévisualisation plein écran
- Édition des métadonnées (titre, description, alt)
- Copie du lien public
- Drag & drop upload

**Avantages** :
- ✅ Expérience utilisateur riche
- ✅ Fonctionnalités avancées

**Inconvénients** :
- ⚠️ Beaucoup plus complexe
- ⚠️ Nécessite plus de JS (Alpine/Livewire)
- ⚠️ Overkill pour un début

**Fichiers à créer** : 10+ fichiers
**Temps estimé** : 2h-3h

---

## ✨ Recommandation : Option 1 (Upload Simple)

Pour rester dans l'esprit "le plus simple possible" et respecter votre demande, l'**Option 1** est parfaite.

Vous pourrez toujours évoluer vers l'Option 2 ou 3 plus tard si nécessaire.

---

# 🚀 Plan Détaillé - Option 1 : Upload Simple

## 📂 Architecture des Fichiers

```
app/
├── Livewire/Admin/
│   └── ImageManager.php          [NOUVEAU] Composant principal
├── Models/
│   └── Image.php                 [NOUVEAU] Model Eloquent
└── Http/

database/
└── migrations/
    └── YYYY_MM_DD_HHMMSS_create_images_table.php  [NOUVEAU]

resources/views/
└── livewire/admin/
    └── image-manager.blade.php   [NOUVEAU]

routes/
└── web.php                       [MODIFIER] Ajouter route /admin/images

storage/
└── app/public/
    └── images/                   [CRÉER] Dossier stockage

public/
└── storage -> ../storage/app/public  [SYMLINK]
```

**Total : 4 nouveaux fichiers + 1 modification + 1 configuration**

---

## 🛠️ Étapes d'Implémentation

### Étape 1 : Configuration du Storage

#### Commandes à exécuter

```bash
# Créer le symlink storage (si pas déjà fait)
php artisan storage:link

# Créer le dossier images
mkdir -p storage/app/public/images
```

#### Vérifier config/filesystems.php

```php
// Devrait déjà être configuré, mais vérifier :
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
],
```

---

### Étape 2 : Migration de la Table `images`

**Fichier** : `database/migrations/YYYY_MM_DD_HHMMSS_create_images_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('name');              // Nom original du fichier
            $table->string('filename');          // Nom stocké (unique)
            $table->string('path');              // Chemin relatif storage
            $table->string('url');               // URL publique
            $table->string('mime_type')->nullable();
            $table->integer('size')->nullable(); // Taille en bytes
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Index pour performances
            $table->index('uploaded_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
```

**Commande** :
```bash
php artisan make:migration create_images_table
# Puis copier le code ci-dessus
php artisan migrate
```

---

### Étape 3 : Model Image

**Fichier** : `app/Models/Image.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'filename',
        'path',
        'url',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Relation avec l'utilisateur qui a uploadé l'image
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Supprimer le fichier physique quand le model est supprimé
     */
    protected static function booted(): void
    {
        static::deleting(function (Image $image) {
            if (Storage::disk('public')->exists($image->path)) {
                Storage::disk('public')->delete($image->path);
            }
        });
    }

    /**
     * Formater la taille du fichier
     */
    public function formattedSize(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
```

---

### Étape 4 : Composant Livewire ImageManager

**Fichier** : `app/Livewire/Admin/ImageManager.php`

```php
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
```

---

### Étape 5 : Vue Blade

**Fichier** : `resources/views/livewire/admin/image-manager.blade.php`

```blade
<div class="flex h-full w-full flex-1 flex-col gap-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Gestion des Images</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Uploadez et gérez les images de l'application
            </p>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4 border border-red-200 dark:border-red-800">
            <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Images</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                </div>
                <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-3">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Taille Totale</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        @php
                            $totalMB = $stats['total_size'] / 1048576;
                            echo number_format($totalMB, 2) . ' MB';
                        @endphp
                    </p>
                </div>
                <div class="rounded-lg bg-purple-100 dark:bg-purple-900/30 p-3">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Aujourd'hui</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['today'] }}</p>
                </div>
                <div class="rounded-lg bg-green-100 dark:bg-green-900/30 p-3">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Upload Section --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Uploader des images</h2>

        <form wire:submit.prevent="uploadImages">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Sélectionner des images (max 10 MB chacune)
                    </label>
                    <input
                        type="file"
                        wire:model="images"
                        multiple
                        accept="image/*"
                        class="block w-full text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none"
                    >
                    @error('images.*')
                        <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Preview des images sélectionnées --}}
                @if ($images)
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($images as $image)
                            <div class="relative">
                                <img src="{{ $image->temporaryUrl() }}" class="w-full h-32 object-cover rounded-lg border border-gray-300 dark:border-gray-600">
                                <span class="absolute bottom-2 left-2 bg-black bg-opacity-70 text-white text-xs px-2 py-1 rounded">
                                    Preview
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex gap-3">
                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        wire:loading.attr="disabled"
                        wire:target="images, uploadImages"
                    >
                        <span wire:loading.remove wire:target="uploadImages">Uploader</span>
                        <span wire:loading wire:target="uploadImages">Upload en cours...</span>
                    </button>

                    @if ($images)
                        <button
                            type="button"
                            wire:click="$set('images', [])"
                            class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors"
                        >
                            Annuler
                        </button>
                    @endif
                </div>

                {{-- Loading indicator --}}
                <div wire:loading wire:target="images" class="text-sm text-gray-500 dark:text-gray-400">
                    Chargement des previews...
                </div>
            </div>
        </form>
    </div>

    {{-- Search --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Rechercher une image..."
            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
    </div>

    {{-- Images Grid --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Images uploadées</h2>

        @if($imagesList->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($imagesList as $image)
                    <div class="group relative rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow">
                        {{-- Image --}}
                        <div class="aspect-square bg-gray-100 dark:bg-gray-700">
                            <img
                                src="{{ asset($image->url) }}"
                                alt="{{ $image->name }}"
                                class="w-full h-full object-cover"
                            >
                        </div>

                        {{-- Overlay on hover --}}
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-60 transition-all duration-200 flex items-center justify-center opacity-0 group-hover:opacity-100">
                            <div class="flex gap-2">
                                {{-- View button --}}
                                <a
                                    href="{{ asset($image->url) }}"
                                    target="_blank"
                                    class="p-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                                    title="Voir l'image"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>

                                {{-- Delete button --}}
                                <button
                                    wire:click="openDeleteModal({{ $image->id }})"
                                    class="p-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors"
                                    title="Supprimer"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Info --}}
                        <div class="p-3 bg-white dark:bg-gray-800">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate" title="{{ $image->name }}">
                                {{ $image->name }}
                            </p>
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $image->formattedSize() }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $image->created_at->format('d/m/Y') }}</span>
                            </div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                Par {{ $image->uploader->name }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $imagesList->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">Aucune image</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Uploadez votre première image ci-dessus</p>
            </div>
        @endif
    </div>

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal && $selectedImage)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeDeleteModal"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Confirmer la suppression
                    </h3>

                    <div class="mb-6">
                        {{-- Image preview --}}
                        <img src="{{ asset($selectedImage->url) }}" alt="{{ $selectedImage->name }}" class="w-full h-48 object-cover rounded-lg mb-4">

                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            Êtes-vous sûr de vouloir supprimer cette image ?
                        </p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $selectedImage->name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedImage->formattedSize() }}</p>
                    </div>

                    <div class="flex gap-3 justify-end">
                        <button
                            wire:click="closeDeleteModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            Annuler
                        </button>

                        <button
                            wire:click="deleteImage({{ $selectedImage->id }})"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors"
                        >
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
```

---

### Étape 6 : Ajouter la Route

**Fichier** : `routes/web.php`

```php
// À ajouter dans le groupe admin (ligne 54-56)
Route::middleware(['permission:manage-users'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', \App\Livewire\Admin\UsersList::class)->name('users');
    Route::get('/images', \App\Livewire\Admin\ImageManager::class)->name('images');  // [NOUVEAU]
});
```

---

## 🎨 Navigation (Optionnel mais Recommandé)

### Ajouter un lien dans le menu admin

**Fichier** : `resources/views/components/layouts/app.blade.php` (ou votre layout)

Trouver le menu de navigation et ajouter :

```blade
@can('manage-users')
    <a href="{{ route('admin.users') }}" class="...">
        Gestion Utilisateurs
    </a>
    <a href="{{ route('admin.images') }}" class="...">
        Gestion Images
    </a>
@endcan
```

---

## ✅ Checklist d'Implémentation

### Préparation
- [ ] Vérifier que `php artisan storage:link` est exécuté
- [ ] Créer le dossier `storage/app/public/images`
- [ ] Vérifier `config/filesystems.php`

### Base de données
- [ ] Créer la migration `create_images_table`
- [ ] Exécuter `php artisan migrate`
- [ ] Créer le model `Image.php`

### Composant Livewire
- [ ] Créer `app/Livewire/Admin/ImageManager.php`
- [ ] Créer `resources/views/livewire/admin/image-manager.blade.php`

### Routing
- [ ] Ajouter la route `/admin/images` dans `web.php`
- [ ] Tester l'accès : `http://localhost:8000/admin/images`

### Tests
- [ ] Uploader une image
- [ ] Vérifier l'image dans `storage/app/public/images`
- [ ] Vérifier l'URL `http://localhost:8000/storage/images/[filename]`
- [ ] Supprimer une image
- [ ] Tester la recherche

---

## 🧪 Commandes de Test

```bash
# 1. Configuration initiale
php artisan storage:link
mkdir -p storage/app/public/images

# 2. Migration
php artisan make:migration create_images_table
php artisan migrate

# 3. Vérifier les routes
php artisan route:list --name=admin

# 4. Tester l'accès (en tant que Super-admin)
# http://localhost:8000/admin/images

# 5. Vérifier les permissions
php artisan tinker
>>> User::find(1)->hasPermissionTo('manage-users')  // Doit retourner true pour Super-admin

# 6. Nettoyer le cache si besoin
php artisan optimize:clear
```

---

## 📝 Points d'Attention

### Sécurité
✅ **Validation stricte** : Max 10 MB, types image uniquement
✅ **Noms uniques** : `time() + uniqid()` pour éviter les collisions
✅ **Permission** : Seulement `manage-users` (Super-admin)
✅ **Suppression cascade** : User supprimé = images supprimées

### Performance
- Les images sont servies via `/storage` (symlink)
- Pagination 12 images/page
- Eager loading `with('uploader')`
- Index sur `uploaded_by` et `created_at`

### Limitations en DEV
⚠️ Stockage local (`public` disk)
⚠️ Pas de resize/optimisation automatique
⚠️ Pas de CDN

**Pour la production** :
- Utiliser S3 ou Cloudinary
- Ajouter intervention/image pour resize
- Mettre en place un CDN

---

## 🚀 Évolutions Futures Possibles

### Court terme
- Copier l'URL en un clic
- Filtres par type MIME
- Tri par taille/date

### Moyen terme
- Catégories d'images
- Tags
- Recherche avancée

### Long terme
- Éditeur d'images (crop, resize)
- Galerie publique
- API pour récupérer les images

---

## 📚 Ressources

- **Livewire File Uploads** : https://livewire.laravel.com/docs/uploads
- **Laravel Storage** : https://laravel.com/docs/12.x/filesystem
- **Spatie Permission** : https://spatie.be/docs/laravel-permission/v6

---

## 💡 Support

Si vous rencontrez des problèmes :

1. Vérifier les logs : `storage/logs/laravel.log`
2. Vérifier les permissions fichiers : `chmod -R 775 storage`
3. Nettoyer le cache : `php artisan optimize:clear`
4. Vérifier le symlink : `ls -la public/storage`

---

**Temps total estimé : 30-45 minutes**

Bonne implémentation ! 🎉

---

# 📧 Configuration des Notifications Email

## Vue d'ensemble

Le système de commande d'images est **déjà préparé** pour envoyer des emails, mais les notifications ne sont pas encore activées. Cette section explique comment configurer et activer les emails de confirmation.

---

## 🎯 Emails à implémenter

### 1. Email de confirmation client
- **Destinataire** : Client qui a passé la commande
- **Contenu** :
  - Numéro de commande
  - Résumé des informations saisies
  - Liste des images commandées avec quantités
  - Message de confirmation

### 2. Email de notification admin
- **Destinataires** : Utilisateurs désignés dans `order_notification_users`
- **Contenu** :
  - Nouvelle commande reçue
  - Informations du client
  - Lien direct vers les détails de la commande
  - Liste des images commandées

---

## ⚙️ Configuration .env

### Étape 1 : Configurer le serveur SMTP

Ajouter/modifier ces lignes dans votre fichier `.env` :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io  # ou smtp.gmail.com, smtp.sendgrid.net, etc.
MAIL_PORT=2525              # 587 pour la plupart des serveurs
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls         # ou ssl
MAIL_FROM_ADDRESS=noreply@votredomaine.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Options de services email :

**Pour le développement (recommandé) :**
- **Mailtrap** : https://mailtrap.io (gratuit, test d'emails)
- **MailHog** : Local, pas de config externe

**Pour la production :**
- **Gmail** : Facile à configurer mais limité
- **SendGrid** : Professionnel, quota gratuit généreux
- **Mailgun** : Bon pour volumes importants
- **Amazon SES** : Très fiable et économique

---

## 🔧 Étapes d'implémentation

### Étape 1 : Créer les classes Mailable

#### Email de confirmation client

```bash
php artisan make:mail OrderConfirmation --markdown=emails.orders.confirmation
```

**Fichier** : `app/Mail/OrderConfirmation.php`

```php
<?php

namespace App\Mail;

use App\Models\ImageOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ImageOrder $order
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmation de votre commande ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.confirmation',
        );
    }
}
```

**Vue** : `resources/views/emails/orders/confirmation.blade.php`

```blade
@component('mail::message')
# Commande confirmée !

Bonjour {{ $order->civility }} {{ $order->full_name }},

Votre commande **{{ $order->order_number }}** a bien été enregistrée.

## Images commandées

@foreach($order->items as $item)
- {{ $item->image->title ?? $item->image->name }} (Quantité: {{ $item->quantity }})
@endforeach

## Informations de livraison

{{ $order->full_address }}

@if($order->customer_notes)
## Vos remarques

{{ $order->customer_notes }}
@endif

Nous vous tiendrons informé de l'avancement de votre commande.

Merci,<br>
{{ config('app.name') }}
@endcomponent
```

---

#### Email de notification admin

```bash
php artisan make:notification NewOrderNotification
```

**Fichier** : `app/Notifications/NewOrderNotification.php`

```php
<?php

namespace App\Notifications;

use App\Models\ImageOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ImageOrder $order
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle commande d\'images - ' . $this->order->order_number)
            ->greeting('Nouvelle commande reçue !')
            ->line('Une nouvelle commande d\'images a été passée.')
            ->line('**Numéro** : ' . $this->order->order_number)
            ->line('**Client** : ' . $this->order->full_name)
            ->line('**Email** : ' . $this->order->email)
            ->line('**Type** : ' . ucfirst($this->order->customer_type))
            ->line('**Images** : ' . $this->order->items->count())
            ->action('Voir la commande', route('admin.orders'))
            ->line('Merci de traiter cette commande rapidement.');
    }
}
```

---

### Étape 2 : Activer l'envoi dans le code

**Fichier** : `app/Livewire/PublicImageOrderForm.php`

Décommenter et activer les lignes suivantes (lignes 228-236) :

```php
// Envoyer email de confirmation au client
Mail::to($this->email)->send(new OrderConfirmation($order));

// Notifier les admins
$notifiableUsers = OrderNotificationUser::getNotifiableUsers();
if ($notifiableUsers->isNotEmpty()) {
    Notification::send($notifiableUsers, new NewOrderNotification($order));
}
```

Ajouter les imports en haut du fichier :

```php
use App\Mail\OrderConfirmation;
use App\Notifications\NewOrderNotification;
```

---

### Étape 3 : Gérer les utilisateurs à notifier

Pour permettre aux admins de choisir qui reçoit les notifications, créer une page de configuration.

#### Option 1 : Via Tinker (rapide pour tester)

```bash
php artisan tinker

# Ajouter un utilisateur aux notifications
App\Models\OrderNotificationUser::create(['user_id' => 1]);

# Lister les utilisateurs notifiés
App\Models\OrderNotificationUser::with('user')->get();

# Retirer un utilisateur
App\Models\OrderNotificationUser::where('user_id', 1)->delete();
```

#### Option 2 : Créer une interface admin (recommandé)

Créer un composant Livewire pour gérer les notifications :

```bash
php artisan make:livewire Admin/NotificationSettings
```

Ajouter une page dans l'admin pour sélectionner les utilisateurs qui doivent recevoir les notifications de nouvelles commandes.

---

## 📋 Configuration par service

### Gmail (Développement/Production limitée)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre.email@gmail.com
MAIL_PASSWORD=votre_mot_de_passe_application  # Pas votre mot de passe Gmail !
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=votre.email@gmail.com
MAIL_FROM_NAME="Nom de votre app"
```

⚠️ **Important Gmail** : Utilisez un "Mot de passe d'application", pas votre mot de passe Gmail normal.
1. Allez dans Paramètres Google > Sécurité > Validation en deux étapes
2. Créez un mot de passe d'application
3. Utilisez ce mot de passe dans `.env`

---

### Mailtrap (Développement - Recommandé)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=votre_username_mailtrap
MAIL_PASSWORD=votre_password_mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=test@example.com
MAIL_FROM_NAME="Dispo App"
```

Créez un compte sur https://mailtrap.io et récupérez vos identifiants.

---

### SendGrid (Production)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=votre_api_key_sendgrid
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votredomaine.com
MAIL_FROM_NAME="Dispo App"
```

---

## 🧪 Tester l'envoi d'emails

### Commande de test

```bash
php artisan tinker
```

```php
// Tester l'envoi d'un email de confirmation
$order = App\Models\ImageOrder::first();
Mail::to('test@example.com')->send(new App\Mail\OrderConfirmation($order));
```

### Vérifier la configuration

```bash
# Vérifier la config mail
php artisan config:clear
php artisan config:cache

# Tester avec un email simple
php artisan tinker
Mail::raw('Test email', function($msg) {
    $msg->to('test@example.com')->subject('Test');
});
```

---

## 🚀 Utilisation des queues (Optionnel mais recommandé)

Pour ne pas ralentir le formulaire lors de l'envoi, utiliser les queues Laravel :

### Étape 1 : Configuration

```env
QUEUE_CONNECTION=database
```

### Étape 2 : Créer la table des jobs

```bash
php artisan queue:table
php artisan migrate
```

### Étape 3 : Modifier les Mailables

Ajouter `implements ShouldQueue` :

```php
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderConfirmation extends Mailable implements ShouldQueue
{
    // ...
}
```

### Étape 4 : Lancer le worker

```bash
php artisan queue:work
```

En production, utilisez Supervisor pour gérer le worker automatiquement.

---

## 📊 Checklist d'activation des emails

- [ ] Configurer `.env` avec les paramètres SMTP
- [ ] Créer `OrderConfirmation` Mailable
- [ ] Créer `NewOrderNotification` Notification
- [ ] Créer les vues emails (Blade)
- [ ] Décommenter les lignes d'envoi dans `PublicImageOrderForm.php`
- [ ] Ajouter les imports nécessaires
- [ ] Ajouter au moins un utilisateur dans `order_notification_users`
- [ ] Tester l'envoi en local avec Mailtrap
- [ ] (Optionnel) Configurer les queues pour les performances

---

## 🔍 Dépannage

### Les emails ne partent pas

1. Vérifier la configuration `.env`
2. Nettoyer le cache : `php artisan config:clear`
3. Vérifier les logs : `storage/logs/laravel.log`
4. Tester la connexion SMTP manuellement

### Les emails vont dans les spams

1. Configurer SPF, DKIM, DMARC sur votre domaine
2. Utiliser un service professionnel (SendGrid, Mailgun)
3. Réchauffer votre IP si vous utilisez un serveur dédié

### Erreur "Connection refused"

- Vérifier que le port n'est pas bloqué par un firewall
- Vérifier les identifiants SMTP
- Essayer un autre port (587, 465, 2525)

---

## 📚 Ressources

- [Laravel Mail Documentation](https://laravel.com/docs/11.x/mail)
- [Laravel Notifications](https://laravel.com/docs/11.x/notifications)
- [Laravel Queues](https://laravel.com/docs/11.x/queues)
- [Mailtrap](https://mailtrap.io)
- [SendGrid](https://sendgrid.com)

---

**Note** : Les emails sont actuellement désactivés pour ne pas bloquer le développement. Suivez ce guide pour les activer quand vous serez prêt.
