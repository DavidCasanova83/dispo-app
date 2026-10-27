@props([
    // Onglet actif : to_do | pages | verified | trash | page (fiche d'une page)
    'active' => 'to_do',
    // Étape mise en avant dans le bandeau (filtre de l'onglet Pages)
    'stage' => null,
])

@php
    $overview = app(\App\Services\VerificationOverviewService::class);
    $stageCounts = $overview->stageCounts();
    $tabCounts = $overview->tabCounts();
    $totalStages = max(1, array_sum($stageCounts));

    // Couleur de chaque étape dans le bandeau (mêmes rôles que les pastilles).
    $stageColors = [
        'unassigned' => 'bg-red-500',
        'queued' => 'bg-gray-300 dark:bg-gray-600',
        'reviewing' => 'bg-blue-500',
        'to_handle' => 'bg-amber-500',
        'correcting' => 'bg-sky-400',
        'ready' => 'bg-purple-500',
        'verified' => 'bg-green-600',
    ];

    $tabs = [
        'to_do' => ['label' => 'À traiter', 'route' => 'verification.admin.todo', 'count' => $tabCounts['to_do'], 'alert' => true],
        'pages' => ['label' => 'Pages', 'route' => 'verification.admin.pages', 'count' => $tabCounts['pages'], 'alert' => false],
        'verified' => ['label' => 'Vérifiées', 'route' => 'verification.admin.verified', 'count' => $tabCounts['verified'], 'alert' => false],
        'trash' => ['label' => 'Corbeille', 'route' => 'verification.admin.trash', 'count' => $tabCounts['trash'], 'alert' => false],
    ];
@endphp

<div class="flex flex-col gap-4">
    {{-- Titre et actions --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Vérification des pages</h1>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Relecture annuelle des pages de verdontourisme.com par vos collègues.
            </p>
        </div>
        @isset($actions)
            <div class="flex items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>

    {{-- Bandeau des étapes : où en sont toutes les pages suivies --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 flex flex-col gap-2">
        <div class="flex h-2 rounded-full overflow-hidden gap-0.5" aria-hidden="true">
            @foreach ($stageCounts as $code => $count)
                @if ($count > 0)
                    <span class="{{ $stageColors[$code] }}" style="width: {{ round($count / $totalStages * 100, 2) }}%"></span>
                @endif
            @endforeach
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-1">
            @foreach (\App\Models\VerificationPage::STAGES as $code => $def)
                <a href="{{ route('verification.admin.pages', ['etape' => $code]) }}" wire:navigate
                    @class([
                        'flex flex-col rounded-md px-2 py-1.5 border transition-colors',
                        'border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-900/40' => $stage === $code,
                        'border-transparent hover:bg-gray-50 dark:hover:bg-gray-700/40' => $stage !== $code,
                    ])
                    title="Afficher les pages : {{ $def['label'] }}">
                    <span class="flex items-center gap-1.5 text-lg font-bold tabular-nums text-gray-900 dark:text-white">
                        <span class="inline-block w-2 h-2 rounded-sm {{ $stageColors[$code] }}"></span>
                        {{ $stageCounts[$code] }}
                    </span>
                    <span class="text-xs leading-tight text-gray-500 dark:text-gray-400">{{ $def['short'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Onglets --}}
    <nav class="flex gap-1 border-b border-gray-200 dark:border-gray-700 overflow-x-auto" aria-label="Sections de la vérification">
        @foreach ($tabs as $code => $tab)
            <a href="{{ route($tab['route']) }}" wire:navigate
                @if ($active === $code) aria-current="page" @endif
                @class([
                    'inline-flex items-center gap-1.5 px-3 py-2 -mb-px text-sm font-medium border-b-2 whitespace-nowrap',
                    'border-gray-900 text-gray-900 dark:border-white dark:text-white' => $active === $code,
                    'border-transparent text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => $active !== $code,
                    'ml-auto' => $code === 'trash',
                ])>
                {{ $tab['label'] }}
                @if ($tab['count'] > 0 || $code !== 'trash')
                    <span @class([
                        'rounded-full px-1.5 text-[11px] leading-[17px] font-semibold tabular-nums',
                        'bg-red-600 text-white' => $tab['alert'] && $tab['count'] > 0,
                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' => ! ($tab['alert'] && $tab['count'] > 0),
                    ])>{{ $tab['count'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>
</div>
