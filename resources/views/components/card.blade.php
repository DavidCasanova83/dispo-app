@props(['class' => ''])

<div {{ $attributes->merge(['class' => "bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800 shadow-sm {$class}"]) }}>
    {{ $slot }}
</div>
