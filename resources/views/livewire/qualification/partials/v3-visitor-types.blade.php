@php
    $visitorTypes = $statistics['visitorTypes'] ?? ['total' => 0, 'legacyDefaulted' => 0, 'items' => []];

    $typeStyles = [
        'Habitant' => ['bar' => 'bg-emerald-500', 'text' => 'text-emerald-600 dark:text-emerald-400'],
        'Socio Pro' => ['bar' => 'bg-blue-500', 'text' => 'text-blue-600 dark:text-blue-400'],
        'Touriste' => ['bar' => 'bg-amber-500', 'text' => 'text-amber-600 dark:text-amber-400'],
    ];
    $defaultStyle = ['bar' => 'bg-gray-400', 'text' => 'text-gray-600 dark:text-gray-400'];
@endphp

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Type de visiteur</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Répartition en % sur {{ number_format($visitorTypes['total'], 0, ',', ' ') }} qualification{{ $visitorTypes['total'] > 1 ? 's' : '' }}
            </p>
        </div>
    </div>

    @if ($visitorTypes['total'] > 0)
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach ($visitorTypes['items'] as $item)
                @php $style = $typeStyles[$item['label']] ?? $defaultStyle; @endphp
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $item['label'] }}</p>
                    <p class="text-3xl font-bold mt-1 {{ $style['text'] }}">{{ $item['pct'] }}%</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ number_format($item['count'], 0, ',', ' ') }} qualification{{ $item['count'] > 1 ? 's' : '' }}
                    </p>
                    <div class="mt-3 h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                        <div class="h-full {{ $style['bar'] }}" style="width: {{ min(100, $item['pct']) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($visitorTypes['legacyDefaulted'] > 0)
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-4">
                {{ number_format($visitorTypes['legacyDefaulted'], 0, ',', ' ') }} qualification{{ $visitorTypes['legacyDefaulted'] > 1 ? 's' : '' }}
                sans type renseigné (anciens formulaires) {{ $visitorTypes['legacyDefaulted'] > 1 ? 'sont comptées' : 'est comptée' }} en « Touriste », comme dans l'export.
            </p>
        @endif
    @else
        <p class="text-center text-gray-500 dark:text-gray-400 py-8">Aucune donnée</p>
    @endif
</div>
