<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl" x-data="{ $currentStep: @entangle('currentStep') }">
    <!-- Header -->
    <div class="mb-4">
        <div class="flex items-center gap-2 mb-2"></div>
        <h1 class="text-3xl text-center font-bold text-gray-900 dark:text-white">
            Formulaire {{ $cityName }}
        </h1>
    </div>

    <!-- Formulaire Multi-étapes -->
    <div class="max-w-4xl mx-auto w-full">
        <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-6 min-h-[500px] transition-all duration-500 ease-in-out">
            <!-- Indicateur d'étapes -->
            <x-qualification.step-indicator :currentStep="$currentStep" />

            <!-- Message de succès -->
            <x-qualification.success-message />

            <!-- Étape 1 : Informations générales -->
            @include('livewire.qualification.step-one')

            <!-- Étape 2 : Profil -->
            @include('livewire.qualification.step-two')

            <!-- Étape 3 : Demandes -->
            @include('livewire.qualification.step-three')
        </div>
    </div>
</div>
