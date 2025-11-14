@props([
    'options' => [],
    'model' => '',
    'multiple' => false,
    'disabled' => false,
    'wireClick' => null
])

<div class="flex flex-wrap gap-2">
    @foreach($options as $option)
        <button
            type="button"
            @if($wireClick)
                wire:click="{{ $wireClick }}('{{ $option }}')"
            @endif
            @if($multiple)
                :class="(Array.isArray({{ $model }}) && {{ $model }}.includes('{{ $option }}')) ?
                    'bg-[#3E9B90] text-white shadow-md scale-105' :
                    'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600'"
            @else
                :class="{{ $model }} === '{{ $option }}' ?
                    'bg-[#3E9B90] text-white shadow-md scale-105' :
                    'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600'"
            @endif
            @if($disabled)
                :disabled="{{ $disabled }}"
                class="px-4 py-2 rounded-lg font-medium transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            @else
                class="px-4 py-2 rounded-lg font-medium transition-all duration-200 hover:shadow-md transform hover:scale-105 active:scale-95"
            @endif
        >
            {{ $option }}
        </button>
    @endforeach
</div>
