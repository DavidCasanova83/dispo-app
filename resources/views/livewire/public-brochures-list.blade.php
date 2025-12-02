<div class="min-h-screen bg-gray-50 dark:bg-zinc-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Nos brochures</h1>
            <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
                Consultez et téléchargez nos brochures touristiques
            </p>
        </div>

        {{-- Flash Messages --}}
        @if (session()->has('success'))
            <div
                class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div
                class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        {{-- Filtres --}}
        @if ($categories->count() > 0 || $authors->count() > 0 || $sectors->count() > 0)
            <div class="mb-6">
                <div class="flex flex-wrap items-center justify-center gap-3">
                    {{-- Filtre Catégorie --}}
                    @if ($categories->count() > 0)
                        <div x-data="{ open: false }" @click.away="open = false" class="relative">
                            <button @click="open = !open" type="button"
                                class="inline-flex items-center gap-2 pl-3 pr-3 py-2.5 text-sm font-medium rounded-full border-2 transition-all duration-200
                                {{ $categoryId
                                    ? 'bg-[#3E9B90] border-[#3E9B90] text-white shadow-md'
                                    : 'bg-white dark:bg-[#001716] border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-gray-300 hover:border-[#3E9B90] dark:hover:border-[#3E9B90]' }}">
                                <svg class="w-4 h-4 {{ $categoryId ? 'text-white' : 'text-[#3E9B90]' }}" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                                    </path>
                                </svg>
                                <span>{{ $categoryId ? $categories->firstWhere('id', $categoryId)?->name : 'Catégorie' }}</span>
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute z-10 mt-2 w-56 origin-top-left rounded-xl bg-white dark:bg-zinc-800 shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-zinc-700 overflow-hidden">
                                <div class="py-1 max-h-60 overflow-y-auto">
                                    <button wire:click="$set('categoryId', null)" @click="open = false"
                                        class="w-full px-4 py-2.5 text-left text-sm flex items-center gap-2 {{ !$categoryId ? 'bg-[#3E9B90]/10 text-[#3E9B90] font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700' }}">
                                        <span
                                            class="w-2 h-2 rounded-full {{ !$categoryId ? 'bg-[#3E9B90]' : 'bg-transparent' }}"></span>
                                        Toutes les catégories
                                    </button>
                                    @foreach ($categories as $category)
                                        <button wire:click="$set('categoryId', {{ $category->id }})"
                                            @click="open = false"
                                            class="w-full px-4 py-2.5 text-left text-sm flex items-center gap-2 {{ $categoryId == $category->id ? 'bg-[#3E9B90]/10 text-[#3E9B90] font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700' }}">
                                            <span
                                                class="w-2 h-2 rounded-full {{ $categoryId == $category->id ? 'bg-[#3E9B90]' : 'bg-transparent' }}"></span>
                                            {{ $category->name }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Filtre Auteur --}}
                    @if ($authors->count() > 0)
                        <div x-data="{ open: false }" @click.away="open = false" class="relative">
                            <button @click="open = !open" type="button"
                                class="inline-flex items-center gap-2 pl-3 pr-3 py-2.5 text-sm font-medium rounded-full border-2 transition-all duration-200
                                {{ $authorId
                                    ? 'bg-[#3E9B90] border-[#3E9B90] text-white shadow-md'
                                    : 'bg-white dark:bg-[#001716] border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-gray-300 hover:border-[#3E9B90] dark:hover:border-[#3E9B90]' }}">
                                <svg class="w-4 h-4 {{ $authorId ? 'text-white' : 'text-[#3E9B90]' }}" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span>{{ $authorId ? $authors->firstWhere('id', $authorId)?->name : 'Auteur' }}</span>
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute z-10 mt-2 w-56 origin-top-left rounded-xl bg-white dark:bg-zinc-800 shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-zinc-700 overflow-hidden">
                                <div class="py-1 max-h-60 overflow-y-auto">
                                    <button wire:click="$set('authorId', null)" @click="open = false"
                                        class="w-full px-4 py-2.5 text-left text-sm flex items-center gap-2 {{ !$authorId ? 'bg-[#3E9B90]/10 text-[#3E9B90] font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700' }}">
                                        <span
                                            class="w-2 h-2 rounded-full {{ !$authorId ? 'bg-[#3E9B90]' : 'bg-transparent' }}"></span>
                                        Tous les auteurs
                                    </button>
                                    @foreach ($authors as $author)
                                        <button wire:click="$set('authorId', {{ $author->id }})"
                                            @click="open = false"
                                            class="w-full px-4 py-2.5 text-left text-sm flex items-center gap-2 {{ $authorId == $author->id ? 'bg-[#3E9B90]/10 text-[#3E9B90] font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700' }}">
                                            <span
                                                class="w-2 h-2 rounded-full {{ $authorId == $author->id ? 'bg-[#3E9B90]' : 'bg-transparent' }}"></span>
                                            {{ $author->name }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Filtre Secteur --}}
                    @if ($sectors->count() > 0)
                        <div x-data="{ open: false }" @click.away="open = false" class="relative">
                            <button @click="open = !open" type="button"
                                class="inline-flex items-center gap-2 pl-3 pr-3 py-2.5 text-sm font-medium rounded-full border-2 transition-all duration-200
                                {{ $sectorId
                                    ? 'bg-[#3E9B90] border-[#3E9B90] text-white shadow-md'
                                    : 'bg-white dark:bg-[#001716] border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-gray-300 hover:border-[#3E9B90] dark:hover:border-[#3E9B90]' }}">
                                <svg class="w-4 h-4 {{ $sectorId ? 'text-white' : 'text-[#3E9B90]' }}" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span>{{ $sectorId ? $sectors->firstWhere('id', $sectorId)?->name : 'Secteur' }}</span>
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute z-10 mt-2 w-56 origin-top-left rounded-xl bg-white dark:bg-zinc-800 shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-zinc-700 overflow-hidden">
                                <div class="py-1 max-h-60 overflow-y-auto">
                                    <button wire:click="$set('sectorId', null)" @click="open = false"
                                        class="w-full px-4 py-2.5 text-left text-sm flex items-center gap-2 {{ !$sectorId ? 'bg-[#3E9B90]/10 text-[#3E9B90] font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700' }}">
                                        <span
                                            class="w-2 h-2 rounded-full {{ !$sectorId ? 'bg-[#3E9B90]' : 'bg-transparent' }}"></span>
                                        Tous les secteurs
                                    </button>
                                    @foreach ($sectors as $sector)
                                        <button wire:click="$set('sectorId', {{ $sector->id }})"
                                            @click="open = false"
                                            class="w-full px-4 py-2.5 text-left text-sm flex items-center gap-2 {{ $sectorId == $sector->id ? 'bg-[#3E9B90]/10 text-[#3E9B90] font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700' }}">
                                            <span
                                                class="w-2 h-2 rounded-full {{ $sectorId == $sector->id ? 'bg-[#3E9B90]' : 'bg-transparent' }}"></span>
                                            {{ $sector->name }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Bouton Réinitialiser --}}
                    @if ($categoryId || $authorId || $sectorId)
                        <button wire:click="resetFilters" type="button"
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Effacer
                        </button>
                    @endif
                </div>
            </div>
        @endif

        {{-- Liste des brochures --}}
        <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg overflow-hidden">
            @if ($brochures->count() > 0)
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($brochures as $brochure)
                        <li class="p-4 sm:p-6 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <div class="flex items-center gap-4">
                                {{-- Thumbnail --}}
                                <div class="flex-shrink-0">
                                    <img src="{{ $brochure->thumbnail_path ? asset('storage/' . $brochure->thumbnail_path) : asset('storage/' . $brochure->path) }}"
                                        alt="{{ $brochure->alt_text ?? ($brochure->title ?? $brochure->name) }}"
                                        class="w-16 sm:w-20 aspect-[210/297] object-cover rounded-lg shadow-md">
                                </div>

                                {{-- Titre et description --}}
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                                        {{ $brochure->title ?? $brochure->name }}
                                    </h3>
                                    @if ($brochure->description)
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                            {{ $brochure->description }}
                                        </p>
                                    @endif
                                    @if ($brochure->edition_year)
                                        @php
                                            $currentYear = (int) date('Y');
                                            $editionYear = (int) $brochure->edition_year;
                                            $yearDiff = $currentYear - $editionYear;

                                            if ($yearDiff <= 1) {
                                                // Année en cours ou n-1 : vert
                                                $colorClasses =
                                                    'text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900/30';
                                            } elseif ($yearDiff === 2) {
                                                // Année n-2 : jaune/ambre
                                                $colorClasses =
                                                    'text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/30';
                                            } else {
                                                // Année n-3 ou plus ancien : rouge
                                                $colorClasses =
                                                    'text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/30';
                                            }
                                        @endphp
                                        <span
                                            class="inline-block mt-2 text-xs font-medium {{ $colorClasses }} px-2 py-1 rounded">
                                            Edition {{ $brochure->edition_year }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Liens (icônes) --}}
                                <div class="flex-shrink-0 flex items-center gap-3">
                                    {{-- Lien PDF --}}
                                    @if ($brochure->link_url)
                                        <a href="{{ $brochure->link_url }}" target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                                            title="{{ $brochure->link_text ?? 'Télécharger le PDF' }}">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 2l5 5h-5V4zM8.5 13.5a1 1 0 0 1 1-1h.5v3h-.5a1 1 0 0 1-1-1v-1zm3 0c0-.55.45-1 1-1h.5c.55 0 1 .45 1 1v1c0 .55-.45 1-1 1H12v1h1.5v1H12c-.55 0-1-.45-1-1v-3zm4 0c0-.55.45-1 1-1h1.5v1H17v.5h.5c.55 0 1 .45 1 1v.5c0 .55-.45 1-1 1H16v-1h1v-.5h-.5c-.55 0-1-.45-1-1v-.5z" />
                                            </svg>
                                        </a>
                                    @endif

                                    {{-- Lien Calameo --}}
                                    @if ($brochure->calameo_link_url)
                                        <a href="{{ $brochure->calameo_link_url }}" target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 hover:bg-orange-200 dark:hover:bg-orange-900/50 transition-colors"
                                            title="{{ $brochure->calameo_link_text ?? 'Voir sur Calameo' }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                                                </path>
                                            </svg>
                                        </a>
                                    @endif

                                    {{-- Bouton Signaler (visible uniquement si connecté) --}}
                                    @auth
                                        <button wire:click="openReportModal({{ $brochure->id }})"
                                            class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 hover:bg-amber-200 dark:hover:bg-amber-900/50 transition-colors"
                                            title="Signaler un problème">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                                </path>
                                            </svg>
                                        </button>
                                    @endauth

                                    {{-- Si aucun lien disponible --}}
                                    @if (!$brochure->link_url && !$brochure->calameo_link_url)
                                        <span class="text-sm text-gray-400 dark:text-gray-500 italic">
                                            Bientôt disponible
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                        </path>
                    </svg>
                    <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">Aucune brochure disponible</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Revenez bientôt pour découvrir nos brochures
                    </p>
                </div>
            @endif
        </div>

        {{-- Lien vers commande --}}
        <div class="mt-8 text-center">
            <a href="{{ url('/commander-images') }}"
                class="inline-flex items-center gap-2 px-6 py-3 bg-[#3E9B90] hover:bg-[#2d7a72] text-white font-semibold rounded-lg transition-colors shadow-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z">
                    </path>
                </svg>
                Commander des brochures (envoi courrier)
            </a>
        </div>
    </div>

    {{-- Modal de signalement --}}
    @if ($showReportModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Overlay --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeReportModal">
                </div>

                {{-- Centrage du modal --}}
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                {{-- Contenu du modal --}}
                <div
                    class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit="submitReport">
                        <div class="bg-white dark:bg-zinc-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div
                                    class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 dark:bg-amber-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white"
                                        id="modal-title">
                                        Signaler un problème
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Brochure : <strong
                                                class="text-gray-700 dark:text-gray-300">{{ $selectedBrochureTitle }}</strong>
                                        </p>
                                    </div>
                                    <div class="mt-4">
                                        <label for="reportComment"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Décrivez le problème rencontré
                                        </label>
                                        <textarea id="reportComment" wire:model="reportComment" rows="4"
                                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                            placeholder="Décrivez le problème (minimum 10 caractères)..."></textarea>
                                        @error('reportComment')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-amber-600 text-base font-medium text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Envoyer le signalement
                            </button>
                            <button type="button" wire:click="closeReportModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-zinc-600 shadow-sm px-4 py-2 bg-white dark:bg-zinc-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
