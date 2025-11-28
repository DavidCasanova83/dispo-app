<div class="min-h-screen bg-gray-50 dark:bg-zinc-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Nos brochures</h1>
            <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
                Consultez et téléchargez nos brochures touristiques
            </p>
        </div>

        {{-- Liste des brochures --}}
        <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg overflow-hidden">
            @if ($brochures->count() > 0)
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($brochures as $brochure)
                        <li class="p-4 sm:p-6 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <div class="flex items-center gap-4">
                                {{-- Thumbnail --}}
                                <div class="flex-shrink-0">
                                    <img src="{{ $brochure->thumbnail_path ? asset('storage/' . $brochure->thumbnail_path) : asset('storage/' . $brochure->path) }}"
                                        alt="{{ $brochure->alt_text ?? $brochure->title ?? $brochure->name }}"
                                        class="w-16 h-20 sm:w-20 sm:h-24 object-cover rounded-lg shadow-md">
                                </div>

                                {{-- Titre et description --}}
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                                        {{ $brochure->title ?? $brochure->name }}
                                    </h3>
                                    @if ($brochure->description)
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                            {{ $brochure->description }}
                                        </p>
                                    @endif
                                    @if ($brochure->edition_year)
                                        <span class="inline-block mt-2 text-xs text-gray-500 dark:text-gray-500 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                            Edition {{ $brochure->edition_year }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Liens (icônes) --}}
                                <div class="flex-shrink-0 flex items-center gap-3">
                                    {{-- Lien PDF --}}
                                    @if ($brochure->link_url)
                                        <a href="{{ $brochure->link_url }}" target="_blank" rel="noopener noreferrer"
                                            class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                                            title="{{ $brochure->link_text ?? 'Télécharger le PDF' }}">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 2l5 5h-5V4zM8.5 13.5a1 1 0 0 1 1-1h.5v3h-.5a1 1 0 0 1-1-1v-1zm3 0c0-.55.45-1 1-1h.5c.55 0 1 .45 1 1v1c0 .55-.45 1-1 1H12v1h1.5v1H12c-.55 0-1-.45-1-1v-3zm4 0c0-.55.45-1 1-1h1.5v1H17v.5h.5c.55 0 1 .45 1 1v.5c0 .55-.45 1-1 1H16v-1h1v-.5h-.5c-.55 0-1-.45-1-1v-.5z"/>
                                            </svg>
                                        </a>
                                    @endif

                                    {{-- Lien Calameo --}}
                                    @if ($brochure->calameo_link_url)
                                        <a href="{{ $brochure->calameo_link_url }}" target="_blank" rel="noopener noreferrer"
                                            class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 hover:bg-orange-200 dark:hover:bg-orange-900/50 transition-colors"
                                            title="{{ $brochure->calameo_link_text ?? 'Voir sur Calameo' }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                                                </path>
                                            </svg>
                                        </a>
                                    @endif

                                    {{-- Si aucun lien disponible --}}
                                    @if (!$brochure->link_url && !$brochure->calameo_link_url)
                                        <span class="text-sm text-gray-400 dark:text-gray-500 italic">
                                            Bientôt disponible
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                        </path>
                    </svg>
                    <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">Aucune brochure disponible</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Revenez bientôt pour découvrir nos brochures</p>
                </div>
            @endif
        </div>

        {{-- Lien vers commande --}}
        <div class="mt-8 text-center">
            <a href="{{ url('/commander-images') }}"
                class="inline-flex items-center gap-2 px-6 py-3 bg-[#3E9B90] hover:bg-[#2d7a72] text-white font-semibold rounded-lg transition-colors shadow-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z">
                    </path>
                </svg>
                Commander des brochures
            </a>
        </div>
    </div>
</div>
