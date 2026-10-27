<div class="flex h-full w-full flex-1 flex-col gap-4">
    <x-verif.space-header active="trash" />

    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    <p class="text-sm text-gray-600 dark:text-gray-400">
        Pages retirées du suivi. Rien n'a été supprimé : relectures, relecteurs et journal sont conservés et reviennent avec la page.
    </p>

    <div class="relative">
        <label for="trash-search" class="sr-only">Rechercher une page</label>
        <input id="trash-search" type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher un titre, une adresse…"
            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 pl-9 pr-3 py-2 text-sm text-gray-900 dark:text-white">
        <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"></path></svg>
    </div>

    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Page</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Retirée le</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Relectures</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Relecteurs</th>
                        <th class="px-3 py-2"><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse ($pages as $page)
                        <tr wire:key="trash-{{ $page->id }}">
                            <td class="px-3 py-2 align-top max-w-md">
                                <a href="{{ route('verification.admin.page', $page->id) }}" wire:navigate class="text-sm font-medium text-gray-900 dark:text-white hover:underline">{{ $page->title }}</a>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ \Illuminate\Support\Str::after($page->url, '://') }}
                                    @if ($page->isOutOfSite()) · plus sur le site @endif
                                </p>
                            </td>
                            <td class="px-3 py-2 align-top text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $page->deleted_at->format('d/m/Y') }}</td>
                            <td class="px-3 py-2 align-top text-sm text-gray-700 dark:text-gray-300 tabular-nums">{{ $page->all_reviews_count }}</td>
                            <td class="px-3 py-2 align-top text-sm text-gray-700 dark:text-gray-300 tabular-nums">{{ $page->assignees_count }}</td>
                            <td class="px-3 py-2 align-top text-right">
                                <button type="button" wire:click="restore({{ $page->id }})"
                                    class="px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md">
                                    Restaurer
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ $search !== '' ? 'Aucune page de la corbeille ne correspond.' : 'La corbeille est vide.' }}
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
