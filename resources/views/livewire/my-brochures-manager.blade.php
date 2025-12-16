<div class="min-h-screen bg-gray-50 dark:bg-zinc-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Mes Brochures</h1>
            <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
                Gérez les PDFs de vos brochures
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

        {{-- Bannière des signalements --}}
        @if ($pendingReports->count() > 0)
            <div class="mb-6 rounded-lg bg-gradient-to-r from-amber-500 to-orange-500 p-6 shadow-lg">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-lg font-bold text-white">
                                {{ $pendingReports->count() }} signalement(s) en attente
                            </h3>
                            @if ($unreadReportsCount > 0)
                                <span class="px-2 py-1 bg-red-600 text-white text-xs font-bold rounded-full">
                                    {{ $unreadReportsCount }} non lu(s)
                                </span>
                            @endif
                        </div>
                        <div class="space-y-2">
                            @foreach ($pendingReports->take(5) as $report)
                                <button wire:click="openReportModal({{ $report->id }})"
                                    class="flex items-center gap-3 w-full text-left p-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors">
                                    @if (!$report->is_read)
                                        <span class="w-2 h-2 bg-red-400 rounded-full flex-shrink-0"></span>
                                    @else
                                        <span class="w-2 h-2 bg-white/30 rounded-full flex-shrink-0"></span>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="text-white font-medium truncate">
                                            {{ $report->image->title ?? $report->image->name }}
                                        </p>
                                        <p class="text-white/70 text-sm truncate">
                                            Par {{ $report->user->name }} - {{ $report->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </button>
                            @endforeach
                            @if ($pendingReports->count() > 5)
                                <p class="text-sm text-white/70 text-center pt-2">
                                    Et {{ $pendingReports->count() - 5 }} autre(s) signalement(s)...
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Search --}}
        <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-6 mb-6">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher une brochure..."
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
        </div>

        {{-- Brochures List --}}
        <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Vos brochures ({{ $brochures->total() }})</h2>

            @if ($brochures->count() > 0)
                <div class="space-y-3">
                    @foreach ($brochures as $brochure)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                            {{-- Thumbnail and info --}}
                            <div class="flex items-start gap-4 flex-1 min-w-0">
                                <a href="{{ asset('storage/' . $brochure->path) }}" target="_blank" class="flex-shrink-0">
                                    <img src="{{ $brochure->thumbnail_path ? asset('storage/' . $brochure->thumbnail_path) : asset('storage/' . $brochure->path) }}"
                                        alt="{{ $brochure->alt_text ?? $brochure->name }}"
                                        class="w-14 h-[79px] object-cover rounded-lg border border-gray-200 dark:border-gray-600 hover:opacity-80 transition-opacity">
                                </a>
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">
                                        {{ $brochure->title ?? $brochure->name }}
                                    </h3>
                                    @if ($brochure->description)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                            {{ $brochure->description }}
                                        </p>
                                    @endif
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-xs text-gray-500 dark:text-gray-500">
                                        @if ($brochure->category)
                                            <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded text-xs">{{ $brochure->category->name }}</span>
                                        @endif
                                        @if ($brochure->author)
                                            <span class="bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 px-2 py-0.5 rounded text-xs">{{ $brochure->author->name }}</span>
                                        @endif
                                        @if ($brochure->sector)
                                            <span class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-2 py-0.5 rounded text-xs">{{ $brochure->sector->name }}</span>
                                        @endif
                                    </div>
                                    {{-- PDF indicator --}}
                                    <div class="flex flex-wrap gap-3 mt-2">
                                        @if ($brochure->pdf_path)
                                            <a href="{{ asset('storage/' . $brochure->pdf_path) }}" target="_blank" rel="noopener noreferrer"
                                                class="inline-flex items-center gap-1 text-xs text-red-600 hover:text-red-700 font-medium">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                PDF disponible
                                            </a>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-xs text-gray-400 font-medium">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                Aucun PDF
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-2 flex-shrink-0 ml-4">
                                {{-- Edit PDF button --}}
                                <button wire:click="openEditModal({{ $brochure->id }})"
                                    class="p-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors"
                                    title="Modifier le PDF">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $brochures->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                    <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">Aucune brochure</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Vous n'êtes responsable d'aucune brochure</p>
                </div>
            @endif
        </div>

        {{-- Edit PDF Modal --}}
        @if ($showEditModal && $editingImage)
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center px-4 py-6">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                        wire:click="closeEditModal"></div>

                    <div class="relative bg-white dark:bg-[#001716] rounded-lg shadow-xl max-w-lg w-full p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            Modifier le PDF
                        </h3>

                        <form wire:submit.prevent="updatePdf">
                            {{-- Image preview --}}
                            <div class="mb-4 flex items-center gap-4">
                                <img src="{{ $editingImage->thumbnail_path ? asset('storage/' . $editingImage->thumbnail_path) : asset('storage/' . $editingImage->path) }}"
                                    alt="{{ $editingImage->alt_text ?? $editingImage->name }}"
                                    class="w-20 h-28 object-cover rounded-lg">
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $editingImage->title ?? $editingImage->name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $editingImage->name }}</p>
                                </div>
                            </div>

                            {{-- PDF Management --}}
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    PDF téléchargeable
                                </label>

                                @if ($editingImage->pdf_path)
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg mb-3">
                                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">PDF actuel</p>
                                            <a href="{{ asset('storage/' . $editingImage->pdf_path) }}" target="_blank"
                                                class="text-xs text-[#3E9B90] hover:underline">
                                                Voir le PDF
                                            </a>
                                        </div>
                                        <label class="flex items-center gap-2 text-sm text-red-600 cursor-pointer">
                                            <input type="checkbox" wire:model="removePdf"
                                                class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                            Supprimer
                                        </label>
                                    </div>
                                @endif

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        {{ $editingImage->pdf_path ? 'Remplacer le PDF' : 'Ajouter un PDF' }} (max 50MB)
                                    </label>
                                    <input type="file" wire:model="editPdfFile" accept=".pdf,application/pdf"
                                        class="w-full text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none px-3 py-2"
                                        {{ $removePdf ? 'disabled' : '' }}>
                                    @error('editPdfFile')
                                        <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span>
                                    @enderror
                                    <div wire:loading wire:target="editPdfFile" class="text-xs text-gray-500 mt-1">
                                        Chargement du PDF...
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-3 justify-end mt-6">
                                <button type="button" wire:click="closeEditModal"
                                    class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                    Annuler
                                </button>

                                <button type="submit"
                                    class="px-6 py-3 text-sm font-medium text-white bg-[#3E9B90] hover:bg-[#2d7a72] rounded-lg transition-colors shadow-md"
                                    wire:loading.attr="disabled" wire:target="editPdfFile, updatePdf">
                                    <span wire:loading.remove wire:target="updatePdf">Enregistrer</span>
                                    <span wire:loading wire:target="updatePdf">Enregistrement...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Report Detail Modal --}}
        @if ($showReportModal && $selectedReport)
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center px-4 py-6">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                        wire:click="closeReportModal"></div>

                    <div class="relative bg-white dark:bg-[#001716] rounded-lg shadow-xl max-w-lg w-full p-6">
                        {{-- Header --}}
                        <div class="flex items-start gap-4 mb-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    Détail du signalement
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Signalé le {{ $selectedReport->created_at->format('d/m/Y à H:i') }}
                                </p>
                            </div>
                        </div>

                        {{-- Brochure info --}}
                        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg mb-4">
                            <img src="{{ $selectedReport->image->thumbnail_path ? asset('storage/' . $selectedReport->image->thumbnail_path) : asset('storage/' . $selectedReport->image->path) }}"
                                class="w-16 h-20 object-cover rounded-lg" alt="">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ $selectedReport->image->title ?? $selectedReport->image->name }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Signalé par: <span class="font-medium">{{ $selectedReport->user->name }}</span>
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $selectedReport->user->email }}
                                </p>
                            </div>
                        </div>

                        {{-- Comment --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Commentaire de l'utilisateur
                            </label>
                            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                                <p class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $selectedReport->comment }}</p>
                            </div>
                        </div>

                        {{-- Resolution note --}}
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Note de résolution (optionnel)
                            </label>
                            <textarea wire:model="resolutionNote" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent"
                                placeholder="Décrivez comment le problème a été résolu..."></textarea>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-3 justify-end">
                            <button type="button" wire:click="closeReportModal"
                                class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                Fermer
                            </button>

                            <button wire:click="resolveReport"
                                class="px-6 py-3 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors shadow-md">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Marquer comme résolu
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
