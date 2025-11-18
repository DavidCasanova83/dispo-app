@props([
    'variant' => 'secondary',
    'size' => 'md',
    'icon' => null,
    'type' => 'button',
    'disabled' => false,
])

@php
    $variantClasses = [
        'primary' => 'bg-accent text-accent-foreground hover:bg-accent/90 border-transparent',
        'secondary' => 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white border-zinc-300 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700',
        'ghost' => 'bg-transparent border-transparent text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 border-transparent',
    ];

    $sizeClasses = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-6 py-3 text-base',
    ];

    $classes = ($variantClasses[$variant] ?? $variantClasses['secondary']) . ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']);
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 rounded-lg border font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none {$classes}"]) }}
    @if ($disabled) disabled @endif
>
    @if ($icon)
        <flux:icon.{{ $icon }} class="size-4" />
    @endif
    {{ $slot }}
</button>
