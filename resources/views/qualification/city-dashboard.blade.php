<x-layouts.app :title="$cityName . ' - Qualification'">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <!-- Header -->
        <div class="mb-4">
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('qualification.index') }}"
                    class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    ← Retour
                </a>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $cityName }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Dashboard de qualification</p>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Total des entrées</div>
            </div>
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700">
                <div class="text-2xl font-bold text-green-600">{{ $stats['completed'] }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Entrées complètes</div>
            </div>
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700">
                <div class="text-2xl font-bold text-orange-600">{{ $stats['incomplete'] }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Entrées incomplètes</div>
            </div>
        </div>

        <!-- Actions -->
        <div class="grid auto-rows-min gap-4 md:grid-cols-2">
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 transition-all duration-300">
                <a href="{{ route('qualification.city.form', $city) }}"
                    class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                    <div class="text-4xl mb-2">📝</div>
                    <h3 class="text-lg font-semibold mb-2">Formulaire</h3>
                    <p class="text-sm opacity-90">Remplir un nouveau formulaire</p>
                </a>
            </div>

            @can('edit-qualification')
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 transition-all duration-300">
                <a href="{{ route('qualification.city.data', $city) }}"
                    class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                    <div class="text-4xl mb-2">📊</div>
                    <h3 class="text-lg font-semibold mb-2">Données</h3>
                    <p class="text-sm opacity-90">Consulter et éditer les données</p>
                </a>
            </div>
            @endcan
        </div>
    </div>
</x-layouts.app>
