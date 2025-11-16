<x-layouts.app :title="__('Qualification')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-8 h-8 text-[#3E9B90]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Sélectionnez un bureau d'accueil
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Cliquez sur une destination pour accéder au formulaire
                </p>
            </div>
            <a href="{{ route('qualification.statistics') }}"
                class="px-6 py-3 bg-gradient-to-r from-[#3E9B90] to-[#2E7B70] hover:from-[#2E7B70] hover:to-[#1E6B60] text-white font-semibold rounded-lg shadow-md transition-all duration-300 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                    </path>
                </svg>
                Statistiques
            </a>
        </div>

        <div class="grid auto-rows-min gap-10 md:grid-cols-3 max-w-4xl w-full mx-auto">
            @php
                $cityColors = [
                    'annot' => 'from-teal-400 to-cyan-600 hover:from-teal-500 hover:to-cyan-700',
                    'colmars-les-alpes' => 'from-amber-700 to-stone-800 hover:from-amber-800 hover:to-stone-900',
                    'entrevaux' => 'from-cyan-700 to-slate-800 hover:from-cyan-800 hover:to-slate-900',
                    'la-palud-sur-verdon' =>
                        'from-purple-600 to-fuchsia-800 hover:from-purple-700 hover:to-fuchsia-900',
                    'saint-andre-les-alpes' => 'from-rose-800 to-red-950 hover:from-rose-900 hover:to-black',
                ];
            @endphp

            @foreach ($cities as $slug => $name)
                <div
                    class="group relative aspect-[4/3] overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br {{ $cityColors[$slug] }} transition-all duration-300">
                    <a href="{{ route('qualification.city.dashboard', $slug) }}"
                        class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                        <!-- Cadre intérieur animé -->
                        <div
                            class="absolute inset-3 border-2 border-white/30 rounded-lg transition-all duration-500 ease-out group-hover:inset-4 group-hover:border-white/60 group-hover:shadow-[0_0_20px_rgba(255,255,255,0.3)]">
                        </div>

                        <h3 class="text-lg font-semibold mb-2 relative z-10">{{ $name }}</h3>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>
