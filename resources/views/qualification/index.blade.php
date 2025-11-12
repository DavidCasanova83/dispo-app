<x-layouts.app :title="__('Qualification')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="mb-4">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Outil de Qualification</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Sélectionnez une ville pour commencer</p>
        </div>

        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            @php
                $cityColors = [
                    'annot' => 'from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700',
                    'colmars-les-alpes' => 'from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700',
                    'entrevaux' => 'from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700',
                    'la-palud-sur-verdon' => 'from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700',
                    'saint-andre-les-alpes' => 'from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700',
                ];
            @endphp

            @foreach ($cities as $slug => $name)
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br {{ $cityColors[$slug] }} transition-all duration-300">
                    <a href="{{ route('qualification.city.dashboard', $slug) }}"
                        class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                        <div class="text-4xl mb-2">🏔️</div>
                        <h3 class="text-lg font-semibold mb-2">{{ $name }}</h3>
                        <p class="text-sm opacity-90">Accéder au formulaire</p>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>
