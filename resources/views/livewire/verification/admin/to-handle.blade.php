<div class="flex h-full w-full flex-1 flex-col gap-5">
    <x-verif.space-header active="to_do">
        <x-slot:actions>
            <a href="{{ route('verification.admin.pages', ['ajouter' => 1]) }}" wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded-lg">
                + Ajouter une page
            </a>
        </x-slot:actions>
    </x-verif.space-header>

    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    @if ($newCount === 0 && $progressCount === 0 && $readyPages->isEmpty())
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-10 text-center">
            <p class="text-base text-gray-900 dark:text-white font-medium">Rien à traiter pour le moment.</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Les nouveaux retours des relecteurs et les pages prêtes à clôturer apparaîtront ici.
                <a href="{{ route('verification.admin.pages') }}" wire:navigate class="underline underline-offset-2">Voir toutes les pages</a>
            </p>
        </div>
    @endif

    {{-- ═══ NOUVEAUX RETOURS ═══ --}}
    @if ($newCount > 0)
        <section class="flex flex-col gap-2">
            <div class="flex items-baseline justify-between gap-2">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Nouveaux retours · {{ $newCount }}</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">du plus ancien au plus récent</span>
            </div>
            @foreach ($newReviews as $review)
                @include('livewire.verification.admin.partials.feedback-card', ['review' => $review])
            @endforeach
            @if ($newCount > $newReviews->count())
                <button type="button" wire:click="$set('showAllNew', true)"
                    class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 text-left">
                    Afficher les {{ $newCount - $newReviews->count() }} autres nouveaux retours ↓
                </button>
            @endif
        </section>
    @endif

    {{-- ═══ PRÊTES À CLÔTURER ═══ --}}
    @if ($readyPages->isNotEmpty())
        <section class="flex flex-col gap-2">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Prêtes à clôturer · {{ $readyPages->count() }}</h2>
                <div class="flex items-center gap-2">
                    @if (count($selectedReady) < $readyPages->count())
                        <button type="button" wire:click="selectAllReady" class="text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white underline underline-offset-2">Tout cocher</button>
                    @else
                        <button type="button" wire:click="clearReadySelection" class="text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white underline underline-offset-2">Tout décocher</button>
                    @endif
                    <button type="button" wire:click="askClose" @disabled(empty($selectedReady))
                        class="px-3 py-1.5 text-xs font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded-md disabled:opacity-40 disabled:cursor-not-allowed">
                        Clôturer la sélection ({{ count($selectedReady) }})
                    </button>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Tous les relecteurs ont répondu et chaque retour est réglé. La clôture marque la page vérifiée pour un an et archive ses relectures : elles restent consultables.
            </p>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700/60">
                @foreach ($readyPages as $page)
                    <div wire:key="ready-{{ $page->id }}" class="flex flex-col sm:flex-row sm:items-center gap-2 px-3 py-2.5">
                        <label class="flex items-start gap-3 flex-1 min-w-0 cursor-pointer">
                            <input type="checkbox" value="{{ $page->id }}" wire:model.live="selectedReady"
                                class="mt-0.5 rounded border-gray-300 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white">
                            <span class="min-w-0">
                                <a href="{{ route('verification.admin.page', $page->id) }}" wire:navigate class="text-sm font-medium text-gray-900 dark:text-white hover:underline">{{ $page->title }}</a>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">
                                    {{ $page->assignees->count() }} relecteur{{ $page->assignees->count() > 1 ? 's' : '' }} · prête depuis le {{ $page->updated_at->format('d/m/Y') }}
                                </span>
                            </span>
                        </label>
                        <div class="flex flex-wrap items-center gap-1">
                            @foreach ($page->reviewerProgress() as $row)
                                <x-verif.reviewer-chip :row="$row" />
                            @endforeach
                        </div>
                        <button type="button" wire:click="askClose({{ $page->id }})"
                            class="self-start sm:self-auto px-2.5 py-1 text-xs font-medium text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md">
                            Clôturer
                        </button>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ═══ CORRECTIONS EN COURS ═══ --}}
    @if ($progressCount > 0)
        <section class="flex flex-col gap-2" x-data="{ open: {{ $progressCount <= 3 ? 'true' : 'false' }} }">
            <button type="button" @click="open = ! open"
                class="flex items-center justify-between gap-2 text-left text-sm font-semibold text-gray-900 dark:text-white">
                <span>Corrections en cours · {{ $progressCount }}</span>
                <span class="text-xs font-normal text-gray-500 dark:text-gray-400" x-text="open ? 'Masquer ↑' : 'Afficher ↓'"></span>
            </button>
            <div x-show="open" x-cloak class="flex flex-col gap-2">
                <p class="text-xs text-gray-500 dark:text-gray-400">Retours que vous avez pris en charge. Marquez-les réglés une fois la page corrigée.</p>
                @foreach ($progressReviews as $review)
                    @include('livewire.verification.admin.partials.feedback-card', ['review' => $review])
                @endforeach
                @if ($progressCount > $progressReviews->count())
                    <button type="button" wire:click="$set('showAllInProgress', true)"
                        class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 text-left">
                        Afficher les {{ $progressCount - $progressReviews->count() }} autres ↓
                    </button>
                @endif
            </div>
        </section>
    @endif

    {{-- ═══ MODALE DE CLÔTURE ═══ --}}
    @if ($showCloseModal)
        @include('livewire.verification.admin.partials.close-modal', ['closing' => $closing])
    @endif
</div>
