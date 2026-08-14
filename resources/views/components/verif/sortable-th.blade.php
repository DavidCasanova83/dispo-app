@props([
    'field',
    'active' => null,   // null | 'asc' | 'desc'
    'align' => 'left',
])

@php
    $isActive = $active !== null;
    $indicator = match ($active) {
        'asc' => '↑',
        'desc' => '↓',
        default => '⇅',
    };
    $justify = $align === 'right' ? 'justify-end' : 'justify-start';
@endphp

<th {{ $attributes->class(['px-3 py-2', $align === 'right' ? 'text-right' : 'text-left']) }}>
    <button type="button" wire:click="sortBy('{{ $field }}')"
        class="group inline-flex items-center gap-1 {{ $justify }} text-xs font-semibold uppercase tracking-wide transition-colors
            {{ $isActive
                ? 'text-gray-900 dark:text-white'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
        {{ $slot }}
        <span class="text-[10px] {{ $isActive ? 'opacity-100' : 'opacity-0 group-hover:opacity-60' }}">{{ $indicator }}</span>
    </button>
</th>
