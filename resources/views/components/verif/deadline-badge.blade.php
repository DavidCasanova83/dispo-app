@props(['page'])

@php
    $days = $page->daysToDeadline();
@endphp

@if (! $page->deadline)
    <span class="text-xs text-gray-400 dark:text-gray-600">—</span>
@else
    <div class="flex flex-col gap-0.5">
        <span class="text-xs text-gray-700 dark:text-gray-300">{{ $page->deadline->format('d/m/Y') }}</span>
        @if ($days !== null)
            @if ($days < 0)
                <x-verif.status-badge size="xs" tone="danger" :label="'⚠ +' . abs($days) . ' j'" />
            @elseif ($days <= 7)
                <x-verif.status-badge size="xs" tone="warn" :label="'J-' . $days" />
            @endif
        @endif
    </div>
@endif
