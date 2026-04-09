@props(['currentStep' => 1])

<div class="mb-4">
    <div class="flex items-center">
        @foreach ([['num' => 1, 'label' => 'Informations'], ['num' => 2, 'label' => 'Profil'], ['num' => 3, 'label' => 'Demandes']] as $step)
            {{-- Step circle + label --}}
            <div class="flex flex-col items-center relative" style="z-index: 1;">
                {{-- Circle --}}
                <div class="flex items-center justify-center w-8 h-8 rounded-full border-2 transition-all duration-500 shadow-sm"
                    :class="{
                        'border-[#3E9B90] bg-[#3E9B90] text-white shadow-md': $currentStep > {{ $step['num'] }},
                        'border-[#3E9B90] bg-white dark:bg-gray-900 text-[#3E9B90] ring-4 ring-[#3E9B90]/20 shadow-md': $currentStep === {{ $step['num'] }},
                        'border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500': $currentStep < {{ $step['num'] }}
                    }">
                    <template x-if="$currentStep > {{ $step['num'] }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                    <template x-if="$currentStep <= {{ $step['num'] }}">
                        <span class="text-xs font-bold">{{ $step['num'] }}</span>
                    </template>
                </div>

                {{-- Label --}}
                <span class="mt-1 text-[11px] font-semibold tracking-wide transition-all duration-300 whitespace-nowrap"
                    :class="{
                        'text-[#3E9B90]': $currentStep >= {{ $step['num'] }},
                        'text-gray-400 dark:text-gray-500': $currentStep < {{ $step['num'] }}
                    }">
                    {{ $step['label'] }}
                </span>
            </div>

            {{-- Connector line (not after the last step) --}}
            @if (!$loop->last)
                <div class="flex-1 h-0.5 mx-1 -mt-4 rounded-full transition-all duration-500"
                    :class="$currentStep > {{ $step['num'] }} ? 'bg-[#3E9B90]' : 'bg-gray-200 dark:bg-gray-700'">
                </div>
            @endif
        @endforeach
    </div>
</div>
