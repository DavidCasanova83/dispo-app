@props(['color' => 'zinc', 'size' => 'md'])

@php
    $colorClasses = [
        'green' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        'red' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
        'zinc' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300',
    ];

    $sizeClasses = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-1 text-sm',
    ];

    $classes = ($colorClasses[$color] ?? $colorClasses['zinc']) . ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']);
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-md font-medium {$classes}"]) }}>
    {{ $slot }}
</span>
