@props([
    // Ligne de VerificationPage::reviewerProgress()
    'row',
])

{{-- Pastille d'avancement d'un relecteur sur une page (colonne « Avancement »). --}}
@php
    $def = \App\Models\VerificationPage::REVIEWER_STATES[$row['state']];
    $icons = [
        'success' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'warn' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        'accent' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
        'neutral' => 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    ];
    $days = $row['since'] ? (int) $row['since']->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null;
    $detail = match ($row['state']) {
        'open' => $days !== null ? ($days === 0 ? 'auj.' : $days.' j') : null,
        'new' => 'nouveau retour',
        'back' => $days !== null ? 'renvoyée '.($days === 0 ? 'auj.' : 'il y a '.$days.' j') : 'renvoyée',
        default => null,
    };
    $title = $row['name'].' : '.$row['label']
        .($row['since'] ? ' depuis le '.$row['since']->format('d/m/Y') : '')
        .($row['assigned'] ? '' : ' (plus assigné à cette page)');
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-1 rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 pl-0.5 pr-2 py-0.5 text-[11px] text-gray-800 dark:text-gray-200 whitespace-nowrap',
    'opacity-60' => ! $row['assigned'],
]) }} title="{{ $title }}">
    <span class="inline-grid place-items-center w-4 h-4 rounded-full text-[10px] font-bold {{ $icons[$def['tone']] }}">{{ $def['icon'] }}</span>
    {{ $row['name'] }}
    @if ($detail)<span class="text-gray-400 dark:text-gray-500">{{ $detail }}</span>@endif
</span>
