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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-2">
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
        <div class="grid auto-rows-min gap-4 md:grid-cols-3 max-w-4xl w-full mx-auto">
            <div
                class="group relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br from-blue-600 to-indigo-800 hover:from-blue-700 hover:to-indigo-900 transition-all duration-300">
                <a href="{{ route('qualification.city.form', $city) }}"
                    class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                    <!-- Cadre intérieur animé -->
                    <div
                        class="absolute inset-3 border-2 border-white/30 rounded-lg transition-all duration-500 ease-out group-hover:inset-4 group-hover:border-white/60 group-hover:shadow-[0_0_20px_rgba(255,255,255,0.3)]">
                    </div>

                    <div class="text-4xl mb-2 relative z-10">📝</div>
                    <h3 class="text-lg font-semibold mb-2 relative z-10">Formulaire</h3>
                    <p class="text-sm opacity-90 relative z-10">Remplir un nouveau formulaire</p>
                </a>
            </div>

            @can('edit-qualification')
                <div
                    class="group relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br from-emerald-600 to-teal-800 hover:from-emerald-700 hover:to-teal-900 transition-all duration-300">
                    <a href="{{ route('qualification.city.data', $city) }}"
                        class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                        <!-- Cadre intérieur animé -->
                        <div
                            class="absolute inset-3 border-2 border-white/30 rounded-lg transition-all duration-500 ease-out group-hover:inset-4 group-hover:border-white/60 group-hover:shadow-[0_0_20px_rgba(255,255,255,0.3)]">
                        </div>

                        <div class="text-4xl mb-2 relative z-10">📊</div>
                        <h3 class="text-lg font-semibold mb-2 relative z-10">Données</h3>
                        <p class="text-sm opacity-90 relative z-10">Consulter et éditer les données</p>
                    </a>
                </div>
            @endcan

            <div
                class="group relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br from-purple-600 to-fuchsia-800 hover:from-purple-700 hover:to-fuchsia-900 transition-all duration-300">
                <a href="{{ route('qualification.statistics') }}"
                    class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                    <!-- Cadre intérieur animé -->
                    <div
                        class="absolute inset-3 border-2 border-white/30 rounded-lg transition-all duration-500 ease-out group-hover:inset-4 group-hover:border-white/60 group-hover:shadow-[0_0_20px_rgba(255,255,255,0.3)]">
                    </div>

                    <div class="text-4xl mb-2 relative z-10">📈</div>
                    <h3 class="text-lg font-semibold mb-2 relative z-10">Statistiques</h3>
                    <p class="text-sm opacity-90 relative z-10">Analyse des données globales</p>
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
