@php
    $origin = $statistics['originBreakdown'] ?? null;

    $originTiles = $origin
        ? [
            ['label' => 'France', 'data' => $origin['france'], 'text' => 'text-teal-600 dark:text-teal-400', 'bar' => 'bg-teal-500'],
            ['label' => 'Europe (hors France)', 'data' => $origin['europe'], 'text' => 'text-blue-600 dark:text-blue-400', 'bar' => 'bg-blue-500'],
            ['label' => 'Reste du monde', 'data' => $origin['world'], 'text' => 'text-purple-600 dark:text-purple-400', 'bar' => 'bg-purple-500'],
        ]
        : [];
@endphp

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Origine détaillée</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                France / Europe / Reste du monde — puis part régionale sur le total des visiteurs français
            </p>
        </div>
    </div>

    @if ($origin && $origin['total'] > 0)
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach ($originTiles as $tile)
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $tile['label'] }}</p>
                    <p class="text-3xl font-bold mt-1 {{ $tile['text'] }}">{{ $tile['data']['pct'] }}%</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ number_format($tile['data']['count'], 0, ',', ' ') }} visiteur{{ $tile['data']['count'] > 1 ? 's' : '' }}
                    </p>
                    <div class="mt-3 h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                        <div class="h-full {{ $tile['bar'] }}" style="width: {{ min(100, $tile['data']['pct']) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Sur les visiteurs français ayant renseigné leur département
                ({{ number_format($origin['frenchWithDepartment'], 0, ',', ' ') }} sur {{ number_format($origin['france']['count'], 0, ',', ' ') }})
            </p>

            @if ($origin['frenchWithDepartment'] > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Région PACA</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">04, 05, 06, 13, 83, 84</p>
                        <p class="text-3xl font-bold mt-1 text-rose-600 dark:text-rose-400">{{ $origin['paca']['pct'] }}%</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ number_format($origin['paca']['count'], 0, ',', ' ') }} visiteur{{ $origin['paca']['count'] > 1 ? 's' : '' }}
                        </p>
                        <div class="mt-3 h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                            <div class="h-full bg-rose-500" style="width: {{ min(100, $origin['paca']['pct']) }}%"></div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Alpes-de-Haute-Provence</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Département 04</p>
                        <p class="text-3xl font-bold mt-1 text-orange-600 dark:text-orange-400">{{ $origin['ahp']['pct'] }}%</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ number_format($origin['ahp']['count'], 0, ',', ' ') }} visiteur{{ $origin['ahp']['count'] > 1 ? 's' : '' }}
                        </p>
                        <div class="mt-3 h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                            <div class="h-full bg-orange-500" style="width: {{ min(100, $origin['ahp']['pct']) }}%"></div>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-4">
                    Un visiteur peut cocher plusieurs départements : les deux parts se recoupent (le 04 est inclus dans PACA).
                </p>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Aucun département renseigné sur la période.</p>
            @endif
        </div>
    @else
        <p class="text-center text-gray-500 dark:text-gray-400 py-8">Aucune donnée</p>
    @endif
</div>
