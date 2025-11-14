@props([
    'currentStep' => 1,
    'totalSteps' => 3,
    'showPrevious' => true,
    'nextLabel' => 'Suivant',
    'previousLabel' => 'Précédent',
    'nextAction' => 'nextStep',
    'previousAction' => 'previousStep'
])

<div class="flex gap-4">
    @if($showPrevious && $currentStep > 1)
        <button
            type="button"
            wire:click="{{ $previousAction }}"
            class="flex-1 bg-gray-200 text-gray-900 hover:bg-gray-300 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 font-bold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-sm hover:shadow-md flex items-center justify-center gap-2"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            <span>{{ $previousLabel }}</span>
        </button>
    @endif

    <button
        type="button"
        wire:click="{{ $nextAction }}"
        wire:loading.attr="disabled"
        wire:loading.class="opacity-75 cursor-not-allowed"
        @class([
            'font-bold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-md hover:shadow-lg flex items-center justify-center gap-2',
            'flex-1' => $showPrevious && $currentStep > 1,
            'w-full' => !$showPrevious || $currentStep === 1,
            'bg-[#3E9B90] hover:bg-[#2E7B71] text-white' => true
        ])
    >
        <span wire:loading.remove wire:target="{{ $nextAction }}">
            {{ $nextLabel }}
        </span>
        <span wire:loading wire:target="{{ $nextAction }}" class="flex items-center gap-2">
            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Chargement...
        </span>
        <svg class="w-5 h-5" wire:loading.remove wire:target="{{ $nextAction }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </button>
</div>
