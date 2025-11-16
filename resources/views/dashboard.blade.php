<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        {{-- Welcome Message --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                Bienvenue {{ auth()->user()->name }} !
            </h1>
        </div>

        <div class="grid auto-rows-min gap-4 md:grid-cols-3 max-w-4xl w-full mx-auto">
            {{-- Admin Module - Only for Super-admin --}}
            @can('manage-users')
                <div
                    class="group relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br from-purple-600 to-indigo-800 hover:from-purple-700 hover:to-indigo-900 transition-all duration-300">
                    <a href="{{ route('admin.users') }}"
                        class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                        <!-- Cadre intérieur animé -->
                        <div
                            class="absolute inset-3 border-2 border-white/30 rounded-lg transition-all duration-500 ease-out group-hover:inset-4 group-hover:border-white/60 group-hover:shadow-[0_0_20px_rgba(255,255,255,0.3)]">
                        </div>

                        <div class="text-4xl mb-2 relative z-10">👥</div>
                        <h3 class="text-lg font-semibold mb-2 relative z-10">Administration</h3>
                        <p class="text-sm opacity-90 relative z-10">Gestion des utilisateurs</p>
                    </a>
                </div>
            @endcan

            {{-- Qualification Module - For users with view-qualification permission --}}
            @can('view-qualification')
                <div
                    class="group relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br from-orange-600 to-red-800 hover:from-orange-700 hover:to-red-900 transition-all duration-300">
                    <a href="{{ route('qualification.index') }}"
                        class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                        <!-- Cadre intérieur animé -->
                        <div
                            class="absolute inset-3 border-2 border-white/30 rounded-lg transition-all duration-500 ease-out group-hover:inset-4 group-hover:border-white/60 group-hover:shadow-[0_0_20px_rgba(255,255,255,0.3)]">
                        </div>

                        <div class="text-4xl mb-2 relative z-10">📋</div>
                        <h3 class="text-lg font-semibold mb-2 relative z-10">Qualification</h3>
                        <p class="text-sm opacity-90 relative z-10">Outil de qualification</p>
                    </a>
                </div>
            @endcan

            {{-- Accommodations Module - For users with view-disponibilites permission --}}
            @can('view-disponibilites')
                <div
                    class="group relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br from-green-600 to-teal-800 hover:from-green-700 hover:to-teal-900 transition-all duration-300">
                    <a href="{{ route('accommodations') }}"
                        class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                        <!-- Cadre intérieur animé -->
                        <div
                            class="absolute inset-3 border-2 border-white/30 rounded-lg transition-all duration-500 ease-out group-hover:inset-4 group-hover:border-white/60 group-hover:shadow-[0_0_20px_rgba(255,255,255,0.3)]">
                        </div>

                        <div class="text-4xl mb-2 relative z-10">🏨</div>
                        <h3 class="text-lg font-semibold mb-2 relative z-10">Hébergements</h3>
                        <p class="text-sm opacity-90 relative z-10">Gérer les hébergements</p>
                    </a>
                </div>
            @endcan

            {{-- Forms Access - For users with fill-forms permission (base level) --}}
            @can('fill-forms')
                @cannot('view-qualification')
                    <div
                        class="group relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br from-blue-600 to-cyan-800 hover:from-blue-700 hover:to-cyan-900 transition-all duration-300">
                        <a href="{{ route('qualification.index') }}"
                            class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                            <!-- Cadre intérieur animé -->
                            <div
                                class="absolute inset-3 border-2 border-white/30 rounded-lg transition-all duration-500 ease-out group-hover:inset-4 group-hover:border-white/60 group-hover:shadow-[0_0_20px_rgba(255,255,255,0.3)]">
                            </div>

                            <div class="text-4xl mb-2 relative z-10">📝</div>
                            <h3 class="text-lg font-semibold mb-2 relative z-10">Formulaires</h3>
                            <p class="text-sm opacity-90 relative z-10">Remplir les formulaires</p>
                        </a>
                    </div>
                @endcannot
            @endcan
        </div>


    </div>
</x-layouts.app>
