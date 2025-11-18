@props(['label' => null, 'description' => null, 'error' => null, 'name' => null])

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-sm font-medium text-zinc-900 dark:text-white">
            {{ $label }}
        </label>
    @endif

    @if ($description)
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ $description }}
        </p>
    @endif

    {{ $slot }}

    @if ($error)
        <p class="text-sm text-red-600 dark:text-red-400">
            {{ $error }}
        </p>
    @endif
</div>
