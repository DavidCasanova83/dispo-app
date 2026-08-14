@props(['priority' => 'medium'])

@php
    $map = [
        'high' => ['bg-red-500', 'Priorité haute'],
        'medium' => ['bg-amber-500', 'Priorité moyenne'],
        'low' => ['bg-green-500', 'Priorité basse'],
    ];
    [$dot, $title] = $map[$priority] ?? $map['medium'];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5']) }} title="{{ $title }}">
    <span class="h-2.5 w-2.5 rounded-full {{ $dot }}"></span>
    <span class="sr-only">{{ $title }}</span>
</span>
