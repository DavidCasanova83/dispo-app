<div class="flex h-full w-full flex-1 flex-col gap-4">
    <x-verif.space-header active="verified" stage="verified" />

    <p class="text-sm text-gray-600 dark:text-gray-400">
        Pages clôturées, de la prochaine à repartir en relecture à la plus lointaine. Chaque page repart seule
        {{ \App\Models\VerificationPage::REVALIDATION_DAYS }} jours après sa clôture, vers les mêmes relecteurs.
        @if ($upcoming > 0)
            <strong class="text-gray-900 dark:text-white">{{ $upcoming }} page{{ $upcoming > 1 ? 's repartent' : ' repart' }} dans les 30 prochains jours.</strong>
        @endif
    </p>

    <div class="flex flex-col sm:flex-row gap-2">
        <div class="relative flex-1">
            <label for="verified-search" class="sr-only">Rechercher une page</label>
            <input id="verified-search" type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher un titre, une adresse…"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 pl-9 pr-3 py-2 text-sm text-gray-900 dark:text-white">
            <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"></path></svg>
        </div>
        <select wire:model.live="filterCategory" aria-label="Catégorie"
            class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
            <option value="all">Toutes catégories</option>
            @foreach (\App\Models\VerificationPage::CATEGORIES as $code => $label)
                <option value="{{ $code }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Page</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Vérifiée le</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Repart en relecture</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Relecteurs</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Relectures archivées</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse ($pages as $page)
                        <tr wire:key="verified-{{ $page->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-3 py-2 align-top max-w-md">
                                <a href="{{ route('verification.admin.page', $page->id) }}" wire:navigate class="text-sm font-medium text-gray-900 dark:text-white hover:underline">{{ $page->title }}</a>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ \Illuminate\Support\Str::after($page->url, '://') }}@if ($page->categoryLabel()) · {{ $page->categoryLabel() }}@endif</p>
                            </td>
                            <td class="px-3 py-2 align-top text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $page->validated_at?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-3 py-2 align-top"><x-verif.next-check-badge :page="$page" /></td>
                            <td class="px-3 py-2 align-top text-xs text-gray-700 dark:text-gray-300">{{ $page->assignees->pluck('name')->join(', ') ?: '—' }}</td>
                            <td class="px-3 py-2 align-top text-sm text-gray-700 dark:text-gray-300 tabular-nums">
                                @if ($page->archived_reviews_count > 0)
                                    <a href="{{ route('verification.admin.page', $page->id) }}" wire:navigate class="hover:underline">{{ $page->archived_reviews_count }}</a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500" title="Clôturée avant l'archivage des relectures">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ $search !== '' || $filterCategory !== 'all' ? 'Aucune page vérifiée ne correspond à ces filtres.' : 'Aucune page vérifiée pour le moment.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pages->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">{{ $pages->links() }}</div>
        @endif
    </div>
</div>
