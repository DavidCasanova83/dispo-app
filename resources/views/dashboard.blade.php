<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        {{-- Welcome Message --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                Bienvenue {{ auth()->user()->name }} !
            </h1>
        </div>

        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            {{-- Admin Module - Only for Super-admin --}}
            @can('manage-users')
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 transition-all duration-300">
                    <a href="{{ route('admin.users') }}"
                        class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                        <div class="text-4xl mb-2">👥</div>
                        <h3 class="text-lg font-semibold mb-2">Administration</h3>
                        <p class="text-sm opacity-90">Gestion des utilisateurs</p>
                    </a>
                </div>
            @endcan

            {{-- Qualification Module - For users with view-qualification permission --}}
            @can('view-qualification')
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 transition-all duration-300">
                    <a href="{{ route('qualification.index') }}"
                        class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                        <div class="text-4xl mb-2">📋</div>
                        <h3 class="text-lg font-semibold mb-2">Qualification</h3>
                        <p class="text-sm opacity-90">Outil de qualification</p>
                    </a>
                </div>
            @endcan

            {{-- Accommodations Module - For users with view-disponibilites permission --}}
            @can('view-disponibilites')
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br from-green-500 to-teal-600 hover:from-green-600 hover:to-teal-700 transition-all duration-300">
                    <a href="{{ route('accommodations') }}"
                        class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                        <div class="text-4xl mb-2">🏨</div>
                        <h3 class="text-lg font-semibold mb-2">Hébergements</h3>
                        <p class="text-sm opacity-90">Gérer les hébergements</p>
                    </a>
                </div>
            @endcan

            {{-- Forms Access - For users with fill-forms permission (base level) --}}
            @can('fill-forms')
                @cannot('view-qualification')
                    <div
                        class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 transition-all duration-300">
                        <a href="{{ route('qualification.index') }}"
                            class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                            <div class="text-4xl mb-2">📝</div>
                            <h3 class="text-lg font-semibold mb-2">Formulaires</h3>
                            <p class="text-sm opacity-90">Remplir les formulaires</p>
                        </a>
                    </div>
                @endcannot
            @endcan
        </div>


    </div>
</x-layouts.app>
