@php
    $kpis = $statistics['kpis'];
    $yoy = $statistics['yoy'] ?? [];

    $cards = [
        [
            'label' => 'Total qualifications',
            'value' => number_format($kpis['total'], 0, ',', ' '),
            'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            'color' => 'teal',
            'yoyKey' => 'total',
        ],
        [
            'label' => 'Moyenne / jour',
            'value' => $kpis['avgPerDay'],
            'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            'color' => 'blue',
            'yoyKey' => 'avgPerDay',
        ],
        [
            'label' => '% visiteurs internationaux',
            'value' => $kpis['internationalPct'] . '%',
            'icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'color' => 'amber',
            'yoyKey' => 'internationalPct',
        ],
        [
            'label' => 'Profil dominant',
            'value' => $kpis['dominantProfile'],
            'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
            'color' => 'purple',
            'yoyKey' => null,
        ],
        [
            'label' => "Tranche d'âge dominante",
            'value' => $kpis['dominantAgeRange'],
            'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            'color' => 'rose',
            'yoyKey' => null,
        ],
    ];

    $iconBgClasses = [
        'teal' => 'bg-teal-100 dark:bg-teal-800/50 text-teal-600 dark:text-teal-400',
        'blue' => 'bg-blue-100 dark:bg-blue-800/50 text-blue-600 dark:text-blue-400',
        'amber' => 'bg-amber-100 dark:bg-amber-800/50 text-amber-600 dark:text-amber-400',
        'purple' => 'bg-purple-100 dark:bg-purple-800/50 text-purple-600 dark:text-purple-400',
        'rose' => 'bg-rose-100 dark:bg-rose-800/50 text-rose-600 dark:text-rose-400',
    ];

    $reliabilityColors = [
        'high' => 'bg-green-500',
        'good' => 'bg-green-400',
        'medium' => 'bg-yellow-400',
        'low' => 'bg-orange-400',
        'very_low' => 'bg-red-400',
    ];
@endphp

<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @foreach ($cards as $card)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 rounded-lg {{ $iconBgClasses[$card['color']] }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}" />
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white truncate">{{ $card['value'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $card['label'] }}</p>

            {{-- YoY comparison arrow --}}
            @if ($card['yoyKey'] && isset($yoy[$card['yoyKey']]) && $yoy[$card['yoyKey']]['pct'] !== null)
                @php $change = $yoy[$card['yoyKey']]; @endphp
                <div class="mt-2 flex items-center gap-1">
                    @if ($change['direction'] === 'up')
                        <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-xs font-medium text-green-600 dark:text-green-400">+{{ $change['pct'] }}%</span>
                    @elseif ($change['direction'] === 'down')
                        <svg class="w-3.5 h-3.5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-xs font-medium text-red-600 dark:text-red-400">{{ $change['pct'] }}%</span>
                    @endif
                    <span class="text-xs text-gray-400 dark:text-gray-500">vs N-1</span>
                </div>
            @endif
        </div>
    @endforeach
</div>

{{-- Reliability indicators (when "all cities" is selected) --}}
@if (!$isSingleCity && !empty($kpis['reliability']))
    <div class="flex flex-wrap gap-3 mb-6">
        @foreach ($kpis['reliability'] as $cityKey => $rel)
            <div class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-xs">
                <span class="w-2 h-2 rounded-full {{ $reliabilityColors[$rel['level']] }}"></span>
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $cities[$cityKey] ?? $cityKey }}</span>
                <span class="text-gray-400 dark:text-gray-500">{{ $rel['count'] }} qual.</span>
                <span class="text-gray-400 dark:text-gray-500">({{ $rel['label'] }})</span>
            </div>
        @endforeach
    </div>
@endif
