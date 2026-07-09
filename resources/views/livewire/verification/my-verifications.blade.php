<div class="flex h-full w-full flex-1 flex-col gap-6 max-w-3xl mx-auto">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
            Bonjour {{ auth()->user()->name }} 👋
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Vos pages à vérifier
        </p>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    {{-- List --}}
    <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
            📋 Mes pages à vérifier <span class="text-gray-500">({{ $pages->count() }})</span>
        </h2>

        @if ($pages->isEmpty())
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-8 text-center">
                @if ($totalDone === 0)
                    <p class="text-base text-gray-700 dark:text-gray-300">
                        💡 <strong>Première fois ici ?</strong>
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        Vous n'avez pas encore de page à vérifier. David vous attribuera des pages bientôt.
                    </p>
                @else
                    <p class="text-base text-gray-700 dark:text-gray-300">
                        ✨ Aucune page à vérifier pour le moment.
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        Merci pour votre contribution ! David vous attribuera de nouvelles pages bientôt.
                    </p>
                @endif
            </div>
        @else
            <div class="space-y-3">
                @foreach ($pages as $page)
                    @php
                        $priorityBadge = match ($page->priority) {
                            'high' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                            'medium' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                            'low' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                        };
                        $revisionReview = $page->revisionRequestedReviewFor(auth()->user());
                    @endphp
                    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-4 sm:p-5">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $priorityBadge }}">
                                {{ $page->priorityIcon() }} {{ strtoupper($page->priorityLabel()) }}
                            </span>
                            @if ($revisionReview)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    🔄 Ré-vérification demandée
                                </span>
                            @endif
                        </div>
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            {{ $page->title }}
                        </h3>
                        @if ($page->theme)
                            <p class="text-sm text-gray-600 dark:text-gray-400">🏷️ {{ $page->theme }}</p>
                        @endif
                        @if ($page->deadline)
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                📅 À vérifier avant le {{ $page->deadline->translatedFormat('d F Y') }}
                            </p>
                        @endif

                        @if ($revisionReview && $revisionReview->admin_response)
                            <div class="mt-3 rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-3">
                                <p class="text-xs uppercase font-semibold text-blue-800 dark:text-blue-300 mb-1">
                                    Message de l'admin
                                </p>
                                <p class="text-sm text-blue-900 dark:text-blue-100 whitespace-pre-line">{{ $revisionReview->admin_response }}</p>
                                <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">
                                    {{ $revisionReview->admin_response_at?->translatedFormat('d F Y à H:i') }}
                                </p>
                            </div>
                        @endif

                        <div class="mt-4 flex flex-col sm:flex-row gap-2">
                            <a href="{{ route('verification.form', $page) }}" wire:navigate
                                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">
                                🇫🇷 Vérifier en FR →
                            </a>
                            @if ($page->url_en)
                                <a href="{{ route('verification.form', ['page' => $page->id, 'lang' => 'en']) }}" wire:navigate
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-indigo-700 bg-indigo-100 hover:bg-indigo-200 dark:text-indigo-200 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 rounded-lg transition-colors">
                                    🇬🇧 EN
                                </a>
                            @endif
                            @if ($page->url_it)
                                <a href="{{ route('verification.form', ['page' => $page->id, 'lang' => 'it']) }}" wire:navigate
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-indigo-700 bg-indigo-100 hover:bg-indigo-200 dark:text-indigo-200 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 rounded-lg transition-colors">
                                    🇮🇹 IT
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Footer --}}
    @if ($totalDone > 0)
        <div class="text-center text-sm text-gray-600 dark:text-gray-400 py-4 border-t border-gray-200 dark:border-gray-700">
            ✅ Vous avez déjà vérifié {{ $totalDone }} page{{ $totalDone > 1 ? 's' : '' }}. Merci !
            <div class="mt-2">
                <a href="{{ route('verification.history') }}" wire:navigate
                    class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 hover:underline">
                    Voir mon historique de vérifications
                </a>
            </div>
        </div>
    @endif
</div>
