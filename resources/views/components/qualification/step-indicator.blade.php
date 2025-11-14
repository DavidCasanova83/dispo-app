@props(['currentStep' => 1])

<div class="mb-8">
    <div class="flex items-center justify-between">
        <!-- Étape 1 -->
        <div class="flex-1">
            <div class="flex items-center">
                <div class="flex items-center text-sm"
                    :class="$currentStep >= 1 ? 'text-[#3E9B90]' : 'text-gray-500 dark:text-gray-400'">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full border-2 transition-all duration-300"
                        :class="$currentStep >= 1 ? 'border-[#3E9B90] bg-[#3E9B90] text-white scale-110' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-400'">
                        <span x-show="$currentStep > 1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                        <span x-show="$currentStep <= 1">1</span>
                    </div>
                    <span class="ml-2 hidden md:inline font-medium transition-all duration-300">Informations</span>
                </div>
                <div class="flex-1 h-1 mx-2 transition-all duration-500"
                    :class="$currentStep >= 2 ? 'bg-[#3E9B90]' : 'bg-gray-300 dark:bg-gray-600'">
                </div>
            </div>
        </div>

        <!-- Étape 2 -->
        <div class="flex-1">
            <div class="flex items-center">
                <div class="flex items-center text-sm"
                    :class="$currentStep >= 2 ? 'text-[#3E9B90]' : 'text-gray-500 dark:text-gray-400'">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full border-2 transition-all duration-300"
                        :class="$currentStep >= 2 ? 'border-[#3E9B90] bg-[#3E9B90] text-white scale-110' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-400'">
                        <span x-show="$currentStep > 2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                        <span x-show="$currentStep <= 2">2</span>
                    </div>
                    <span class="ml-2 hidden md:inline font-medium transition-all duration-300">Profil</span>
                </div>
                <div class="flex-1 h-1 mx-2 transition-all duration-500"
                    :class="$currentStep >= 3 ? 'bg-[#3E9B90]' : 'bg-gray-300 dark:bg-gray-600'">
                </div>
            </div>
        </div>

        <!-- Étape 3 -->
        <div class="flex-1">
            <div class="flex items-center justify-end">
                <div class="flex items-center text-sm"
                    :class="$currentStep >= 3 ? 'text-[#3E9B90]' : 'text-gray-500 dark:text-gray-400'">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full border-2 transition-all duration-300"
                        :class="$currentStep >= 3 ? 'border-[#3E9B90] bg-[#3E9B90] text-white scale-110' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-400'">
                        3
                    </div>
                    <span class="ml-2 hidden md:inline font-medium transition-all duration-300">Demandes</span>
                </div>
            </div>
        </div>
    </div>
</div>
