<div class="min-h-screen bg-gray-50 dark:bg-zinc-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Gestion des Brochures</h1>
            <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
                Uploadez et gérez les brochures de l'application
            </p>
            <a href="{{ route('admin.images.statistics') }}" wire:navigate
                class="inline-flex items-center gap-2 mt-4 px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-semibold rounded-lg shadow-md transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Voir les statistiques
            </a>
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

        {{-- Bannière des signalements --}}
        @if ($pendingReports->count() > 0)
            <div class="mb-6 rounded-lg bg-gradient-to-r from-amber-500 to-orange-500 p-6 shadow-lg">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-lg font-bold text-white">
                                {{ $pendingReports->count() }} signalement(s) en attente
                            </h3>
                            @if ($unreadReportsCount > 0)
                                <span class="px-2 py-1 bg-red-600 text-white text-xs font-bold rounded-full">
                                    {{ $unreadReportsCount }} non lu(s)
                                </span>
                            @endif
                        </div>
                        <div class="space-y-2">
                            @foreach ($pendingReports->take(5) as $report)
                                @if ($report->image)
                                    <button wire:click="openReportModal({{ $report->id }})"
                                        class="flex items-center gap-3 w-full text-left p-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors">
                                        @if (!$report->is_read)
                                            <span class="w-2 h-2 bg-red-400 rounded-full flex-shrink-0"></span>
                                        @else
                                            <span class="w-2 h-2 bg-white/30 rounded-full flex-shrink-0"></span>
                                        @endif
                                        <img src="{{ $report->image->thumbnail_path ? asset('storage/' . $report->image->thumbnail_path) : asset('storage/' . $report->image->path) }}"
                                            class="w-8 h-10 object-cover rounded flex-shrink-0" alt="">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-white truncate">
                                                {{ $report->image->title ?? $report->image->name }}
                                            </p>
                                            <p class="text-xs text-white/70 truncate">
                                                Par {{ $report->user->name }} - {{ $report->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </button>
                                @endif
                            @endforeach
                            @if ($pendingReports->count() > 5)
                                <p class="text-sm text-white/70 text-center pt-2">
                                    Et {{ $pendingReports->count() - 5 }} autre(s) signalement(s)...
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Statistics Cards --}}
        <div class="grid gap-6 md:grid-cols-3 mb-6">
            <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Brochures</p>
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
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Uploader des brochures</h2>

            <form wire:submit.prevent="uploadImages">
                <div class="space-y-4">
                    <div>
                        <label class="block text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            Contenu de la brochure (PDF ou image) <span class="text-red-500">*</span>
                        </label>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                            Sélectionnez un ou plusieurs fichiers PDF ou images (max 50 MB chacun)
                        </p>
                        <input type="file" wire:model="contentFiles" multiple accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                            class="block w-full text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none px-4 py-3">
                        @error('contentFiles.*')
                            <span class="text-sm text-red-600 dark:text-red-400 mt-1 block">{{ $message }}</span>
                        @enderror
                        <div wire:loading wire:target="contentFiles" class="text-sm text-gray-500 mt-2">
                            Chargement des fichiers...
                        </div>
                    </div>

                    {{-- Preview des fichiers sélectionnés --}}
                    @if ($contentFiles)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($contentFiles as $index => $contentFile)
                                @php
                                    $isPdf = strtolower($contentFile->getClientOriginalExtension()) === 'pdf';
                                @endphp
                                <div
                                    class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 bg-white dark:bg-gray-800">
                                    <div class="relative mb-3">
                                        @if ($isPdf)
                                            {{-- Affichage PDF --}}
                                            <div class="w-full h-32 bg-red-50 dark:bg-red-900/20 rounded-lg flex flex-col items-center justify-center">
                                                <svg class="w-12 h-12 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                </svg>
                                                <span class="text-xs text-red-600 dark:text-red-400 mt-1 font-medium">PDF</span>
                                            </div>
                                        @else
                                            {{-- Affichage Image --}}
                                            <img src="{{ $contentFile->temporaryUrl() }}"
                                                class="w-full h-32 object-cover rounded-lg">
                                        @endif
                                        <span
                                            class="absolute top-2 left-2 bg-black bg-opacity-70 text-white text-xs px-2 py-1 rounded">
                                            Brochure {{ $index + 1 }}
                                        </span>
                                        <span class="absolute top-2 right-2 {{ $isPdf ? 'bg-red-600' : 'bg-blue-600' }} text-white text-xs px-2 py-1 rounded">
                                            {{ strtoupper($contentFile->getClientOriginalExtension()) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 truncate" title="{{ $contentFile->getClientOriginalName() }}">
                                        {{ $contentFile->getClientOriginalName() }}
                                    </p>
                                    <div class="space-y-2">
                                        <div>
                                            <label
                                                class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Titre
                                            </label>
                                            <input type="text" wire:model="titles.{{ $index }}"
                                                placeholder="Titre de la brochure"
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
                                            <textarea wire:model="descriptions.{{ $index }}" rows="2" placeholder="Description détaillée de la brochure"
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
                                        <div class="grid grid-cols-3 gap-2">
                                            <div>
                                                <label
                                                    class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Année d'édition
                                                </label>
                                                <input type="number" wire:model="editionYears.{{ $index }}"
                                                    min="1900" max="2100" placeholder="2025"
                                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Ordre d'affichage
                                                </label>
                                                <input type="number" wire:model="displayOrders.{{ $index }}"
                                                    min="0" placeholder="Ex: 1, 2, 3..."
                                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                                @if(count($usedDisplayOrders) > 0)
                                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                        Ordres utilisés : {{ implode(', ', $usedDisplayOrders) }}
                                                    </p>
                                                @endif
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
                                        {{-- Catégorie, Auteur, Secteur --}}
                                        <div class="grid grid-cols-3 gap-2">
                                            <div>
                                                <label
                                                    class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Catégorie
                                                </label>
                                                <select wire:model="categoryIds.{{ $index }}"
                                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                                    <option value="">-- Aucune --</option>
                                                    @foreach ($categories as $category)
                                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Auteur
                                                </label>
                                                <select wire:model="authorIds.{{ $index }}"
                                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                                    <option value="">-- Aucun --</option>
                                                    @foreach ($authors as $author)
                                                        <option value="{{ $author->id }}">{{ $author->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Secteur
                                                </label>
                                                <select wire:model="sectorIds.{{ $index }}"
                                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                                    <option value="">-- Aucun --</option>
                                                    @foreach ($sectors as $sector)
                                                        <option value="{{ $sector->id }}">{{ $sector->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        {{-- Responsable --}}
                                        <div>
                                            <label
                                                class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Responsable
                                            </label>
                                            <select wire:model="responsableIds.{{ $index }}"
                                                class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                                <option value="">-- Aucun --</option>
                                                @foreach ($responsables as $responsable)
                                                    <option value="{{ $responsable->id }}">{{ $responsable->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        {{-- Image de présentation --}}
                                        <div class="border-t border-gray-200 dark:border-gray-700 pt-3 mt-3">
                                            <label
                                                class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Image de présentation (max 10MB)
                                                @if ($isPdf)
                                                    <span class="text-red-500">*</span>
                                                @endif
                                            </label>

                                            {{-- Checkbox pour utiliser l'image par défaut (uniquement pour PDF) --}}
                                            @if ($isPdf)
                                                @php
                                                    $defaultImageUrl = $this->getActiveDefaultImageUrl($index);
                                                    $selectedAuthor = isset($authorIds[$index]) && $authorIds[$index] ? $authors->find($authorIds[$index]) : null;
                                                    $hasAuthorDefault = $selectedAuthor && $selectedAuthor->hasDefaultImage();
                                                @endphp
                                                <div class="mb-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                                    <label class="flex items-center gap-2 cursor-pointer">
                                                        <input type="checkbox"
                                                            wire:model.live="useDefaultImages.{{ $index }}"
                                                            class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-[#3E9B90] focus:ring-2 focus:ring-[#3E9B90]"
                                                            {{ !$defaultImageUrl ? 'disabled' : '' }}>
                                                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                            Utiliser l'image par défaut
                                                        </span>
                                                    </label>
                                                    @if ($defaultImageUrl)
                                                        <div class="mt-2 flex items-center gap-3">
                                                            <img src="{{ $defaultImageUrl }}" class="w-16 h-20 object-cover rounded shadow">
                                                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                                                @if ($hasAuthorDefault)
                                                                    <span class="text-blue-600 dark:text-blue-400 font-medium">Image de l'auteur : {{ $selectedAuthor->name }}</span>
                                                                @else
                                                                    <span class="text-gray-500">Image par défaut globale</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @else
                                                        <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                                            Aucune image par défaut configurée.
                                                            <button type="button" wire:click="toggleDefaultImageConfig" class="underline">Configurer</button>
                                                        </p>
                                                    @endif
                                                </div>
                                            @endif

                                            {{-- Champ d'upload (caché si checkbox cochée) --}}
                                            @if (!($isPdf && isset($useDefaultImages[$index]) && $useDefaultImages[$index]))
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                                    @if ($isPdf)
                                                        <span class="text-red-600 dark:text-red-400 font-medium">
                                                            Obligatoire pour les fichiers PDF (sauf si image par défaut)
                                                        </span>
                                                    @else
                                                        Optionnel - Si non fournie, l'image de contenu sera utilisée
                                                    @endif
                                                </p>
                                                <input type="file" wire:model="presentationImages.{{ $index }}"
                                                    accept="image/*"
                                                    class="w-full text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none px-3 py-2">
                                                @error('presentationImages.' . $index)
                                                    <span
                                                        class="text-xs text-red-600 dark:text-red-400 mt-1 block">{{ $message }}</span>
                                                @enderror
                                                <div wire:loading wire:target="presentationImages.{{ $index }}"
                                                    class="text-xs text-gray-500 mt-1">
                                                    Chargement de l'image...
                                                </div>
                                            @endif
                                            @if (isset($presentationImages[$index]) && $presentationImages[$index] && !($isPdf && isset($useDefaultImages[$index]) && $useDefaultImages[$index]))
                                                <div class="mt-2 flex items-center gap-2">
                                                    <img src="{{ $presentationImages[$index]->temporaryUrl() }}" class="w-12 h-12 object-cover rounded">
                                                    <p class="text-xs text-green-600 dark:text-green-400">
                                                        {{ $presentationImages[$index]->getClientOriginalName() }}
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex gap-3">
                        <button type="submit"
                            class="px-8 py-3 bg-[#3E9B90] text-white text-lg font-semibold rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-md disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                            wire:loading.attr="disabled" wire:target="contentFiles, presentationImages, uploadImages">
                            <span wire:loading.remove wire:target="uploadImages">Uploader</span>
                            <span wire:loading wire:target="uploadImages">Upload en cours...</span>
                        </button>

                        @if ($contentFiles)
                            <button type="button" wire:click="$set('contentFiles', [])"
                                class="px-8 py-3 bg-gray-500 hover:bg-gray-600 text-white text-lg font-semibold rounded-lg transition-colors shadow-md">
                                Annuler
                            </button>
                        @endif
                    </div>

                    {{-- Loading indicator --}}
                    <div wire:loading wire:target="contentFiles" class="text-sm text-gray-500 dark:text-gray-400">
                        Chargement des previews...
                    </div>
                </div>
            </form>
        </div>

        {{-- Configuration des images par défaut --}}
        <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-6 mb-6">
            <button wire:click="toggleDefaultImageConfig" class="w-full flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    <span class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Images par défaut
                    </span>
                </h2>
                <svg class="w-5 h-5 text-gray-500 transition-transform {{ $showDefaultImageConfig ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            @if ($showDefaultImageConfig)
                <div class="mt-4 space-y-4">
                    {{-- Image par défaut globale --}}
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Image par défaut globale</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                            Cette image sera utilisée si aucune image par défaut n'est définie pour l'auteur sélectionné.
                        </p>

                        @if ($globalDefaultImagePath)
                            <div class="flex items-center gap-4 mb-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <img src="{{ asset('storage/' . $globalDefaultImagePath) }}" class="w-20 h-24 object-cover rounded shadow">
                                <div class="flex-1">
                                    <p class="text-sm text-green-600 dark:text-green-400 font-medium">Image configurée</p>
                                    <button wire:click="deleteGlobalDefaultImage" wire:confirm="Supprimer l'image par défaut globale ?"
                                        class="mt-2 text-xs text-red-600 hover:text-red-700 underline">
                                        Supprimer
                                    </button>
                                </div>
                            </div>
                        @endif

                        <div class="flex gap-2">
                            <input type="file" wire:model="globalDefaultImage" accept="image/*"
                                class="flex-1 text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none px-3 py-2">
                            <button wire:click="uploadGlobalDefaultImage"
                                wire:loading.attr="disabled"
                                wire:target="globalDefaultImage, uploadGlobalDefaultImage"
                                class="px-4 py-2 bg-[#3E9B90] hover:bg-[#2d7a72] text-white text-sm rounded-lg transition-colors disabled:opacity-50">
                                <span wire:loading.remove wire:target="uploadGlobalDefaultImage">Enregistrer</span>
                                <span wire:loading wire:target="uploadGlobalDefaultImage">...</span>
                            </button>
                        </div>
                        <div wire:loading wire:target="globalDefaultImage" class="text-xs text-gray-500 mt-1">
                            Chargement...
                        </div>
                        @error('globalDefaultImage') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Info sur les images par auteur --}}
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>Astuce :</strong> Vous pouvez également définir une image par défaut pour chaque auteur dans la section "Auteurs" ci-dessous.
                            L'image de l'auteur sera prioritaire sur l'image globale.
                        </p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Gestion des Catégories, Auteurs, Secteurs --}}
        <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Gérer les listes</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Catégories --}}
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Catégories</h3>
                    <div class="space-y-2 mb-3 max-h-40 overflow-y-auto">
                        @forelse ($categories as $category)
                            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800 px-3 py-2 rounded">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $category->name }}</span>
                                <button wire:click="deleteCategory({{ $category->id }})"
                                    wire:confirm="Supprimer cette catégorie ?"
                                    class="text-red-500 hover:text-red-700 p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">Aucune catégorie</p>
                        @endforelse
                    </div>
                    <div class="flex gap-2">
                        <input type="text" wire:model="newCategoryName" placeholder="Nouvelle catégorie"
                            class="flex-1 px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                        <button wire:click="addCategory"
                            class="px-3 py-2 bg-[#3E9B90] hover:bg-[#2d7a72] text-white text-sm rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </button>
                    </div>
                    @error('newCategoryName') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Auteurs --}}
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Auteurs</h3>
                    <div class="space-y-2 mb-3 max-h-80 overflow-y-auto">
                        @forelse ($authors as $author)
                            <div class="bg-gray-50 dark:bg-gray-800 px-3 py-2 rounded">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        @if ($author->hasDefaultImage())
                                            <img src="{{ $author->getDefaultImageUrl() }}" class="w-8 h-10 object-cover rounded" alt="">
                                        @else
                                            <div class="w-8 h-10 bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        @endif
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $author->name }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        {{-- Bouton éditer image par défaut --}}
                                        <button wire:click="toggleEditAuthorDefaultImage({{ $author->id }})"
                                            class="{{ $editingAuthorId === $author->id ? 'text-blue-600' : 'text-blue-400 hover:text-blue-600' }} p-1"
                                            title="{{ $author->hasDefaultImage() ? 'Modifier l\'image par défaut' : 'Ajouter une image par défaut' }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                        @if ($author->hasDefaultImage())
                                            <button wire:click="deleteAuthorDefaultImage({{ $author->id }})"
                                                wire:confirm="Supprimer l'image par défaut de cet auteur ?"
                                                class="text-amber-500 hover:text-amber-700 p-1" title="Supprimer l'image par défaut">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        @endif
                                        <button wire:click="deleteAuthor({{ $author->id }})"
                                            wire:confirm="Supprimer cet auteur ?"
                                            class="text-red-500 hover:text-red-700 p-1" title="Supprimer l'auteur">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Formulaire d'édition de l'image par défaut --}}
                                @if ($editingAuthorId === $author->id)
                                    <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                        <div class="flex gap-2 items-end">
                                            <div class="flex-1">
                                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                    {{ $author->hasDefaultImage() ? 'Remplacer l\'image par défaut' : 'Ajouter une image par défaut' }}
                                                </label>
                                                <input type="file" wire:model="editAuthorDefaultImage" accept="image/*"
                                                    class="w-full text-xs text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-white dark:bg-gray-700 focus:outline-none px-2 py-1">
                                            </div>
                                            <button wire:click="updateAuthorDefaultImage({{ $author->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="editAuthorDefaultImage, updateAuthorDefaultImage"
                                                class="px-3 py-1.5 bg-[#3E9B90] hover:bg-[#2d7a72] text-white text-xs rounded-lg transition-colors disabled:opacity-50">
                                                <span wire:loading.remove wire:target="updateAuthorDefaultImage">OK</span>
                                                <span wire:loading wire:target="updateAuthorDefaultImage">...</span>
                                            </button>
                                        </div>
                                        <div wire:loading wire:target="editAuthorDefaultImage" class="text-xs text-gray-500 mt-1">Chargement...</div>
                                        @error('editAuthorDefaultImage') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">Aucun auteur</p>
                        @endforelse
                    </div>
                    {{-- Formulaire nouvel auteur --}}
                    <div class="space-y-2">
                        <div class="flex gap-2">
                            <input type="text" wire:model="newAuthorName" placeholder="Nouvel auteur"
                                class="flex-1 px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                            <button wire:click="addAuthor"
                                class="px-3 py-2 bg-[#3E9B90] hover:bg-[#2d7a72] text-white text-sm rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </button>
                        </div>
                        @error('newAuthorName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror

                        {{-- Image par défaut pour le nouvel auteur --}}
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Image par défaut (optionnel)</label>
                            <input type="file" wire:model="newAuthorDefaultImage" accept="image/*"
                                class="w-full text-xs text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none px-2 py-1">
                            <div wire:loading wire:target="newAuthorDefaultImage" class="text-xs text-gray-500 mt-1">Chargement...</div>
                            @error('newAuthorDefaultImage') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- Secteurs --}}
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Secteurs</h3>
                    <div class="space-y-2 mb-3 max-h-40 overflow-y-auto">
                        @forelse ($sectors as $sector)
                            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800 px-3 py-2 rounded">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $sector->name }}</span>
                                <button wire:click="deleteSector({{ $sector->id }})"
                                    wire:confirm="Supprimer ce secteur ?"
                                    class="text-red-500 hover:text-red-700 p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">Aucun secteur</p>
                        @endforelse
                    </div>
                    <div class="flex gap-2">
                        <input type="text" wire:model="newSectorName" placeholder="Nouveau secteur"
                            class="flex-1 px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                        <button wire:click="addSector"
                            class="px-3 py-2 bg-[#3E9B90] hover:bg-[#2d7a72] text-white text-sm rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </button>
                    </div>
                    @error('newSectorName') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Search --}}
        <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-6 mb-6">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher une brochure..."
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
        </div>

        {{-- Images Grid --}}
        <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Brochures uploadées</h2>

            @if ($imagesList->count() > 0)
                <div class="space-y-3">
                    @foreach ($imagesList as $image)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                            {{-- Miniature et infos --}}
                            <div class="flex items-start gap-4 flex-1 min-w-0">
                                <a href="{{ asset('storage/' . $image->path) }}" target="_blank" class="flex-shrink-0">
                                    <img src="{{ $image->thumbnail_path ? asset('storage/' . $image->thumbnail_path) : asset('storage/' . $image->path) }}"
                                        alt="{{ $image->alt_text ?? $image->name }}"
                                        class="w-14 h-[79px] object-cover rounded-lg border border-gray-200 dark:border-gray-600 hover:opacity-80 transition-opacity">
                                </a>
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">
                                        {{ $image->title ?? $image->name }}
                                    </h3>
                                    @if ($image->description)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                            {{ $image->description }}
                                        </p>
                                    @endif
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-xs text-gray-500 dark:text-gray-500">
                                        <span>{{ $image->formattedSize() }}</span>
                                        <span>{{ $image->created_at->format('d/m/Y') }}</span>
                                        <span>Par {{ $image->uploader->name }}</span>
                                        @if ($image->quantity_available !== null)
                                            <span class="text-[#3E9B90] font-medium">Stock: {{ $image->quantity_available }}</span>
                                        @endif
                                        @if ($image->display_order !== null)
                                            <span class="text-purple-600 dark:text-purple-400 font-medium">Ordre: {{ $image->display_order }}</span>
                                        @endif
                                        @if ($image->category)
                                            <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded text-xs">{{ $image->category->name }}</span>
                                        @endif
                                        @if ($image->author)
                                            <span class="bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 px-2 py-0.5 rounded text-xs">{{ $image->author->name }}</span>
                                        @endif
                                        @if ($image->sector)
                                            <span class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-2 py-0.5 rounded text-xs">{{ $image->sector->name }}</span>
                                        @endif
                                        @if ($image->responsable)
                                            <span class="bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 rounded text-xs">Resp: {{ $image->responsable->name }}</span>
                                        @endif
                                    </div>
                                    {{-- Liens --}}
                                    <div class="flex flex-wrap gap-3 mt-2">
                                        @if ($image->link_url)
                                            <a href="{{ $image->link_url }}" target="_blank" rel="noopener noreferrer"
                                                class="inline-flex items-center gap-1 text-xs text-[#3E9B90] hover:text-[#2d7a72] font-medium">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                </svg>
                                                {{ $image->link_text ?? 'Voir le lien' }}
                                            </a>
                                        @endif
                                        @if ($image->calameo_link_url)
                                            <a href="{{ $image->calameo_link_url }}" target="_blank" rel="noopener noreferrer"
                                                class="inline-flex items-center gap-1 text-xs text-orange-500 hover:text-orange-600 font-medium">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                                </svg>
                                                {{ $image->calameo_link_text ?? 'Voir sur Calameo' }}
                                            </a>
                                        @endif
                                        @if ($image->pdf_path)
                                            @php
                                                $downloadExtension = strtolower(pathinfo($image->pdf_path, PATHINFO_EXTENSION));
                                                $isDownloadPdf = $downloadExtension === 'pdf';
                                            @endphp
                                            <a href="{{ asset('storage/' . $image->pdf_path) }}" target="_blank" rel="noopener noreferrer"
                                                class="inline-flex items-center gap-1 text-xs {{ $isDownloadPdf ? 'text-red-600 hover:text-red-700' : 'text-blue-600 hover:text-blue-700' }} font-medium">
                                                @if ($isDownloadPdf)
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    Télécharger PDF
                                                @else
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    Télécharger image
                                                @endif
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-2 flex-shrink-0 ml-4">
                                {{-- View button (priorité: PDF > calameo > link > image) --}}
                                @php
                                    $consultUrl = $image->pdf_path
                                        ? asset('storage/' . $image->pdf_path)
                                        : ($image->calameo_link_url ?? $image->link_url ?? asset('storage/' . $image->path));
                                @endphp
                                <a href="{{ $consultUrl }}" target="_blank"
                                    class="p-2 bg-[#3E9B90] hover:bg-[#2d7a72] text-white rounded-lg transition-colors"
                                    title="Consulter">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>

                                {{-- Edit button --}}
                                <button wire:click="openEditModal({{ $image->id }})"
                                    class="p-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors"
                                    title="Modifier">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>

                                {{-- Delete button --}}
                                <button wire:click="openDeleteModal({{ $image->id }})"
                                    class="p-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors"
                                    title="Supprimer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
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
                    <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">Aucune brochure</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Uploadez votre première brochure ci-dessus</p>
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
                                Êtes-vous sûr de vouloir supprimer cette brochure ?
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

        {{-- Edit Modal --}}
        @if ($showEditModal && $editingImage)
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center px-4 py-6">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                        wire:click="closeEditModal"></div>

                    <div class="relative bg-white dark:bg-[#001716] rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            Modifier la brochure
                        </h3>

                        <form wire:submit.prevent="updateImage">
                            {{-- Image preview --}}
                            <div class="mb-4">
                                <img src="{{ asset('storage/' . $editingImage->thumbnail_path) }}"
                                    alt="{{ $editingImage->alt_text ?? $editingImage->name }}"
                                    class="w-32 h-32 object-cover rounded-lg">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ $editingImage->name }}</p>
                            </div>

                            <div class="space-y-4">
                                {{-- Titre --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Titre
                                    </label>
                                    <input type="text" wire:model="editTitle"
                                        class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                    @error('editTitle') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                </div>

                                {{-- Texte alternatif --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Texte alternatif
                                    </label>
                                    <input type="text" wire:model="editAltText"
                                        class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                    @error('editAltText') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                </div>

                                {{-- Description --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Description
                                    </label>
                                    <textarea wire:model="editDescription" rows="3"
                                        class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent"></textarea>
                                    @error('editDescription') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                </div>

                                {{-- Liens --}}
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Texte du lien
                                        </label>
                                        <input type="text" wire:model="editLinkText"
                                            class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            URL du lien
                                        </label>
                                        <input type="url" wire:model="editLinkUrl"
                                            class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                        @error('editLinkUrl') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Liens Calameo --}}
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Texte du lien Calameo
                                        </label>
                                        <input type="text" wire:model="editCalameoLinkText"
                                            class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            URL du lien Calameo
                                        </label>
                                        <input type="url" wire:model="editCalameoLinkUrl"
                                            class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                        @error('editCalameoLinkUrl') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Quantités --}}
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Quantité disponible
                                        </label>
                                        <input type="number" wire:model="editQuantityAvailable" min="0"
                                            class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                        @error('editQuantityAvailable') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Quantité max par commande
                                        </label>
                                        <input type="number" wire:model="editMaxOrderQuantity" min="0"
                                            class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                        @error('editMaxOrderQuantity') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Année, Ordre et Print --}}
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Année d'édition
                                        </label>
                                        <input type="number" wire:model="editEditionYear" min="1900" max="2100"
                                            class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                        @error('editEditionYear') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Ordre d'affichage
                                        </label>
                                        <input type="number" wire:model="editDisplayOrder" min="0" placeholder="Ex: 1, 2, 3..."
                                            class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                        @if(count($usedDisplayOrders) > 0)
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Ordres utilisés : {{ implode(', ', $usedDisplayOrders) }}
                                            </p>
                                        @endif
                                        @error('editDisplayOrder') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="flex items-end pb-2">
                                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                            <input type="checkbox" wire:model="editPrintAvailable"
                                                class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-[#3E9B90] focus:ring-2 focus:ring-[#3E9B90]">
                                            Disponible à la commande
                                        </label>
                                    </div>
                                </div>

                                {{-- Catégorie, Auteur, Secteur --}}
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Catégorie
                                        </label>
                                        <select wire:model="editCategoryId"
                                            class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                            <option value="">-- Aucune --</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('editCategoryId') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Auteur
                                        </label>
                                        <select wire:model="editAuthorId"
                                            class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                            <option value="">-- Aucun --</option>
                                            @foreach ($authors as $author)
                                                <option value="{{ $author->id }}">{{ $author->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('editAuthorId') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Secteur
                                        </label>
                                        <select wire:model="editSectorId"
                                            class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                            <option value="">-- Aucun --</option>
                                            @foreach ($sectors as $sector)
                                                <option value="{{ $sector->id }}">{{ $sector->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('editSectorId') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Responsable --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Responsable
                                    </label>
                                    <select wire:model="editResponsableId"
                                        class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                        <option value="">-- Aucun --</option>
                                        @foreach ($responsables as $responsable)
                                            <option value="{{ $responsable->id }}">{{ $responsable->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('editResponsableId') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                </div>

                                {{-- Gestion du fichier téléchargeable (PDF/Image) --}}
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        PDF ou image téléchargeable
                                    </label>

                                    @if ($editingImage->pdf_path)
                                        @php
                                            $fileExtension = strtolower(pathinfo($editingImage->pdf_path, PATHINFO_EXTENSION));
                                            $isPdf = $fileExtension === 'pdf';
                                        @endphp
                                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg mb-3">
                                            @if ($isPdf)
                                                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                </svg>
                                            @else
                                                <img src="{{ asset('storage/' . $editingImage->pdf_path) }}" alt="Preview" class="w-8 h-8 object-cover rounded">
                                            @endif
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $isPdf ? 'PDF actuel' : 'Image actuelle' }}</p>
                                                <a href="{{ asset('storage/' . $editingImage->pdf_path) }}" target="_blank"
                                                    class="text-xs text-[#3E9B90] hover:underline">
                                                    Voir le fichier
                                                </a>
                                            </div>
                                            <label class="flex items-center gap-2 text-sm text-red-600 cursor-pointer">
                                                <input type="checkbox" wire:model="removePdf"
                                                    class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                                Supprimer
                                            </label>
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            {{ $editingImage->pdf_path ? 'Remplacer le fichier' : 'Ajouter un PDF ou une image' }} (max 50MB)
                                        </label>
                                        <input type="file" wire:model="editPdfFile" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                            class="w-full text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none px-3 py-2"
                                            {{ $removePdf ? 'disabled' : '' }}>
                                        @error('editPdfFile')
                                            <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span>
                                        @enderror
                                        <div wire:loading wire:target="editPdfFile" class="text-xs text-gray-500 mt-1">
                                            Chargement du fichier...
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-3 justify-end mt-6">
                                <button type="button" wire:click="closeEditModal"
                                    class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                    Annuler
                                </button>

                                <button type="submit"
                                    class="px-6 py-3 text-sm font-medium text-white bg-[#3E9B90] hover:bg-[#2d7a72] rounded-lg transition-colors shadow-md">
                                    Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Report Detail Modal --}}
        @if ($showReportModal && $selectedReport)
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center px-4 py-6">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                        wire:click="closeReportModal"></div>

                    <div class="relative bg-white dark:bg-[#001716] rounded-lg shadow-xl max-w-lg w-full p-6">
                        <div class="flex items-start gap-4 mb-4">
                            <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 dark:bg-amber-900/30">
                                <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    Détail du signalement
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Signalé le {{ $selectedReport->created_at->format('d/m/Y à H:i') }}
                                </p>
                            </div>
                        </div>

                        {{-- Brochure info --}}
                        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg mb-4">
                            <img src="{{ $selectedReport->image->thumbnail_path ? asset('storage/' . $selectedReport->image->thumbnail_path) : asset('storage/' . $selectedReport->image->path) }}"
                                class="w-16 h-20 object-cover rounded-lg" alt="">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ $selectedReport->image->title ?? $selectedReport->image->name }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Signalé par: <span class="font-medium">{{ $selectedReport->user->name }}</span>
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $selectedReport->user->email }}
                                </p>
                            </div>
                        </div>

                        {{-- Comment --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Commentaire de l'utilisateur
                            </label>
                            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                                <p class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $selectedReport->comment }}</p>
                            </div>
                        </div>

                        {{-- Resolution note --}}
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Note de résolution (optionnel)
                            </label>
                            <textarea wire:model="resolutionNote" rows="3" placeholder="Décrivez les actions prises pour résoudre ce problème..."
                                class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent"></textarea>
                        </div>

                        <div class="flex gap-3 justify-end">
                            <button wire:click="closeReportModal"
                                class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                Fermer
                            </button>

                            <button wire:click="resolveReport"
                                class="px-6 py-3 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors shadow-md">
                                Marquer comme résolu
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
