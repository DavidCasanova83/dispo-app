{{-- Confirmation de clôture (une page ou plusieurs). Attend $closing (pages avec reviews_count). --}}
@php
    $archived = $closing->sum('reviews_count');
    $until = now()->addDays(\App\Models\VerificationPage::REVALIDATION_DAYS);
@endphp
<div class="fixed inset-0 z-50 overflow-y-auto" wire:key="close-modal">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" wire:click="cancelClose"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg p-6 border border-gray-200 dark:border-gray-700 flex flex-col gap-3">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                @if ($closing->count() === 1)
                    Clôturer « {{ $closing->first()->title }} » ?
                @else
                    Clôturer {{ $closing->count() }} pages ?
                @endif
            </h2>
            <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ $closing->count() > 1 ? 'Elles seront marquées' : 'Elle sera marquée' }}
                <strong>vérifiée{{ $closing->count() > 1 ? 's' : '' }} jusqu'au {{ $until->format('d/m/Y') }}</strong>,
                puis {{ $closing->count() > 1 ? 'repartiront' : 'repartira' }} automatiquement en relecture.
            </p>
            <div class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-3 text-sm text-green-900 dark:text-green-100">
                <p class="font-semibold">Rien n'est supprimé</p>
                <ul class="mt-1 list-disc list-inside space-y-0.5">
                    <li>{{ $archived }} relecture{{ $archived > 1 ? 's sont archivées' : ' est archivée' }} dans le cycle en cours.</li>
                    <li>Elles restent visibles dans la fiche de la page et dans l'historique des relecteurs.</li>
                    <li>Les relecteurs restent assignés pour le prochain cycle.</li>
                </ul>
            </div>
            @if ($closing->count() > 1)
                <details class="text-xs text-gray-600 dark:text-gray-400">
                    <summary class="cursor-pointer">Voir les pages</summary>
                    <ul class="mt-1 list-disc list-inside">
                        @foreach ($closing as $page)
                            <li>{{ $page->title }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" wire:click="cancelClose"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                    Annuler
                </button>
                <button type="button" wire:click="confirmClose" wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded-lg">
                    {{ $closing->count() > 1 ? 'Clôturer les '.$closing->count().' pages' : 'Clôturer la page' }}
                </button>
            </div>
        </div>
    </div>
</div>
