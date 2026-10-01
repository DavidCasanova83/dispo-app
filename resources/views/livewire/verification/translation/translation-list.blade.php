<div class="flex h-full w-full flex-1 flex-col gap-4">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Traductions</h1>
        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
            Toutes les pages du site : indiquez pour chacune si les versions anglaise et italienne sont correctes.
        </p>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Tuiles = filtres sur l'état des traductions --}}
    <div class="grid gap-2 grid-cols-2 sm:grid-cols-3 lg:grid-cols-5">
        <x-verif.stat-filter label="Toutes les pages" :count="$stats['all']"
            :active="$filterStatus === 'all'" wire:click="applyStatusFilter('all')" />
        @foreach (\App\Models\TranslationCheck::FILTERS as $code => $def)
            <x-verif.stat-filter
                :label="$def['label']"
                :tone="$def['tone']"
                :alert="$code === 'to_check'"
                :count="$stats[$code]"
                :active="$filterStatus === $code"
                wire:click="applyStatusFilter('{{ $code }}')" />
        @endforeach
    </div>

    {{-- Filtres secondaires --}}
    <div class="flex flex-col lg:flex-row lg:items-center gap-2">
        <div class="relative flex-1">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher un titre, une URL…"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 pl-9 pr-3 py-2 text-sm text-gray-900 dark:text-white">
            <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"></path>
            </svg>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <select wire:model.live="filterLanguage"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                <option value="all">EN et IT</option>
                @foreach (\App\Models\TranslationCheck::LANGUAGES as $code => $lang)
                    <option value="{{ $code }}">{{ $lang['flag'] }} {{ $lang['label'] }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterCategory"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                <option value="">Toutes les catégories</option>
                @foreach (\App\Models\VerificationPage::CATEGORIES as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
            </select>
            @if ($filterStatus !== 'all' || $filterLanguage !== 'all' || $filterCategory !== '' || $search !== '')
                <button type="button" wire:click="resetFilters"
                    class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white underline underline-offset-2">
                    Tout afficher
                </button>
            @endif
        </div>
    </div>

    {{-- Tableau --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Page</th>
                        @foreach ($languages as $code)
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ \App\Models\TranslationCheck::LANGUAGES[$code]['flag'] }} {{ \App\Models\TranslationCheck::LANGUAGES[$code]['short'] }}
                            </th>
                        @endforeach
                        <th class="px-3 py-2"><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse ($pages as $page)
                        <tr wire:key="translation-page-{{ $page->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-3 py-2 align-top max-w-md">
                                <a href="{{ route('verification.translations.form', $page) }}" wire:navigate
                                    class="text-sm font-medium text-gray-900 dark:text-white hover:underline">{{ $page->title }}</a>
                                <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    @if ($page->categoryLabel())
                                        <span>{{ $page->categoryLabel() }}</span>
                                    @endif
                                    <a href="{{ $page->url }}" target="_blank" rel="noopener noreferrer"
                                        class="truncate max-w-[20rem] hover:underline">🇫🇷 {{ \Illuminate\Support\Str::after($page->url, '://') }} ↗</a>
                                </div>
                            </td>
                            @foreach ($languages as $code)
                                @php $check = $page->translationCheckFor($code); @endphp
                                <td class="px-3 py-2 align-top">
                                    <x-verif.status-badge size="xs"
                                        :tone="\App\Models\TranslationCheck::statusToneFor($check)"
                                        :label="\App\Models\TranslationCheck::statusLabelFor($check)" />
                                    @if (! $page->urlForLanguage($code))
                                        <p class="mt-0.5 text-[11px] text-gray-400 dark:text-gray-500">URL à renseigner</p>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-3 py-2 align-top text-right">
                                <a href="{{ route('verification.translations.form', $page) }}" wire:navigate
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">
                                    Vérifier →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($languages) + 2 }}" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                @if ($filterStatus === 'to_check' && $search === '' && $filterCategory === '')
                                    ✨ Toutes les traductions ont été vérifiées. Merci !
                                @else
                                    Aucune page ne correspond à ces filtres.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $pages->links() }}</div>
</div>
