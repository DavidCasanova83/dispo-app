@props([
    'label' => '',
    'tone' => 'neutral',
    'size' => 'sm',
])

@php
    // Palette unique du module vérification : 6 rôles, pas une couleur par écran.
    $tones = [
        'neutral' => 'bg-gray-100 text-gray-700 dark:bg-gray-700/60 dark:text-gray-300',
        'warn' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
        'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        'danger' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        'accent' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
        'success' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    ];
    $sizes = [
        'xs' => 'px-1.5 py-0.5 text-[11px]',
        'sm' => 'px-2 py-0.5 text-xs',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex items-center rounded font-medium whitespace-nowrap',
    $tones[$tone] ?? $tones['neutral'],
    $sizes[$size] ?? $sizes['sm'],
]) }}>
    {{ $label !== '' ? $label : $slot }}
</span>
