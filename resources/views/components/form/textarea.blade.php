@props([
    'label' => null,
    'description' => null,
    'rows' => 4,
    'required' => false,
])

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

    <textarea
        rows="{{ $rows }}"
        {{ $attributes->merge(['class' => 'w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder:text-zinc-400 dark:placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-accent-foreground disabled:opacity-50']) }}
        @if ($required) required @endif
    >{{ $slot }}</textarea>

    @error($attributes->get('wire:model') ?? $attributes->get('name'))
        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
