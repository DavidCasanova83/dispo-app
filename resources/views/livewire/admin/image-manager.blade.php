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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($images as $index => $image)
                            <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-3">
                                <div class="relative mb-3">
                                    <img src="{{ $image->temporaryUrl() }}" class="w-full h-32 object-cover rounded-lg">
                                    <span class="absolute top-2 left-2 bg-black bg-opacity-70 text-white text-xs px-2 py-1 rounded">
                                        Image {{ $index + 1 }}
                                    </span>
                                </div>
                                <div class="space-y-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Texte alternatif (optionnel)
                                        </label>
                                        <input
                                            type="text"
                                            wire:model="altTexts.{{ $index }}"
                                            placeholder="Description courte pour l'accessibilité"
                                            class="w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Description (optionnel)
                                        </label>
                                        <textarea
                                            wire:model="descriptions.{{ $index }}"
                                            rows="2"
                                            placeholder="Description détaillée de l'image"
                                            class="w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"
                                        ></textarea>
                                    </div>
                                </div>
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
                                src="{{ $image->thumbnail_path ? Storage::url($image->thumbnail_path) : $image->url }}"
                                alt="{{ $image->alt_text ?? $image->name }}"
                                class="w-full h-full object-cover"
                            >
                        </div>

                        {{-- Overlay on hover --}}
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-60 transition-all duration-200 flex items-center justify-center opacity-0 group-hover:opacity-100">
                            <div class="flex gap-2">
                                {{-- View button --}}
                                <a
                                    href="{{ $image->url }}"
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
                        <img src="{{ $selectedImage->url }}" alt="{{ $selectedImage->alt_text ?? $selectedImage->name }}" class="w-full h-48 object-cover rounded-lg mb-4">

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
