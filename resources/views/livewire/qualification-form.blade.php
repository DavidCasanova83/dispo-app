<div class="flex min-h-screen w-full flex-col items-center justify-center px-3 py-3 sm:py-4" x-data="{ $currentStep: @entangle('currentStep') }">
    <!-- Notifications sticky en haut du viewport (succès + erreurs) -->
    <x-qualification.notifications />

    <!-- Header -->
    <div class="mb-2 sm:mb-3">
        <h1 class="text-xl sm:text-2xl text-center font-bold text-gray-900 dark:text-white">
            Formulaire {{ $cityName }}
        </h1>
    </div>

    <!-- Formulaire Multi-étapes -->
    <div class="max-w-5xl mx-auto w-full">
        <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-4 sm:p-5 transition-all duration-500 ease-in-out">
            <!-- Indicateur d'étapes -->
            <x-qualification.step-indicator :currentStep="$currentStep" />

            <!-- Étape 1 : Informations générales -->
            @include('livewire.qualification.step-one')

            <!-- Étape 2 : Profil -->
            @include('livewire.qualification.step-two')

            <!-- Étape 3 : Demandes -->
            @include('livewire.qualification.step-three')
        </div>
    </div>
</div>
