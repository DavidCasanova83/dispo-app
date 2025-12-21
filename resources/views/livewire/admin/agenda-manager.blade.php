<div class="min-h-screen bg-gray-50 dark:bg-zinc-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Gestion des Agendas</h1>
            <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
                Uploadez et gérez les agendas de l'application
            </p>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-6 rounded-lg bg-gradient-to-r from-green-500 to-green-600 p-6 shadow-lg">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="flex-1 text-white font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-lg bg-gradient-to-r from-red-500 to-red-600 p-6 shadow-lg">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                    <p class="flex-1 text-white font-medium">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        {{-- Section 1: Ajouter un nouvel agenda --}}
        <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-8 mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                Ajouter un agenda
            </h2>

            @if ($pendingAgenda)
                {{-- Message si un agenda est déjà en attente --}}
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="text-sm text-blue-800 dark:text-blue-200 font-medium">
                                Un agenda est déjà en attente d'activation
                            </p>
                            <p class="text-sm text-blue-600 dark:text-blue-300 mt-1">
                                Vous devez supprimer l'agenda en attente ci-dessous avant d'en ajouter un nouveau.
                            </p>
                        </div>
                    </div>
                </div>
            @else
                {{-- Info sur le comportement --}}
                <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        L'agenda sera mis en attente et activé automatiquement à la date de début renseignée.
                    </p>
                </div>

                <form wire:submit.prevent="uploadAgenda">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- PDF --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Fichier PDF *
                            </label>
                            <input type="file" wire:model="pdfFile" accept=".pdf,application/pdf"
                                class="w-full text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none px-4 py-3">
                            @error('pdfFile')
                                <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span>
                            @enderror
                            <div wire:loading wire:target="pdfFile" class="text-sm text-gray-500 mt-1">Chargement du PDF...</div>
                            @if ($pdfFile)
                                <p class="text-sm text-green-600 mt-1">PDF sélectionné: {{ $pdfFile->getClientOriginalName() }}</p>
                            @endif
                        </div>

                        {{-- Titre --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Titre (optionnel)
                            </label>
                            <input type="text" wire:model="title" placeholder="Ex: Agenda Printemps 2025"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                            @error('title')
                                <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Description (optionnel)
                            </label>
                            <textarea wire:model="description" rows="3" placeholder="Description de l'agenda..."
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent"></textarea>
                            @error('description')
                                <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Date début --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Date de début * <span class="text-xs text-gray-500">(date d'activation)</span>
                            </label>
                            <input type="date" wire:model="startDate"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                            @error('startDate')
                                <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Date fin --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Date de fin *
                            </label>
                            <input type="date" wire:model="endDate"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                            @error('endDate')
                                <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit"
                            class="px-8 py-3 bg-[#3E9B90] text-white text-lg font-semibold rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-md disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                            wire:loading.attr="disabled" wire:target="pdfFile, uploadAgenda">
                            <span wire:loading.remove wire:target="uploadAgenda">
                                Ajouter l'agenda
                            </span>
                            <span wire:loading wire:target="uploadAgenda">Upload en cours...</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>

        {{-- Section 2: Agenda en attente --}}
        @if ($pendingAgenda)
            <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-8 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Agenda en attente</h2>

                <div class="flex items-start gap-6 p-4 bg-gradient-to-r from-amber-500/10 to-amber-500/5 rounded-lg border-2 border-amber-500">
                    {{-- Image de couverture globale --}}
                    <div class="flex-shrink-0">
                        @if ($hasCoverImage)
                            <img src="{{ $coverThumbnailUrl ?? $coverImageUrl }}?v={{ time() }}"
                                alt="Agenda en attente"
                                class="w-32 h-40 object-cover rounded-lg shadow-md">
                        @else
                            <div class="w-32 h-40 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>

                    {{-- Infos --}}
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-3 py-1 bg-amber-500 text-white text-xs font-bold rounded-full">EN ATTENTE</span>
                            <span class="text-sm text-amber-600 dark:text-amber-400">
                                Activation le {{ $pendingAgenda->start_date->format('d/m/Y') }}
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $pendingAgenda->title ?? 'Agenda' }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            <span class="font-medium">Période:</span> {{ $pendingAgenda->period }}
                        </p>
                        @if ($pendingAgenda->description)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 line-clamp-2">
                                {{ $pendingAgenda->description }}
                            </p>
                        @endif
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-3">
                            Ajouté le {{ $pendingAgenda->created_at->format('d/m/Y à H:i') }} par {{ $pendingAgenda->uploader->name }}
                        </p>

                        {{-- Actions --}}
                        <div class="flex items-center gap-3 mt-4">
                            <a href="{{ asset('storage/' . $pendingAgenda->pdf_path) }}" target="_blank"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Voir le PDF
                            </a>
                            <button wire:click="openEditModal({{ $pendingAgenda->id }})"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Modifier
                            </button>
                            <button wire:click="openDeleteModal({{ $pendingAgenda->id }})"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Section 3: Agenda en cours --}}
        <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-8 mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Agenda en cours</h2>

            @if ($currentAgenda)
                <div class="flex items-start gap-6 p-4 bg-gradient-to-r from-[#3E9B90]/10 to-[#3E9B90]/5 rounded-lg border-2 border-[#3E9B90]">
                    {{-- Image de couverture globale --}}
                    <div class="flex-shrink-0">
                        @if ($hasCoverImage)
                            <img src="{{ $coverThumbnailUrl ?? $coverImageUrl }}?v={{ time() }}"
                                alt="Agenda en cours"
                                class="w-32 h-40 object-cover rounded-lg shadow-md">
                        @else
                            <div class="w-32 h-40 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>

                    {{-- Infos --}}
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-3 py-1 bg-[#3E9B90] text-white text-xs font-bold rounded-full">EN COURS</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $currentAgenda->title ?? 'Agenda' }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            <span class="font-medium">Période:</span> {{ $currentAgenda->period }}
                        </p>
                        @if ($currentAgenda->description)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 line-clamp-2">
                                {{ $currentAgenda->description }}
                            </p>
                        @endif
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-3">
                            Uploadé le {{ $currentAgenda->created_at->format('d/m/Y à H:i') }} par {{ $currentAgenda->uploader->name }}
                        </p>

                        {{-- Actions --}}
                        <div class="flex items-center gap-3 mt-4">
                            <a href="{{ asset('storage/' . $currentAgenda->pdf_path) }}" target="_blank"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Voir le PDF
                            </a>
                            <button wire:click="openEditModal({{ $currentAgenda->id }})"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Modifier
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Lien permanent PDF --}}
                <div class="mt-4 p-3 bg-gray-100 dark:bg-gray-800 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">Lien permanent PDF:</span>
                        <code class="ml-2 px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs break-all">{{ asset('storage/agendas/agenda-en-cours.pdf') }}</code>
                    </p>
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p>Aucun agenda en cours. Ajoutez-en un ci-dessus.</p>
                </div>
            @endif
        </div>

        {{-- Section 4: Historique des agendas --}}
        <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-8 mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Historique des agendas</h2>

            @if ($archivedAgendas->count() > 0)
                <div class="space-y-3">
                    @foreach ($archivedAgendas as $agenda)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-4">
                                {{-- Image de couverture globale (thumbnail) --}}
                                @if ($hasCoverImage)
                                    <img src="{{ $coverThumbnailUrl ?? $coverImageUrl }}?v={{ time() }}"
                                        alt="Agenda archivé"
                                        class="w-12 h-16 object-cover rounded">
                                @else
                                    <div class="w-12 h-16 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">
                                        {{ $agenda->title ?? 'Agenda' }}
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $agenda->period }}
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        Archivé le {{ $agenda->archived_at?->format('d/m/Y') }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                @if ($agenda->pdf_path && Storage::disk('public')->exists($agenda->pdf_path))
                                    <a href="{{ asset('storage/' . $agenda->pdf_path) }}" target="_blank"
                                        class="p-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                                        title="Voir le PDF">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </a>
                                @endif
                                <button wire:click="openEditModal({{ $agenda->id }})"
                                    class="p-2 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg hover:bg-amber-200 dark:hover:bg-amber-900/50 transition-colors"
                                    title="Modifier">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button wire:click="openDeleteModal({{ $agenda->id }})"
                                    class="p-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                                    title="Supprimer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $archivedAgendas->links() }}
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                    <p>Aucun agenda archivé pour le moment.</p>
                </div>
            @endif
        </div>

        {{-- Section 5: Image de couverture globale --}}
        <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Image de couverture</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Cette image sera utilisée pour tous les agendas (actuel et archivés)
            </p>

            <div class="flex flex-col md:flex-row items-start gap-6">
                {{-- Image actuelle --}}
                <div class="flex-shrink-0">
                    @if ($hasCoverImage)
                        <img src="{{ $coverImageUrl }}?v={{ time() }}"
                            alt="Image de couverture"
                            class="w-40 h-52 object-cover rounded-lg shadow-md border-2 border-[#3E9B90]">
                    @else
                        <div class="w-40 h-52 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-400 dark:border-gray-500">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <p class="mt-2 text-xs text-gray-500">Aucune image</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Formulaire upload image --}}
                <div class="flex-1">
                    <form wire:submit.prevent="uploadCoverImage">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $hasCoverImage ? 'Changer l\'image' : 'Uploader une image' }}
                            </label>
                            <input type="file" wire:model="coverImage" accept="image/*"
                                class="w-full text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none px-4 py-3">
                            @error('coverImage')
                                <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span>
                            @enderror
                            <div wire:loading wire:target="coverImage" class="text-sm text-gray-500 mt-1">Chargement...</div>
                            @if ($coverImage)
                                <div class="mt-2">
                                    <img src="{{ $coverImage->temporaryUrl() }}" class="w-24 h-32 object-cover rounded-lg">
                                </div>
                            @endif
                        </div>

                        <button type="submit"
                            class="px-6 py-2 bg-[#3E9B90] text-white font-medium rounded-lg transition-all duration-200 hover:bg-[#2d7a72] disabled:opacity-50 disabled:cursor-not-allowed"
                            wire:loading.attr="disabled" wire:target="coverImage, uploadCoverImage">
                            <span wire:loading.remove wire:target="uploadCoverImage">
                                {{ $hasCoverImage ? 'Mettre à jour l\'image' : 'Uploader l\'image' }}
                            </span>
                            <span wire:loading wire:target="uploadCoverImage">Upload en cours...</span>
                        </button>
                    </form>

                    @if ($hasCoverImage)
                        <div class="mt-4 p-3 bg-gray-100 dark:bg-gray-800 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Lien permanent:</span>
                                <code class="ml-2 px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs break-all">{{ asset('storage/agendas/couverture.jpg') }}</code>
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Paramètres catégorie/auteur --}}
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Paramètres de l'agenda</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Ces paramètres seront utilisés pour l'affichage sur la page brochures
                </p>

                <form wire:submit.prevent="updateAgendaSettings">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Catégorie --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Catégorie
                            </label>
                            <select wire:model="agendaCategoryId"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                <option value="">-- Aucune catégorie --</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Auteur --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Auteur
                            </label>
                            <select wire:model="agendaAuthorId"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                <option value="">-- Aucun auteur --</option>
                                @foreach ($authors as $author)
                                    <option value="{{ $author->id }}">{{ $author->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit"
                            class="px-6 py-2 bg-[#3E9B90] text-white font-medium rounded-lg transition-all duration-200 hover:bg-[#2d7a72] disabled:opacity-50 disabled:cursor-not-allowed"
                            {{ !$currentAgenda ? 'disabled' : '' }}>
                            Enregistrer les paramètres
                        </button>
                        @if (!$currentAgenda)
                            <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">
                                Uploadez d'abord un agenda pour configurer ces paramètres.
                            </p>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Edit Modal --}}
        @if ($showEditModal && $editingAgenda)
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center px-4 py-6">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeEditModal"></div>

                    <div class="relative bg-white dark:bg-[#001716] rounded-lg shadow-xl max-w-lg w-full p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Modifier l'agenda</h3>

                        <form wire:submit.prevent="updateAgenda">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titre</label>
                                    <input type="text" wire:model="editTitle"
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90]">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                    <textarea wire:model="editDescription" rows="3"
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90]"></textarea>
                                </div>

                                {{-- PDF File (optionnel) --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Remplacer le PDF (optionnel)
                                    </label>
                                    <input type="file" wire:model="editPdfFile" accept=".pdf,application/pdf"
                                        class="w-full text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none px-4 py-2">
                                    @error('editPdfFile')
                                        <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span>
                                    @enderror
                                    <div wire:loading wire:target="editPdfFile" class="text-sm text-gray-500 mt-1">
                                        Chargement du PDF...
                                    </div>
                                    @if ($editPdfFile)
                                        <p class="text-sm text-green-600 mt-1">
                                            Nouveau PDF: {{ $editPdfFile->getClientOriginalName() }}
                                        </p>
                                    @endif
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Laissez vide pour conserver le PDF actuel. Max 50 MB.
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date début</label>
                                        <input type="date" wire:model="editStartDate"
                                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90]">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date fin</label>
                                        <input type="date" wire:model="editEndDate"
                                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90]">
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-3 justify-end mt-6">
                                <button type="button" wire:click="closeEditModal"
                                    class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                    Annuler
                                </button>
                                <button type="submit"
                                    class="px-6 py-2 text-sm font-medium text-white bg-[#3E9B90] hover:bg-[#2d7a72] rounded-lg transition-colors disabled:opacity-50"
                                    wire:loading.attr="disabled" wire:target="editPdfFile, updateAgenda">
                                    <span wire:loading.remove wire:target="updateAgenda">Enregistrer</span>
                                    <span wire:loading wire:target="updateAgenda">Mise à jour...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Delete Modal --}}
        @if ($showDeleteModal && $selectedAgenda)
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center px-4">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeDeleteModal"></div>

                    <div class="relative bg-white dark:bg-[#001716] rounded-lg shadow-xl max-w-md w-full p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Confirmer la suppression</h3>

                        <div class="mb-6">
                            @if ($hasCoverImage)
                                <img src="{{ $coverThumbnailUrl ?? $coverImageUrl }}"
                                    alt="{{ $selectedAgenda->title ?? 'Agenda' }}"
                                    class="w-full h-48 object-cover rounded-lg mb-4">
                            @endif
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                Êtes-vous sûr de vouloir supprimer cet agenda ?
                            </p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $selectedAgenda->title ?? 'Agenda' }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedAgenda->period }}</p>
                        </div>

                        <div class="flex gap-3 justify-end">
                            <button wire:click="closeDeleteModal"
                                class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                Annuler
                            </button>
                            <button wire:click="deleteAgenda({{ $selectedAgenda->id }})"
                                class="px-6 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
