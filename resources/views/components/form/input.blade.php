@props([
    'type' => 'text',
    'label' => null,
    'description' => null,
    'icon' => null,
    'required' => false,
])

@php
    $inputClasses = 'w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder:text-zinc-400 dark:placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-accent-foreground disabled:opacity-50';

    if ($icon) {
        $inputClasses .= ' pl-10';
    }
@endphp

<div class="space-y-2">
    @if ($label)
        <label class="block text-sm font-medium text-zinc-900 dark:text-white">
            {{ $label }}
            @if ($required)
                <span class="text-red-600">*</span>
            @endif
        </label>
    @endif

    @if ($description)
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ $description }}
        </p>
    @endif

    <div class="relative">
        @if ($icon)
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <flux:icon.{{ $icon }} class="size-5 text-zinc-400" />
            </div>
        @endif

        <input
            type="{{ $type }}"
            {{ $attributes->merge(['class' => $inputClasses]) }}
            @if ($required) required @endif
        >
    </div>

    @error($attributes->get('wire:model') ?? $attributes->get('name'))
        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
