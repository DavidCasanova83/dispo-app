<div class="min-h-screen bg-gray-50 dark:bg-zinc-900">
    <div class="flex flex-col min-h-screen">

        {{-- HEADER --}}
        <header class="flex-shrink-0 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between py-3 px-4 sm:px-6 lg:px-8">
                <img src="{{ asset('images/cropped-cropped-VerdonTourisme-logo-inline-v2.png') }}" alt="Verdon Tourisme" style="width: 123px; height: 70px;" class="object-contain">
                <div class="relative w-full max-w-sm">
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Rechercher une brochure..."
                        class="w-full pl-10 pr-10 py-2.5 rounded-full border-2 border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white placeholder-gray-400 focus:border-violet-500 focus:ring-violet-500 focus:outline-none transition-colors text-sm" />
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

        {{-- CONTENU --}}
        <div class="flex flex-1 min-h-0">

            {{-- COLONNE MENU --}}
            <aside class="hidden lg:flex flex-col flex-shrink-0 border-e border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-y-auto" style="width: 300px;">
                <div class="p-5">
                    @if ($configuredMenu->isNotEmpty())
                        <nav class="space-y-1">
                            @foreach ($configuredMenu as $menuItem)
                                @php $isOrange = $menuItem->auth_only; @endphp
                                <div x-data="{ open: true }">
                                    <a href="{{ $menuItem->url }}"
                                        class="flex items-center gap-2 px-3 py-2.5 text-sm font-bold rounded-lg transition-all duration-200 {{ $isOrange
                                            ? 'text-orange-600 hover:bg-orange-500/10 hover:translate-x-0.5'
                                            : 'text-[#3E9B90] hover:bg-[#3E9B90]/10 hover:translate-x-0.5' }}">
                                        @if ($menuItem->children->count() > 0)
                                            <svg @click.prevent="open = !open" class="w-4 h-4 flex-shrink-0 transition-transform cursor-pointer" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        @endif
                                        <span class="truncate">{{ $menuItem->title }}</span>
                                        @if ($isOrange)
                                            <span class="flex-shrink-0 relative group/lock">
                                                <svg class="w-3.5 h-3.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                </svg>
                                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 text-[10px] font-medium text-white bg-gray-900 dark:bg-zinc-700 rounded shadow-lg whitespace-nowrap opacity-0 group-hover/lock:opacity-100 transition-opacity pointer-events-none">
                                                    Réservé aux utilisateurs connectés
                                                </span>
                                            </span>
                                        @endif
                                    </a>

                                    @if ($menuItem->children->count() > 0)
                                        <div x-show="open" x-collapse class="ml-4 mt-0.5 space-y-0.5 border-l-2 {{ $isOrange ? 'border-orange-500/20' : 'border-[#3E9B90]/20' }} pl-2">
                                            @foreach ($menuItem->children as $childItem)
                                                @php $isChildOrange = $childItem->auth_only || $isOrange; @endphp
                                                <a href="{{ $childItem->url }}"
                                                    class="flex items-center gap-1.5 px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ $isChildOrange
                                                        ? 'text-orange-600 hover:bg-orange-500/10 hover:translate-x-0.5'
                                                        : 'text-[#3E9B90] hover:bg-[#3E9B90]/10 hover:translate-x-0.5' }}">
                                                    <span class="truncate">{{ $childItem->title }}</span>
                                                    @if ($isChildOrange)
                                                        <span class="flex-shrink-0 relative group/lock">
                                                            <svg class="w-3 h-3 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                            </svg>
                                                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 text-[10px] font-medium text-white bg-gray-900 dark:bg-zinc-700 rounded shadow-lg whitespace-nowrap opacity-0 group-hover/lock:opacity-100 transition-opacity pointer-events-none">
                                                                Réservé aux utilisateurs connectés
                                                            </span>
                                                        </span>
                                                    @endif
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </nav>
                    @endif

                    <div class="mt-auto pt-4 border-t border-[#3E9B90]/15 space-y-2">
                        <a href="{{ route('mes-brochures') }}"
                            class="flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg bg-violet-600 text-white hover:bg-violet-700 shadow-md hover:shadow-lg transition-all duration-200">
                            <span>Mes Brochures</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </a>
                        <a href="{{ route('admin.images.statistics') }}"
                            class="flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg bg-[#3E9B90] text-white hover:bg-[#357f76] shadow-md hover:shadow-lg transition-all duration-200">
                            <span>Statistiques</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </aside>

            {{-- COLONNE CONTENU --}}
            <main class="flex-1 min-w-0 py-6 px-4 sm:px-6 lg:px-8 overflow-y-auto">
                <div>

                    {{-- Flash Messages --}}
                    @if (session('success'))
                        <div class="mb-4 p-3 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 rounded-lg text-sm">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="mb-4 p-3 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg text-sm">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Bannière des signalements --}}
                    @if ($pendingReports->count() > 0)
                        <div class="mb-4 rounded-lg bg-gradient-to-r from-amber-500 to-orange-500 p-4 shadow-lg">
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-white flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-sm font-bold text-white">
                                            {{ $pendingReports->count() }} signalement(s) en attente
                                        </h3>
                                        @if ($unreadReportsCount > 0)
                                            <span class="px-2 py-0.5 bg-red-600 text-white text-xs font-bold rounded-full">
                                                {{ $unreadReportsCount }} non lu(s)
                                            </span>
                                        @endif
                                    </div>
                                    <div class="space-y-1">
                                        @foreach ($pendingReports->take(3) as $report)
                                            <button wire:click="openReportModal({{ $report->id }})"
                                                class="flex items-center gap-2 w-full text-left p-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors text-sm cursor-pointer">
                                                @if (!$report->is_read)
                                                    <span class="w-2 h-2 bg-red-400 rounded-full flex-shrink-0"></span>
                                                @else
                                                    <span class="w-2 h-2 bg-white/30 rounded-full flex-shrink-0"></span>
                                                @endif
                                                <span class="text-white font-medium truncate">{{ $report->image->title ?? $report->image->name }}</span>
                                                <span class="text-white/60 text-xs ml-auto flex-shrink-0">{{ $report->created_at->diffForHumans() }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Titre + compteur --}}
                    <div class="mb-4 flex items-center justify-between">
                        <h1 class="text-lg font-bold text-gray-900 dark:text-white">
                            Mes Brochures <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $brochures->total() }})</span>
                        </h1>
                        <a href="{{ route('brochures-oti-vt') }}"
                            class="text-sm text-[#3E9B90] hover:text-[#357f76] font-medium transition-colors">
                            Voir toutes les brochures
                        </a>
                    </div>

                    {{-- Grille des brochures --}}
                    @if ($brochures->count() > 0)
                        <div class="grid grid-cols-4 gap-2 sm:gap-4">
                            @foreach ($brochures as $brochure)
                                @php
                                    $downloadUrl = $brochure->pdf_path
                                        ? asset('storage/' . $brochure->pdf_path)
                                        : ($brochure->link_url ?? asset('storage/' . $brochure->path));
                                    $consultUrl = $brochure->pdf_path
                                        ? asset('storage/' . $brochure->pdf_path)
                                        : ($brochure->calameo_link_url ?? $brochure->link_url ?? asset('storage/' . $brochure->path));
                                @endphp
                                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 shadow-md dark:shadow-zinc-950/50 overflow-hidden hover:shadow-lg dark:hover:shadow-zinc-950/70 hover:-translate-y-0.5 transition-all duration-200">
                                    {{-- Image --}}
                                    <a href="{{ $consultUrl }}" target="_blank" rel="noopener noreferrer" class="block" style="max-height: 180px; overflow: hidden;">
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

                                            {{-- Boutons --}}
                                            <div class="flex items-center gap-0.5 sm:gap-1">
                                                <a href="{{ $consultUrl }}" target="_blank" rel="noopener noreferrer"
                                                    class="inline-flex items-center justify-center w-5 h-5 sm:w-7 sm:h-7 rounded sm:rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors cursor-pointer"
                                                    title="Consulter">
                                                    <svg class="w-2.5 h-2.5 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </a>
                                                <a href="{{ $downloadUrl }}" target="_blank" rel="noopener noreferrer"
                                                    class="inline-flex items-center justify-center w-5 h-5 sm:w-7 sm:h-7 rounded sm:rounded-lg bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors cursor-pointer"
                                                    title="Télécharger" {{ $brochure->pdf_path ? 'download' : '' }}>
                                                    <svg class="w-2.5 h-2.5 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                </a>
                                                {{-- Bouton Modifier (violet) --}}
                                                <button wire:click="openEditModal({{ $brochure->id }})"
                                                    class="inline-flex items-center justify-center w-5 h-5 sm:w-7 sm:h-7 rounded sm:rounded-lg bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 hover:bg-violet-200 dark:hover:bg-violet-900/50 transition-colors cursor-pointer"
                                                    title="Modifier">
                                                    <svg class="w-2.5 h-2.5 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Pagination --}}
                        <div class="mt-6">
                            {{ $brochures->links() }}
                        </div>
                    @else
                        <div class="bg-white dark:bg-zinc-800 shadow-md dark:shadow-zinc-950/50 rounded-lg border border-gray-200 dark:border-zinc-700 text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">Aucune brochure</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Vous n'êtes responsable d'aucune brochure</p>
                        </div>
                    @endif

                </div>
            </main>

        </div>{{-- fin flex colonnes --}}
    </div>{{-- fin flex-col --}}

    {{-- Edit Brochure Modal --}}
    @if ($showEditModal && $editingImage)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4 py-6">
                <div class="fixed inset-0 bg-black/50 dark:bg-black/70 transition-opacity" wire:click="closeEditModal"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-lg shadow-xl dark:shadow-zinc-950/50 max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto border border-gray-200 dark:border-zinc-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Modifier la brochure</h3>
                    <form wire:submit.prevent="updateBrochure">
                        {{-- Current image preview --}}
                        <div class="mb-4 flex items-center gap-4">
                            <img src="{{ $editingImage->thumbnail_path ? asset('storage/' . $editingImage->thumbnail_path) : asset('storage/' . $editingImage->path) }}"
                                alt="{{ $editingImage->alt_text ?? $editingImage->name }}"
                                class="w-20 h-28 object-cover rounded-lg border border-gray-200 dark:border-zinc-600">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $editingImage->title ?? $editingImage->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $editingImage->name }}</p>
                            </div>
                        </div>

                        {{-- Title --}}
                        <div class="mb-4">
                            <label for="editTitle" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titre</label>
                            <input type="text" id="editTitle" wire:model="editTitle"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                                placeholder="Titre de la brochure">
                            @error('editTitle') <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- Description --}}
                        <div class="mb-4">
                            <label for="editDescription" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea id="editDescription" wire:model="editDescription" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                                placeholder="Description de la brochure"></textarea>
                            @error('editDescription') <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- Edition Year --}}
                        <div class="mb-4">
                            <label for="editEditionYear" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Année d'édition</label>
                            <input type="number" id="editEditionYear" wire:model="editEditionYear" min="1900" max="{{ date('Y') + 5 }}"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                                placeholder="{{ date('Y') }}">
                            @error('editEditionYear') <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- Presentation Image --}}
                        <div class="border-t border-gray-200 dark:border-zinc-700 pt-4 mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Image de présentation</label>
                            @php
                                $editDefaultImageUrl = $this->getEditDefaultImageUrl();
                                $editAuthor = $editingImage->author;
                                $editHasAuthorDefault = $editAuthor && $editAuthor->hasDefaultImage();
                            @endphp
                            <div class="mb-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model.live="editUseDefaultImage"
                                        class="rounded border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 text-violet-500 focus:ring-2 focus:ring-violet-500"
                                        {{ !$editDefaultImageUrl ? 'disabled' : '' }}>
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Utiliser l'image par défaut</span>
                                </label>
                                @if ($editDefaultImageUrl)
                                    <div class="mt-2 flex items-center gap-3">
                                        <img src="{{ $editDefaultImageUrl }}" class="w-16 h-20 object-cover rounded shadow">
                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                            @if ($editHasAuthorDefault)
                                                <span class="text-blue-600 dark:text-blue-400 font-medium">Image de l'auteur : {{ $editAuthor->name }}</span>
                                            @else
                                                <span class="text-gray-500">Image par défaut globale</span>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Aucune image par défaut configurée.</p>
                                @endif
                            </div>
                            @if (!$editUseDefaultImage)
                                @if ($editPresentationImage)
                                    <div class="mb-3 p-3 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Aperçu :</p>
                                        <img src="{{ $editPresentationImage->temporaryUrl() }}" class="w-24 h-32 object-cover rounded-lg border border-gray-200 dark:border-zinc-600">
                                    </div>
                                @endif
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Changer l'image (max 10MB)</label>
                                    <input type="file" wire:model="editPresentationImage" accept="image/jpeg,image/png,image/gif,image/webp"
                                        class="w-full text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-zinc-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-zinc-700 px-3 py-2">
                                    @error('editPresentationImage') <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span> @enderror
                                    <div wire:loading wire:target="editPresentationImage" class="text-xs text-gray-500 mt-1">Chargement...</div>
                                </div>
                            @endif
                        </div>

                        {{-- PDF Management --}}
                        <div class="border-t border-gray-200 dark:border-zinc-700 pt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">PDF téléchargeable</label>
                            @if ($editingImage->pdf_path)
                                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-zinc-700 rounded-lg mb-3">
                                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">PDF actuel</p>
                                        <a href="{{ asset('storage/' . $editingImage->pdf_path) }}" target="_blank" class="text-xs text-violet-600 hover:underline">Voir le PDF</a>
                                    </div>
                                    <label class="flex items-center gap-2 text-sm text-red-600 cursor-pointer">
                                        <input type="checkbox" wire:model="removePdf" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                        Supprimer
                                    </label>
                                </div>
                            @endif
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $editingImage->pdf_path ? 'Remplacer le PDF' : 'Ajouter un PDF' }} (max 50MB)
                                </label>
                                <input type="file" wire:model="editPdfFile" accept=".pdf,application/pdf"
                                    class="w-full text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-zinc-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-zinc-700 px-3 py-2"
                                    {{ $removePdf ? 'disabled' : '' }}>
                                @error('editPdfFile') <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span> @enderror
                                <div wire:loading wire:target="editPdfFile" class="text-xs text-gray-500 mt-1">Chargement...</div>
                            </div>
                        </div>

                        <div class="flex gap-3 justify-end mt-6">
                            <button type="button" wire:click="closeEditModal"
                                class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 hover:bg-gray-300 dark:hover:bg-zinc-600 rounded-lg transition-colors cursor-pointer">
                                Annuler
                            </button>
                            <button type="submit"
                                class="px-6 py-3 text-sm font-medium text-white bg-violet-600 hover:bg-violet-700 rounded-lg transition-colors shadow-md cursor-pointer"
                                wire:loading.attr="disabled" wire:target="editPresentationImage, editPdfFile, updateBrochure">
                                <span wire:loading.remove wire:target="updateBrochure">Enregistrer</span>
                                <span wire:loading wire:target="updateBrochure">Enregistrement...</span>
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
                <div class="fixed inset-0 bg-black/50 dark:bg-black/70 transition-opacity" wire:click="closeReportModal"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-lg shadow-xl dark:shadow-zinc-950/50 max-w-lg w-full p-6 border border-gray-200 dark:border-zinc-700">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Détail du signalement</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Signalé le {{ $selectedReport->created_at->format('d/m/Y à H:i') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-zinc-700 rounded-lg mb-4">
                        <img src="{{ $selectedReport->image->thumbnail_path ? asset('storage/' . $selectedReport->image->thumbnail_path) : asset('storage/' . $selectedReport->image->path) }}"
                            class="w-16 h-20 object-cover rounded-lg" alt="">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $selectedReport->image->title ?? $selectedReport->image->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Par: <span class="font-medium">{{ $selectedReport->user->name }}</span></p>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Commentaire</label>
                        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                            <p class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $selectedReport->comment }}</p>
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Note de résolution (optionnel)</label>
                        <textarea wire:model="resolutionNote" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                            placeholder="Décrivez comment le problème a été résolu..."></textarea>
                    </div>
                    <div class="flex gap-3 justify-end">
                        <button type="button" wire:click="closeReportModal"
                            class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 hover:bg-gray-300 dark:hover:bg-zinc-600 rounded-lg transition-colors cursor-pointer">
                            Fermer
                        </button>
                        <button wire:click="resolveReport"
                            class="px-6 py-3 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors shadow-md cursor-pointer flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Marquer comme résolu
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
