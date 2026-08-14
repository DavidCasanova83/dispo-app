@props([
    'label',
    'count' => 0,
    'active' => false,
    'tone' => 'neutral',
    'alert' => false,   // met en avant la tuile quand elle contient quelque chose à traiter
])

@php
    $accents = [
        'neutral' => 'text-gray-900 dark:text-white',
        'warn' => 'text-amber-600 dark:text-amber-400',
        'info' => 'text-blue-600 dark:text-blue-400',
        'danger' => 'text-red-600 dark:text-red-400',
        'accent' => 'text-purple-600 dark:text-purple-400',
        'success' => 'text-green-600 dark:text-green-400',
    ];
    // Le surlignage d'alerte reprend la couleur de la tuile : rouge pour un
    // manque, violet pour une action en attente… plutôt qu'un rouge unique.
    $highlights = [
        'neutral' => 'border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-700/30',
        'warn' => 'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/20',
        'info' => 'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20',
        'danger' => 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/20',
        'accent' => 'border-purple-300 bg-purple-50 dark:border-purple-700 dark:bg-purple-900/20',
        'success' => 'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-900/20',
    ];
    $needsAttention = $alert && $count > 0;
@endphp

<button type="button" {{ $attributes->class([
    'rounded-lg border px-3 py-2.5 text-left transition-colors',
    'border-gray-900 bg-gray-900/[0.04] dark:border-white dark:bg-white/10' => $active,
    ($highlights[$tone] ?? $highlights['danger']) => ! $active && $needsAttention,
    'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700/50' => ! $active && ! $needsAttention,
]) }}>
    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $label }}</span>
    <span class="mt-0.5 block text-xl font-bold {{ $count > 0 ? ($accents[$tone] ?? $accents['neutral']) : 'text-gray-300 dark:text-gray-600' }}">
        {{ $count }}
    </span>
</button>
