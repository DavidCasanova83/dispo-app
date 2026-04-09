{{-- Notifications sticky en haut du viewport (succès + erreurs de validation) --}}
<div
    class="fixed top-3 left-1/2 -translate-x-1/2 z-50 w-[calc(100%-1.5rem)] max-w-2xl pointer-events-none space-y-2"
>
    {{-- Message de succès --}}
    <div
        x-data="{ show: @entangle('showSuccessMessage') }"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform -translate-y-3 scale-95"
        x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 transform -translate-y-3 scale-95"
        @success-message-shown.window="setTimeout(() => { show = false }, 5000)"
        class="pointer-events-auto p-3 bg-green-600 dark:bg-green-500 border-l-4 border-green-800 dark:border-green-300 rounded-lg shadow-2xl ring-1 ring-green-900/20"
        style="display: none;"
    >
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-white text-sm font-bold">
                    Qualification enregistrée avec succès !
                </p>
                <p class="text-green-50 text-xs">
                    Vous pouvez en saisir une nouvelle.
                </p>
            </div>
            <button
                type="button"
                @click="show = false"
                class="ml-3 flex-shrink-0 text-white/90 hover:text-white hover:bg-white/20 rounded-full p-1 transition-colors"
                aria-label="Fermer"
            >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Erreurs de validation --}}
    @if ($errors->any())
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => { show = false }, 8000)"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform -translate-y-3 scale-95"
            x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 transform -translate-y-3 scale-95"
            class="pointer-events-auto p-3 bg-red-600 dark:bg-red-500 border-l-4 border-red-800 dark:border-red-300 rounded-lg shadow-2xl ring-1 ring-red-900/20"
        >
            <div class="flex items-start">
                <div class="flex-shrink-0 mt-0.5">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-white text-sm font-bold">
                        @if ($errors->count() === 1)
                            Veuillez corriger l'erreur suivante :
                        @else
                            Veuillez corriger les {{ $errors->count() }} erreurs suivantes :
                        @endif
                    </p>
                    <ul class="mt-1 text-xs text-red-50 list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button
                    type="button"
                    @click="show = false"
                    class="ml-3 flex-shrink-0 text-white/90 hover:text-white hover:bg-white/20 rounded-full p-1 transition-colors"
                    aria-label="Fermer"
                >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif
</div>
