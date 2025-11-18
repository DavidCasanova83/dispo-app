@props(['variant' => 'info', 'icon' => null])

@php
    $variantClasses = [
        'success' => 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-200',
        'danger' => 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-200',
        'warning' => 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-200',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-200',
    ];

    $iconClasses = [
        'success' => 'text-green-600 dark:text-green-400',
        'danger' => 'text-red-600 dark:text-red-400',
        'warning' => 'text-amber-600 dark:text-amber-400',
        'info' => 'text-blue-600 dark:text-blue-400',
    ];

    $classes = $variantClasses[$variant] ?? $variantClasses['info'];
    $iconClass = $iconClasses[$variant] ?? $iconClasses['info'];
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg border p-4 {$classes}"]) }}>
    <div class="flex items-start gap-3">
        @if ($icon)
            <flux:icon.{{ $icon }} class="size-5 shrink-0 {{ $iconClass }}" />
        @endif
        <div class="flex-1">
            {{ $slot }}
        </div>
    </div>
</div>
