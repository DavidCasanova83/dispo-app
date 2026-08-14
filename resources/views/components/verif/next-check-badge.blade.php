@props(['page'])

@php
    $next = $page->nextVerificationAt();
    $days = $page->daysUntilNextVerification();
@endphp

@if ($next)
    @php
        // Le cron de renouvellement tourne chaque nuit : au-delà de l'échéance,
        // la page est simplement en attente de son prochain passage.
        [$tone, $text] = match (true) {
            $days <= 0 => ['warn', 'Renouvellement imminent'],
            $days <= 30 => ['warn', 'Dans ' . $days . ' j'],
            default => ['success', 'Dans ' . (int) floor($days / 30) . ' mois'],
        };
    @endphp
    <div class="flex flex-col gap-0.5">
        <x-verif.status-badge size="xs" :tone="$tone" :label="$text" />
        <span class="text-[11px] text-gray-400 dark:text-gray-500" title="Date de la prochaine vérification">
            {{ $next->format('d/m/Y') }}
        </span>
    </div>
@else
    <span class="text-xs text-gray-400 dark:text-gray-600">—</span>
@endif
