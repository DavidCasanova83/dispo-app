@props(['label' => null])

<label class="inline-flex items-center gap-3 cursor-pointer">
    <div class="relative">
        <input
            type="checkbox"
            {{ $attributes->merge(['class' => 'peer sr-only']) }}
        >
        <div class="h-6 w-11 rounded-full bg-zinc-200 dark:bg-zinc-700 peer-checked:bg-accent transition-colors"></div>
        <div class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-5"></div>
    </div>

    @if ($label)
        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $label }}</span>
    @endif
</label>
