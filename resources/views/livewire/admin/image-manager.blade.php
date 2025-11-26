<div class="min-h-screen bg-gray-50 dark:bg-zinc-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Gestion des Images</h1>
            <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
                Uploadez et gérez les images de l'application
            </p>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-6 rounded-lg bg-gradient-to-r from-green-500 to-green-600 p-6 shadow-lg">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="flex-1 text-white font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-lg bg-gradient-to-r from-red-500 to-red-600 p-6 shadow-lg">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                    <p class="flex-1 text-white font-medium">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        {{-- Statistics Cards --}}
        <div class="grid gap-6 md:grid-cols-3 mb-6">
            <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Images</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                    </div>
                    <div class="rounded-lg bg-[#3E9B90]/10 dark:bg-[#3E9B90]/20 p-3">
                        <svg class="w-6 h-6 text-[#3E9B90]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Taille Totale</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            @php
                                $totalMB = $stats['total_size'] / 1048576;
                                echo number_format($totalMB, 2) . ' MB';
                            @endphp
                        </p>
                    </div>
                    <div class="rounded-lg bg-purple-100 dark:bg-purple-900/30 p-3">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aujourd'hui</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['today'] }}</p>
                    </div>
                    <div class="rounded-lg bg-green-100 dark:bg-green-900/30 p-3">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Upload Section --}}
        <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-8 mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Uploader des images</h2>

            <form wire:submit.prevent="uploadImages">
                <div class="space-y-4">
                    <div>
                        <label class="block text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            Sélectionner des images (max 10 MB chacune)
                        </label>
                        <input type="file" wire:model="images" multiple accept="image/*"
                            class="block w-full text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none px-4 py-3">
                        @error('images.*')
                            <span class="text-sm text-red-600 dark:text-red-400 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Preview des images sélectionnées --}}
                    @if ($images)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($images as $index => $image)
                                <div
                                    class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 bg-white dark:bg-gray-800">
                                    <div class="relative mb-3">
                                        <img src="{{ $image->temporaryUrl() }}"
                                            class="w-full h-32 object-cover rounded-lg">
                                        <span
                                            class="absolute top-2 left-2 bg-black bg-opacity-70 text-white text-xs px-2 py-1 rounded">
                                            Image {{ $index + 1 }}
                                        </span>
                                    </div>
                                    <div class="space-y-2">
                                        <div>
                                            <label
                                                class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Titre
                                            </label>
                                            <input type="text" wire:model="titles.{{ $index }}"
                                                placeholder="Titre de l'image"
                                                class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Texte alternatif (optionnel)
                                            </label>
                                            <input type="text" wire:model="altTexts.{{ $index }}"
                                                placeholder="Description courte pour l'accessibilité"
                                                class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Description (optionnel)
                                            </label>
                                            <textarea wire:model="descriptions.{{ $index }}" rows="2" placeholder="Description détaillée de l'image"
                                                class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent"></textarea>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label
                                                    class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Texte du lien (optionnel)
                                                </label>
                                                <input type="text" wire:model="linkTexts.{{ $index }}"
                                                    placeholder="Ex: Voir la collection"
                                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    URL du lien (optionnel)
                                                </label>
                                                <input type="url" wire:model="linkUrls.{{ $index }}"
                                                    placeholder="https://..."
                                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label
                                                    class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Texte du lien Calameo (optionnel)
                                                </label>
                                                <input type="text" wire:model="calameoLinkTexts.{{ $index }}"
                                                    placeholder="Ex: Voir sur Calameo"
                                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    URL du lien Calameo (optionnel)
                                                </label>
                                                <input type="url" wire:model="calameoLinkUrls.{{ $index }}"
                                                    placeholder="https://www.calameo.com/..."
                                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label
                                                    class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Quantité disponible
                                                </label>
                                                <input type="number"
                                                    wire:model="quantitiesAvailable.{{ $index }}" min="0"
                                                    placeholder="0"
                                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Qté max commande
                                                </label>
                                                <input type="number"
                                                    wire:model="maxOrderQuantities.{{ $index }}"
                                                    min="0" placeholder="0"
                                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label
                                                    class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Année d'édition
                                                </label>
                                                <input type="number" wire:model="editionYears.{{ $index }}"
                                                    min="1900" max="2100" placeholder="2025"
                                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                            </div>
                                            <div class="flex items-end">
                                                <label
                                                    class="flex items-center gap-2 text-xs font-medium text-gray-700 dark:text-gray-300">
                                                    <input type="checkbox"
                                                        wire:model="printAvailables.{{ $index }}"
                                                        class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-[#3E9B90] focus:ring-2 focus:ring-[#3E9B90]">
                                                    Print disponible
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex gap-3">
                        <button type="submit"
                            class="px-8 py-3 bg-[#3E9B90] text-white text-lg font-semibold rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-md disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                            wire:loading.attr="disabled" wire:target="images, uploadImages">
                            <span wire:loading.remove wire:target="uploadImages">Uploader</span>
                            <span wire:loading wire:target="uploadImages">Upload en cours...</span>
                        </button>

                        @if ($images)
                            <button type="button" wire:click="$set('images', [])"
                                class="px-8 py-3 bg-gray-500 hover:bg-gray-600 text-white text-lg font-semibold rounded-lg transition-colors shadow-md">
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
        <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-6 mb-6">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher une image..."
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
        </div>

        {{-- Images Grid --}}
        <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Images uploadées</h2>

            @if ($imagesList->count() > 0)
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach ($imagesList as $image)
                        <div
                            class="group relative rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow bg-white dark:bg-transparent">
                            {{-- Image --}}
                            <div class="aspect-square bg-gray-100 dark:bg-gray-700">
                                <img src="{{ $image->thumbnail_path ? asset('storage/' . $image->thumbnail_path) : asset('storage/' . $image->path) }}"
                                    alt="{{ $image->alt_text ?? $image->name }}" class="w-full h-full object-cover">
                            </div>

                            {{-- Overlay on hover --}}
                            <div
                                class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-60 transition-all duration-200 flex items-center justify-center opacity-0 group-hover:opacity-100">
                                <div class="flex gap-2">
                                    {{-- View button --}}
                                    <a href="{{ asset('storage/' . $image->path) }}" target="_blank"
                                        class="p-2 bg-[#3E9B90] hover:bg-[#2d7a72] text-white rounded-lg transition-colors"
                                        title="Voir l'image">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                            </path>
                                        </svg>
                                    </a>

                                    {{-- Delete button --}}
                                    <button wire:click="openDeleteModal({{ $image->id }})"
                                        class="p-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors"
                                        title="Supprimer">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Info --}}
                            <div class="p-3 bg-white dark:bg-gray-800">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate"
                                    title="{{ $image->name }}">
                                    {{ $image->name }}
                                </p>
                                <div class="flex justify-between items-center mt-1">
                                    <span
                                        class="text-xs text-gray-500 dark:text-gray-400">{{ $image->formattedSize() }}</span>
                                    <span
                                        class="text-xs text-gray-500 dark:text-gray-400">{{ $image->created_at->format('d/m/Y') }}</span>
                                </div>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    Par {{ $image->uploader->name }}
                                </p>
                                @if ($image->link_url)
                                    <a href="{{ $image->link_url }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1 text-xs text-[#3E9B90] hover:text-[#2d7a72] mt-2 font-medium">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14">
                                            </path>
                                        </svg>
                                        {{ $image->link_text ?? 'Voir le lien' }}
                                    </a>
                                @endif
                                @if ($image->calameo_link_url)
                                    <a href="{{ $image->calameo_link_url }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1 text-xs text-orange-500 hover:text-orange-600 mt-1 font-medium">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                                            </path>
                                        </svg>
                                        {{ $image->calameo_link_text ?? 'Voir sur Calameo' }}
                                    </a>
                                @endif
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
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                    <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">Aucune image</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Uploadez votre première image ci-dessus</p>
                </div>
            @endif
        </div>

        {{-- Delete Confirmation Modal --}}
        @if ($showDeleteModal && $selectedImage)
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center px-4">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                        wire:click="closeDeleteModal"></div>

                    <div class="relative bg-white dark:bg-[#001716] rounded-lg shadow-xl max-w-md w-full p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            Confirmer la suppression
                        </h3>

                        <div class="mb-6">
                            {{-- Image preview --}}
                            <img src="{{ asset('storage/' . $selectedImage->path) }}"
                                alt="{{ $selectedImage->alt_text ?? $selectedImage->name }}"
                                class="w-full h-48 object-cover rounded-lg mb-4">

                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                Êtes-vous sûr de vouloir supprimer cette image ?
                            </p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $selectedImage->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedImage->formattedSize() }}
                            </p>
                        </div>

                        <div class="flex gap-3 justify-end">
                            <button wire:click="closeDeleteModal"
                                class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                Annuler
                            </button>

                            <button wire:click="deleteImage({{ $selectedImage->id }})"
                                class="px-6 py-3 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors shadow-md">
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
