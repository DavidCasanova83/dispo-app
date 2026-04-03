<div class="brochures-oti-vt min-h-screen bg-gray-50 dark:bg-zinc-900">
    <link rel="stylesheet" href="{{ asset('css/brochures-oti-vt.css') }}">

    <div class="flex flex-col min-h-screen">

        {{-- ============================================================ --}}
        {{-- HEADER : barre au-dessus des colonnes 2 et 3                 --}}
        {{-- ============================================================ --}}
        <header class="flex-shrink-0 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between py-3 px-4 sm:px-6 lg:px-8">
                <img src="{{ asset('images/cropped-cropped-VerdonTourisme-logo-inline-v2.png') }}" alt="Verdon Tourisme" style="width: 123px; height: 70px;" class="object-contain">
                <div class="relative w-full max-w-sm">
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Rechercher une brochure..."
                        class="w-full pl-10 pr-10 py-2.5 rounded-full border-2 border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white placeholder-gray-400 focus:border-[#3E9B90] focus:ring-[#3E9B90] focus:outline-none transition-colors text-sm" />
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    @if ($search)
                        <button wire:click="$set('search', '')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-pointer transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        </header>

        {{-- ============================================================ --}}
        {{-- CONTENU : colonnes 2 et 3 côte à côte                       --}}
        {{-- ============================================================ --}}
        <div class="flex flex-1 min-h-0">

        {{-- ============================================================ --}}
        {{-- COLONNE 2 : Menu catégories (visible par tous)               --}}
        {{-- ============================================================ --}}
        <aside class="hidden lg:flex flex-col flex-shrink-0 border-e border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-y-auto" style="width: 300px;">
            <div class="p-5">
                @if ($configuredMenu->isNotEmpty())
                    {{-- ===== MENU CONFIGURÉ DEPUIS L'ADMIN ===== --}}
                    <nav class="space-y-1">
                        @foreach ($configuredMenu as $menuItem)
                            <div x-data="{ open: false }">
                                <div class="flex items-center">
                                    <a href="{{ $menuItem->url }}"
                                        class="flex-1 flex items-center gap-2 px-3 py-2.5 text-sm font-bold rounded-lg transition-all duration-200 {{ request()->url() === url($menuItem->url) ? 'bg-[#3E9B90]/15 text-[#3E9B90]' : 'text-[#3E9B90] hover:bg-[#3E9B90]/10 hover:translate-x-0.5' }}">
                                        <span class="truncate">{{ $menuItem->title }}</span>
                                    </a>
                                    @if ($menuItem->children->count() > 0)
                                        <button @click="open = !open" class="p-1.5 text-[#3E9B90] hover:bg-[#3E9B90]/10 rounded-lg cursor-pointer transition-colors">
                                            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-45': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>

                                @if ($menuItem->children->count() > 0)
                                    <div x-show="open" x-collapse class="ml-4 mt-0.5 space-y-0.5 border-l-2 border-[#3E9B90]/20 pl-2">
                                        @foreach ($menuItem->children as $childItem)
                                            <a href="{{ $childItem->url }}"
                                                class="block px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->url() === url($childItem->url) ? 'bg-[#3E9B90]/15 text-[#3E9B90] font-medium' : 'text-[#3E9B90] hover:bg-[#3E9B90]/10 hover:translate-x-0.5' }}">
                                                {{ $childItem->title }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </nav>
                @else
                    {{-- ===== MENU AUTO-GÉNÉRÉ (FALLBACK) ===== --}}
                    @php
                        $menuQueryParams = [];
                        if ($this->sectorSlug) $menuQueryParams['secteur'] = $this->sectorSlug;
                        if ($this->authorSlug) $menuQueryParams['auteur'] = $this->authorSlug;
                        $menuQueryString = $menuQueryParams ? '?' . http_build_query($menuQueryParams) : '';
                    @endphp

                    <nav class="space-y-1">
                        <a href="{{ route('brochures-oti-vt') . $menuQueryString }}"
                            class="flex items-center gap-2 px-3 py-2.5 text-sm font-bold rounded-lg transition-all duration-200 {{ !$categoryId ? 'bg-[#3E9B90]/15 text-[#3E9B90]' : 'text-[#3E9B90] hover:bg-[#3E9B90]/10 hover:translate-x-0.5' }}">
                            Toutes les brochures
                        </a>

                        @foreach ($menuCategories as $menuCat)
                            <div x-data="{ expanded: {{ $categoryId == $menuCat->id ? 'true' : 'false' }} }">
                                <div class="flex items-center">
                                    <a href="{{ route('brochures-oti-vt.category', $menuCat->slug) . $menuQueryString }}"
                                        class="flex-1 flex items-center gap-2 px-3 py-2.5 text-sm font-bold rounded-lg transition-all duration-200 {{ $categoryId == $menuCat->id ? 'bg-[#3E9B90]/15 text-[#3E9B90]' : 'text-[#3E9B90] hover:bg-[#3E9B90]/10 hover:translate-x-0.5' }}">
                                        <span class="truncate">{{ $menuCat->name }}</span>
                                    </a>
                                    @if ($menuCat->subCategories->count() > 0)
                                        <button @click="expanded = !expanded" class="p-1 text-[#3E9B90]/50 hover:text-[#3E9B90] cursor-pointer transition-colors">
                                            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>

                                @if ($menuCat->subCategories->count() > 0)
                                    <div x-show="expanded" x-collapse class="ml-4 mt-0.5 space-y-0.5 border-l-2 border-[#3E9B90]/20 pl-2">
                                        @foreach ($menuCat->subCategories as $menuSubCat)
                                            <a href="{{ route('brochures-oti-vt.subcategory', [$menuCat->slug, $menuSubCat->slug]) . $menuQueryString }}"
                                                class="block px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ $subCategoryId == $menuSubCat->id ? 'bg-[#3E9B90]/15 text-[#3E9B90] font-medium' : 'text-[#3E9B90] hover:bg-[#3E9B90]/10 hover:translate-x-0.5' }}">
                                                {{ $menuSubCat->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </nav>

                    <hr class="my-4 border-[#3E9B90]/15">

                    <nav class="space-y-0.5">
                        @php
                            $authorMenuBaseParams = [];
                            if ($this->sectorSlug) $authorMenuBaseParams['secteur'] = $this->sectorSlug;
                            if ($this->categorySlug && $this->subCategorySlug) {
                                $authorMenuBaseRoute = route('brochures-oti-vt.subcategory', [$this->categorySlug, $this->subCategorySlug]);
                            } elseif ($this->categorySlug) {
                                $authorMenuBaseRoute = route('brochures-oti-vt.category', $this->categorySlug);
                            } else {
                                $authorMenuBaseRoute = route('brochures-oti-vt');
                            }
                            $authorMenuBaseQuery = $authorMenuBaseParams ? '?' . http_build_query($authorMenuBaseParams) : '';
                        @endphp
                        <a href="{{ $authorMenuBaseRoute . $authorMenuBaseQuery }}"
                            class="block px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ !$authorId ? 'bg-[#3E9B90]/15 text-[#3E9B90] font-medium' : 'text-[#3E9B90] hover:bg-[#3E9B90]/10 hover:translate-x-0.5' }}">
                            Tous les auteurs
                        </a>
                        @foreach ($authors as $author)
                            @php
                                $authorMenuParams = $authorMenuBaseParams;
                                $authorMenuParams['auteur'] = $author->slug;
                                $authorMenuLink = $authorMenuBaseRoute . '?' . http_build_query($authorMenuParams);
                            @endphp
                            <a href="{{ $authorMenuLink }}"
                                class="block px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ $authorId == $author->id ? 'bg-[#3E9B90]/15 text-[#3E9B90] font-medium' : 'text-[#3E9B90] hover:bg-[#3E9B90]/10 hover:translate-x-0.5' }}">
                                {{ $author->name }}
                            </a>
                        @endforeach
                    </nav>

                    <hr class="my-4 border-[#3E9B90]/15">

                    <nav class="space-y-0.5">
                        @php
                            $sectorMenuBaseParams = [];
                            if ($this->authorSlug) $sectorMenuBaseParams['auteur'] = $this->authorSlug;
                            if ($this->categorySlug && $this->subCategorySlug) {
                                $sectorMenuBaseRoute = route('brochures-oti-vt.subcategory', [$this->categorySlug, $this->subCategorySlug]);
                            } elseif ($this->categorySlug) {
                                $sectorMenuBaseRoute = route('brochures-oti-vt.category', $this->categorySlug);
                            } else {
                                $sectorMenuBaseRoute = route('brochures-oti-vt');
                            }
                            $sectorMenuBaseQuery = $sectorMenuBaseParams ? '?' . http_build_query($sectorMenuBaseParams) : '';
                        @endphp
                        <a href="{{ $sectorMenuBaseRoute . $sectorMenuBaseQuery }}"
                            class="block px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ !$sectorId ? 'bg-[#3E9B90]/15 text-[#3E9B90] font-medium' : 'text-[#3E9B90] hover:bg-[#3E9B90]/10 hover:translate-x-0.5' }}">
                            Tous les secteurs
                        </a>
                        @foreach ($sectors as $sector)
                            @php
                                $sectorMenuParams = $sectorMenuBaseParams;
                                $sectorMenuParams['secteur'] = $sector->slug;
                                $sectorMenuLink = $sectorMenuBaseRoute . '?' . http_build_query($sectorMenuParams);
                            @endphp
                            <a href="{{ $sectorMenuLink }}"
                                class="block px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ $sectorId == $sector->id ? 'bg-[#3E9B90]/15 text-[#3E9B90] font-medium' : 'text-[#3E9B90] hover:bg-[#3E9B90]/10 hover:translate-x-0.5' }}">
                                {{ $sector->name }}
                            </a>
                        @endforeach
                    </nav>
                @endif
            </div>
        </aside>

        {{-- ============================================================ --}}
        {{-- COLONNE 3 : Contenu principal (brochures)                    --}}
        {{-- ============================================================ --}}
        <main class="flex-1 min-w-0 py-6 px-4 sm:px-6 lg:px-8 overflow-y-auto">
            <div>

                {{-- Filtres rapides --}}
                @if ($categories->count() > 0 || $authors->count() > 0 || $sectors->count() > 0 || $search)
                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        {{-- Filtre Catégorie --}}
                        @if ($categories->count() > 0)
                            <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                <button @click="open = !open" type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs sm:text-sm font-medium rounded-full border-2 transition-all duration-200 cursor-pointer
                                    {{ $categoryId
                                        ? 'bg-[#3E9B90] border-[#3E9B90] text-white shadow-md'
                                        : 'bg-white dark:bg-zinc-800 border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-gray-300 hover:border-[#3E9B90]' }}">
                                    <svg class="w-3.5 h-3.5 {{ $categoryId ? 'text-white' : 'text-[#3E9B90]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                    <span>{{ $categoryId ? $categories->firstWhere('id', $categoryId)?->name : 'Catégorie' }}</span>
                                    <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="open" x-transition class="absolute z-20 mt-1 w-52 rounded-xl bg-white dark:bg-zinc-800 shadow-lg ring-1 ring-black/5 dark:ring-zinc-700 overflow-hidden">
                                    @php
                                        $catFilterParams = [];
                                        if ($this->sectorSlug) $catFilterParams['secteur'] = $this->sectorSlug;
                                        if ($this->authorSlug) $catFilterParams['auteur'] = $this->authorSlug;
                                        $catFilterQuery = $catFilterParams ? '?' . http_build_query($catFilterParams) : '';
                                    @endphp
                                    <div class="py-1 max-h-60 overflow-y-auto">
                                        <a href="{{ route('brochures-oti-vt') . $catFilterQuery }}"
                                            class="block px-4 py-2 text-sm {{ !$categoryId ? 'bg-[#3E9B90]/10 text-[#3E9B90] font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700' }}">
                                            Toutes les catégories
                                        </a>
                                        @foreach ($categories as $category)
                                            <a href="{{ route('brochures-oti-vt.category', $category->slug) . $catFilterQuery }}"
                                                class="block px-4 py-2 text-sm {{ $categoryId == $category->id ? 'bg-[#3E9B90]/10 text-[#3E9B90] font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700' }}">
                                                {{ $category->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Filtre Sous-catégorie --}}
                        @if ($categoryId && $subCategories->count() > 0)
                            <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                <button @click="open = !open" type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs sm:text-sm font-medium rounded-full border-2 transition-all duration-200 cursor-pointer
                                    {{ $subCategoryId
                                        ? 'bg-[#3E9B90] border-[#3E9B90] text-white shadow-md'
                                        : 'bg-white dark:bg-zinc-800 border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-gray-300 hover:border-[#3E9B90]' }}">
                                    <span>{{ $subCategoryId ? $subCategories->firstWhere('id', $subCategoryId)?->name : 'Sous-catégorie' }}</span>
                                    <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="open" x-transition class="absolute z-20 mt-1 w-52 rounded-xl bg-white dark:bg-zinc-800 shadow-lg ring-1 ring-black/5 dark:ring-zinc-700 overflow-hidden">
                                    @php
                                        $subFilterParams = [];
                                        if ($this->sectorSlug) $subFilterParams['secteur'] = $this->sectorSlug;
                                        if ($this->authorSlug) $subFilterParams['auteur'] = $this->authorSlug;
                                        $subFilterQuery = $subFilterParams ? '?' . http_build_query($subFilterParams) : '';
                                        $parentSlug = $currentCategory->slug ?? $categories->firstWhere('id', $categoryId)?->slug;
                                    @endphp
                                    <div class="py-1 max-h-60 overflow-y-auto">
                                        <a href="{{ route('brochures-oti-vt.category', $parentSlug) . $subFilterQuery }}"
                                            class="block px-4 py-2 text-sm {{ !$subCategoryId ? 'bg-[#3E9B90]/10 text-[#3E9B90] font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700' }}">
                                            Toutes les sous-catégories
                                        </a>
                                        @foreach ($subCategories as $subCategory)
                                            <a href="{{ route('brochures-oti-vt.subcategory', [$parentSlug, $subCategory->slug]) . $subFilterQuery }}"
                                                class="block px-4 py-2 text-sm {{ $subCategoryId == $subCategory->id ? 'bg-[#3E9B90]/10 text-[#3E9B90] font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700' }}">
                                                {{ $subCategory->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Filtre Auteur --}}
                        @if ($authors->count() > 0)
                            <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                <button @click="open = !open" type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs sm:text-sm font-medium rounded-full border-2 transition-all duration-200 cursor-pointer
                                    {{ $authorId
                                        ? 'bg-[#3E9B90] border-[#3E9B90] text-white shadow-md'
                                        : 'bg-white dark:bg-zinc-800 border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-gray-300 hover:border-[#3E9B90]' }}">
                                    <svg class="w-3.5 h-3.5 {{ $authorId ? 'text-white' : 'text-[#3E9B90]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span>{{ $authorId ? $authors->firstWhere('id', $authorId)?->name : 'Auteur' }}</span>
                                    <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="open" x-transition class="absolute z-20 mt-1 w-52 rounded-xl bg-white dark:bg-zinc-800 shadow-lg ring-1 ring-black/5 dark:ring-zinc-700 overflow-hidden">
                                    @php
                                        $authorFilterBase = $this->categorySlug
                                            ? ($this->subCategorySlug
                                                ? route('brochures-oti-vt.subcategory', [$this->categorySlug, $this->subCategorySlug])
                                                : route('brochures-oti-vt.category', $this->categorySlug))
                                            : route('brochures-oti-vt');
                                        $authorFilterBaseParams = [];
                                        if ($this->sectorSlug) $authorFilterBaseParams['secteur'] = $this->sectorSlug;
                                    @endphp
                                    <div class="py-1 max-h-60 overflow-y-auto">
                                        <a href="{{ $authorFilterBase . ($authorFilterBaseParams ? '?' . http_build_query($authorFilterBaseParams) : '') }}"
                                            class="block px-4 py-2 text-sm {{ !$authorId ? 'bg-[#3E9B90]/10 text-[#3E9B90] font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700' }}">
                                            Tous les auteurs
                                        </a>
                                        @foreach ($authors as $author)
                                            @php
                                                $authorFilterParams = $authorFilterBaseParams;
                                                $authorFilterParams['auteur'] = $author->slug;
                                            @endphp
                                            <a href="{{ $authorFilterBase . '?' . http_build_query($authorFilterParams) }}"
                                                class="block px-4 py-2 text-sm {{ $authorId == $author->id ? 'bg-[#3E9B90]/10 text-[#3E9B90] font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700' }}">
                                                {{ $author->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Filtre Secteur --}}
                        @if ($sectors->count() > 0)
                            <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                <button @click="open = !open" type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs sm:text-sm font-medium rounded-full border-2 transition-all duration-200 cursor-pointer
                                    {{ $sectorId
                                        ? 'bg-[#3E9B90] border-[#3E9B90] text-white shadow-md'
                                        : 'bg-white dark:bg-zinc-800 border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-gray-300 hover:border-[#3E9B90]' }}">
                                    <svg class="w-3.5 h-3.5 {{ $sectorId ? 'text-white' : 'text-[#3E9B90]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span>{{ $sectorId ? $sectors->firstWhere('id', $sectorId)?->name : 'Secteur' }}</span>
                                    <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="open" x-transition class="absolute z-20 mt-1 w-52 rounded-xl bg-white dark:bg-zinc-800 shadow-lg ring-1 ring-black/5 dark:ring-zinc-700 overflow-hidden">
                                    @php
                                        $sectorFilterBase = $this->categorySlug
                                            ? ($this->subCategorySlug
                                                ? route('brochures-oti-vt.subcategory', [$this->categorySlug, $this->subCategorySlug])
                                                : route('brochures-oti-vt.category', $this->categorySlug))
                                            : route('brochures-oti-vt');
                                        $sectorFilterBaseParams = [];
                                        if ($this->authorSlug) $sectorFilterBaseParams['auteur'] = $this->authorSlug;
                                    @endphp
                                    <div class="py-1 max-h-60 overflow-y-auto">
                                        <a href="{{ $sectorFilterBase . ($sectorFilterBaseParams ? '?' . http_build_query($sectorFilterBaseParams) : '') }}"
                                            class="block px-4 py-2 text-sm {{ !$sectorId ? 'bg-[#3E9B90]/10 text-[#3E9B90] font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700' }}">
                                            Tous les secteurs
                                        </a>
                                        @foreach ($sectors as $sector)
                                            @php
                                                $sectorFilterParams = $sectorFilterBaseParams;
                                                $sectorFilterParams['secteur'] = $sector->slug;
                                            @endphp
                                            <a href="{{ $sectorFilterBase . '?' . http_build_query($sectorFilterParams) }}"
                                                class="block px-4 py-2 text-sm {{ $sectorId == $sector->id ? 'bg-[#3E9B90]/10 text-[#3E9B90] font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700' }}">
                                                {{ $sector->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Bouton Effacer --}}
                        @if ($categoryId || $subCategoryId || $authorId || $sectorId)
                            <a href="{{ route('brochures-oti-vt') }}"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Effacer
                            </a>
                        @endif

                        {{-- Résultats de recherche (aligné à droite) --}}
                        @if ($search)
                            <span class="ml-auto text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                {{ $brochures->count() }} résultat(s) pour "<span class="font-medium text-[#3E9B90]">{{ $search }}</span>"
                            </span>
                        @endif
                    </div>
                @endif

                {{-- Menu mobile (burger) pour les catégories sur petit écran --}}
                <div class="lg:hidden mb-4" x-data="{ mobileMenu: false }">
                    <button @click="mobileMenu = !mobileMenu"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg border-2 border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-700 dark:text-gray-300 hover:border-[#3E9B90] transition-colors w-full justify-center cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        Filtrer par catégorie
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': mobileMenu }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div x-show="mobileMenu" x-collapse
                        class="mt-2 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-4 space-y-3">

                        @if ($configuredMenu->isNotEmpty())
                            {{-- Menu configuré mobile --}}
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Menu</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($configuredMenu as $menuItem)
                                        <a href="{{ $menuItem->url }}"
                                            class="px-3 py-1.5 text-sm rounded-full border transition-colors {{ request()->url() === url($menuItem->url) ? 'bg-[#3E9B90] border-[#3E9B90] text-white' : 'border-gray-300 dark:border-zinc-600 text-gray-600 dark:text-gray-400 hover:border-[#3E9B90]' }}">
                                            {{ $menuItem->title }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            {{-- Menu auto-généré mobile (fallback) --}}
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Catégories</h3>
                                <div class="flex flex-wrap gap-2">
                                    @php
                                        $mobileQueryParams = [];
                                        if ($this->sectorSlug) $mobileQueryParams['secteur'] = $this->sectorSlug;
                                        if ($this->authorSlug) $mobileQueryParams['auteur'] = $this->authorSlug;
                                        $mobileQueryString = $mobileQueryParams ? '?' . http_build_query($mobileQueryParams) : '';
                                    @endphp
                                    <a href="{{ route('brochures-oti-vt') . $mobileQueryString }}"
                                        class="px-3 py-1.5 text-sm rounded-full border transition-colors {{ !$categoryId ? 'bg-[#3E9B90] border-[#3E9B90] text-white' : 'border-gray-300 dark:border-zinc-600 text-gray-600 dark:text-gray-400 hover:border-[#3E9B90]' }}">
                                        Toutes
                                    </a>
                                    @foreach ($menuCategories as $menuCat)
                                        <a href="{{ route('brochures-oti-vt.category', $menuCat->slug) . $mobileQueryString }}"
                                            class="px-3 py-1.5 text-sm rounded-full border transition-colors {{ $categoryId == $menuCat->id ? 'bg-[#3E9B90] border-[#3E9B90] text-white' : 'border-gray-300 dark:border-zinc-600 text-gray-600 dark:text-gray-400 hover:border-[#3E9B90]' }}">
                                            {{ $menuCat->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            @if ($categoryId && $subCategories->count() > 0)
                                <div>
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Sous-catégories</h3>
                                    <div class="flex flex-wrap gap-2">
                                        @php
                                            $parentSlug = $currentCategory->slug ?? $categories->firstWhere('id', $categoryId)?->slug;
                                        @endphp
                                        <a href="{{ route('brochures-oti-vt.category', $parentSlug) . $mobileQueryString }}"
                                            class="px-3 py-1.5 text-sm rounded-full border transition-colors {{ !$subCategoryId ? 'bg-[#3E9B90] border-[#3E9B90] text-white' : 'border-gray-300 dark:border-zinc-600 text-gray-600 dark:text-gray-400 hover:border-[#3E9B90]' }}">
                                            Toutes
                                        </a>
                                        @foreach ($subCategories as $subCat)
                                            <a href="{{ route('brochures-oti-vt.subcategory', [$parentSlug, $subCat->slug]) . $mobileQueryString }}"
                                                class="px-3 py-1.5 text-sm rounded-full border transition-colors {{ $subCategoryId == $subCat->id ? 'bg-[#3E9B90] border-[#3E9B90] text-white' : 'border-gray-300 dark:border-zinc-600 text-gray-600 dark:text-gray-400 hover:border-[#3E9B90]' }}">
                                                {{ $subCat->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Auteurs</h3>
                                <div class="flex flex-wrap gap-2">
                                    @php
                                        $mobileAuthorBase = $this->categorySlug
                                            ? ($this->subCategorySlug
                                                ? route('brochures-oti-vt.subcategory', [$this->categorySlug, $this->subCategorySlug])
                                                : route('brochures-oti-vt.category', $this->categorySlug))
                                            : route('brochures-oti-vt');
                                        $mobileAuthorBaseParams = [];
                                        if ($this->sectorSlug) $mobileAuthorBaseParams['secteur'] = $this->sectorSlug;
                                    @endphp
                                    <a href="{{ $mobileAuthorBase . ($mobileAuthorBaseParams ? '?' . http_build_query($mobileAuthorBaseParams) : '') }}"
                                        class="px-3 py-1.5 text-sm rounded-full border transition-colors {{ !$authorId ? 'bg-[#3E9B90] border-[#3E9B90] text-white' : 'border-gray-300 dark:border-zinc-600 text-gray-600 dark:text-gray-400 hover:border-[#3E9B90]' }}">
                                        Tous
                                    </a>
                                    @foreach ($authors as $author)
                                        @php
                                            $mobileAuthorParams = $mobileAuthorBaseParams;
                                            $mobileAuthorParams['auteur'] = $author->slug;
                                        @endphp
                                        <a href="{{ $mobileAuthorBase . '?' . http_build_query($mobileAuthorParams) }}"
                                            class="px-3 py-1.5 text-sm rounded-full border transition-colors {{ $authorId == $author->id ? 'bg-[#3E9B90] border-[#3E9B90] text-white' : 'border-gray-300 dark:border-zinc-600 text-gray-600 dark:text-gray-400 hover:border-[#3E9B90]' }}">
                                            {{ $author->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Secteurs</h3>
                                <div class="flex flex-wrap gap-2">
                                    @php
                                        $mobileSectorBase = $this->categorySlug
                                            ? ($this->subCategorySlug
                                                ? route('brochures-oti-vt.subcategory', [$this->categorySlug, $this->subCategorySlug])
                                                : route('brochures-oti-vt.category', $this->categorySlug))
                                            : route('brochures-oti-vt');
                                        $mobileSectorBaseParams = [];
                                        if ($this->authorSlug) $mobileSectorBaseParams['auteur'] = $this->authorSlug;
                                    @endphp
                                    <a href="{{ $mobileSectorBase . ($mobileSectorBaseParams ? '?' . http_build_query($mobileSectorBaseParams) : '') }}"
                                        class="px-3 py-1.5 text-sm rounded-full border transition-colors {{ !$sectorId ? 'bg-[#3E9B90] border-[#3E9B90] text-white' : 'border-gray-300 dark:border-zinc-600 text-gray-600 dark:text-gray-400 hover:border-[#3E9B90]' }}">
                                        Tous
                                    </a>
                                    @foreach ($sectors as $sector)
                                        @php
                                            $mobileSectorParams = $mobileSectorBaseParams;
                                            $mobileSectorParams['secteur'] = $sector->slug;
                                        @endphp
                                        <a href="{{ $mobileSectorBase . '?' . http_build_query($mobileSectorParams) }}"
                                            class="px-3 py-1.5 text-sm rounded-full border transition-colors {{ $sectorId == $sector->id ? 'bg-[#3E9B90] border-[#3E9B90] text-white' : 'border-gray-300 dark:border-zinc-600 text-gray-600 dark:text-gray-400 hover:border-[#3E9B90]' }}">
                                            {{ $sector->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Flash Messages --}}
                @if (session()->has('success'))
                    <div class="mb-4 p-3 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 rounded-lg text-sm">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session()->has('error'))
                    <div class="mb-4 p-3 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Agenda en cours --}}
                @if ($showAgenda && $currentAgenda)
                    <div class="mb-6 p-4 bg-gradient-to-r from-[#3E9B90]/10 to-[#3E9B90]/5 dark:from-[#3E9B90]/20 dark:to-[#3E9B90]/10 rounded-xl border-2 border-[#3E9B90] shadow-lg">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="px-3 py-1 bg-[#3E9B90] text-white text-xs font-bold rounded-full uppercase tracking-wide">
                                Agenda en cours
                            </span>
                        </div>
                        <div class="flex gap-3 sm:gap-4 sm:items-center">
                            @if (\App\Models\Agenda::hasCoverImage())
                                <div class="flex-shrink-0">
                                    <img src="{{ \App\Models\Agenda::getCoverThumbnailUrl() }}" alt="Couverture de l'agenda"
                                        class="w-12 sm:w-20 aspect-[210/297] object-cover rounded-lg shadow-md ring-2 ring-[#3E9B90]/30">
                                </div>
                            @endif
                            <div class="flex-1 min-w-0 flex flex-col sm:flex-row sm:items-center sm:gap-4">
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm sm:text-lg font-bold text-gray-900 dark:text-white max-sm:line-clamp-2">
                                        {{ $currentAgenda->title ?? 'Agenda en cours' }} - {{ $currentAgenda->period }}
                                    </h3>
                                    @if ($currentAgenda->description)
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">{{ $currentAgenda->description }}</p>
                                    @endif
                                </div>
                                @php $agendaUrl = route('pdf.agenda.current'); @endphp
                                <div class="flex-shrink-0 flex items-center gap-1 sm:gap-2 self-end sm:self-center mt-1 sm:mt-0">
                                    <a href="{{ $agendaUrl }}" target="_blank" rel="noopener noreferrer"
                                        wire:click="trackAgendaClick('consulter')"
                                        class="inline-flex items-center justify-center w-7 h-7 sm:w-10 sm:h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors shadow-md cursor-pointer"
                                        title="Consulter en ligne">
                                        <svg class="w-3.5 h-3.5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <a href="{{ $agendaUrl }}" target="_blank" rel="noopener noreferrer"
                                        wire:click="trackAgendaClick('telecharger')"
                                        class="inline-flex items-center justify-center w-7 h-7 sm:w-10 sm:h-10 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors shadow-md cursor-pointer"
                                        title="Télécharger" download>
                                        <svg class="w-3.5 h-3.5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </a>
                                    <button x-data="{ copied: false }"
                                        @click="navigator.clipboard.writeText('{{ $agendaUrl }}'); copied = true; setTimeout(() => copied = false, 2000); $wire.trackAgendaClick('copier_lien')"
                                        class="inline-flex items-center justify-center w-7 h-7 sm:w-10 sm:h-10 rounded-lg transition-colors shadow-md cursor-pointer"
                                        :class="copied ? 'bg-green-500 text-white' : 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-900/50'"
                                        :title="copied ? 'Lien copié !' : 'Copier le lien'">
                                        <svg x-show="!copied" class="w-3.5 h-3.5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                                        </svg>
                                        <svg x-show="copied" x-cloak class="w-3.5 h-3.5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Grille des brochures --}}
                @if ($brochures->count() > 0)
                    <div class="grid grid-cols-4 gap-2 sm:gap-4">
                        @foreach ($brochures as $brochure)
                            @php
                                $downloadUrl = $brochure->pdf_path
                                    ? asset('storage/' . $brochure->pdf_path)
                                    : $brochure->link_url ?? asset('storage/' . $brochure->path);
                                $consultUrl = $brochure->pdf_path
                                    ? asset('storage/' . $brochure->pdf_path)
                                    : $brochure->calameo_link_url ?? ($brochure->link_url ?? asset('storage/' . $brochure->path));
                            @endphp
                            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 shadow-md dark:shadow-zinc-950/50 overflow-hidden hover:shadow-lg dark:hover:shadow-zinc-950/70 hover:-translate-y-0.5 transition-all duration-200">
                                {{-- Image de couverture --}}
                                <a href="{{ $consultUrl }}" target="_blank" rel="noopener noreferrer"
                                    wire:click="trackClick({{ $brochure->id }}, 'consulter')" class="block" style="max-height: 180px; overflow: hidden;">
                                    <img src="{{ $brochure->thumbnail_path ? asset('storage/' . $brochure->thumbnail_path) : asset('storage/' . $brochure->path) }}"
                                        alt="{{ $brochure->alt_text ?? ($brochure->title ?? $brochure->name) }}"
                                        class="w-full h-full object-cover">
                                </a>

                                {{-- Contenu --}}
                                <div class="p-2 sm:p-3">
                                    <h3 class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-white line-clamp-2 leading-tight">
                                        {{ $brochure->title ?? $brochure->name }}
                                    </h3>
                                    <p class="hidden sm:block mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $brochure->description ?? '' }}</p>

                                    <div class="mt-1.5 sm:mt-2 flex items-center justify-between">
                                        @if ($brochure->edition_year)
                                            @php
                                                $currentYear = (int) date('Y');
                                                $editionYear = (int) $brochure->edition_year;
                                                $yearDiff = $currentYear - $editionYear;
                                                if ($yearDiff <= 1) {
                                                    $colorClasses = 'text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900/30';
                                                } elseif ($yearDiff === 2) {
                                                    $colorClasses = 'text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/30';
                                                } else {
                                                    $colorClasses = 'text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/30';
                                                }
                                            @endphp
                                            <span class="text-xs font-medium {{ $colorClasses }} px-2 py-0.5 rounded">
                                                {{ $brochure->edition_year }}
                                            </span>
                                        @else
                                            <span></span>
                                        @endif

                                        {{-- Boutons d'action --}}
                                        <div class="flex items-center gap-0.5 sm:gap-1">
                                            <a href="{{ $consultUrl }}" target="_blank" rel="noopener noreferrer"
                                                wire:click="trackClick({{ $brochure->id }}, 'consulter')"
                                                class="inline-flex items-center justify-center w-5 h-5 sm:w-7 sm:h-7 rounded sm:rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors cursor-pointer"
                                                title="Consulter en ligne">
                                                <svg class="w-2.5 h-2.5 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </a>
                                            <a href="{{ $downloadUrl }}" target="_blank" rel="noopener noreferrer"
                                                wire:click="trackClick({{ $brochure->id }}, 'telecharger')"
                                                class="inline-flex items-center justify-center w-5 h-5 sm:w-7 sm:h-7 rounded sm:rounded-lg bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors cursor-pointer"
                                                title="Télécharger" {{ $brochure->pdf_path ? 'download' : '' }}>
                                                <svg class="w-2.5 h-2.5 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                            </a>
                                            <button x-data="{ copied: false }"
                                                @click="navigator.clipboard.writeText('{{ $downloadUrl }}'); copied = true; setTimeout(() => copied = false, 2000); $wire.trackClick({{ $brochure->id }}, 'copier_lien')"
                                                class="inline-flex items-center justify-center w-5 h-5 sm:w-7 sm:h-7 rounded sm:rounded-lg transition-colors cursor-pointer"
                                                :class="copied ? 'bg-green-500 text-white' : 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-900/50'"
                                                :title="copied ? 'Lien copié !' : 'Copier le lien'">
                                                <svg x-show="!copied" class="w-2.5 h-2.5 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                                                </svg>
                                                <svg x-show="copied" x-cloak class="w-2.5 h-2.5 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            @auth
                                                <button wire:click="openReportModal({{ $brochure->id }})"
                                                    class="inline-flex items-center justify-center w-5 h-5 sm:w-7 sm:h-7 rounded sm:rounded-lg bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 hover:bg-amber-200 dark:hover:bg-amber-900/50 transition-colors cursor-pointer"
                                                    title="Signaler un problème">
                                                    <svg class="w-2.5 h-2.5 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                    </svg>
                                                </button>
                                            @endauth
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white dark:bg-zinc-800 shadow-md dark:shadow-zinc-950/50 rounded-lg border border-gray-200 dark:border-zinc-700 text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">Aucune brochure disponible</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Revenez bientôt pour découvrir nos brochures</p>
                    </div>
                @endif

            </div>
        </main>

        </div>{{-- fin flex colonnes --}}
    </div>{{-- fin flex-col --}}

    {{-- Modal de signalement --}}
    @if ($showReportModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 dark:bg-black/70 transition-opacity" wire:click="closeReportModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl dark:shadow-zinc-950/50 transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200 dark:border-zinc-700">
                    <form wire:submit="submitReport">
                        <div class="bg-white dark:bg-zinc-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 dark:bg-amber-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">Signaler un problème</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Brochure : <strong class="text-gray-700 dark:text-gray-300">{{ $selectedBrochureTitle }}</strong>
                                        </p>
                                    </div>
                                    <div class="mt-4">
                                        <label for="reportComment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Décrivez le problème rencontré
                                        </label>
                                        <textarea id="reportComment" wire:model="reportComment" rows="4"
                                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                            placeholder="Décrivez le problème (minimum 10 caractères)..."></textarea>
                                        @error('reportComment')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 dark:border-zinc-700">
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
