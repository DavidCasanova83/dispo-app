@props(['show' => false])

<div
    x-data="{ show: @entangle('showSuccessMessage') }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform -translate-y-2 scale-95"
    x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 transform -translate-y-2 scale-95"
    @success-message-shown.window="setTimeout(() => { show = false }, 5000)"
    class="mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 dark:border-green-400 rounded-lg shadow-md"
    style="display: none;"
>
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="ml-3 flex-1">
            <p class="text-green-800 dark:text-green-200 font-semibold">
                Qualification enregistrée avec succès !
            </p>
            <p class="text-green-700 dark:text-green-300 text-sm mt-1">
                Vous pouvez en saisir une nouvelle.
            </p>
        </div>
        <button
            @click="show = false"
            class="ml-4 flex-shrink-0 text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200 transition-colors"
        >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        </button>
    </div>
</div>
