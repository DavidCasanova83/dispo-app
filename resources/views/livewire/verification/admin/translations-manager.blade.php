<div class="flex h-full w-full flex-1 flex-col gap-4">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Suivi des traductions</h1>
        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
            @if ($view === 'list')
                État des versions anglaise et italienne de chaque page. Les corrections signalées remontent en premier.
            @else
                Corrections signalées par les traducteurs pour cette page.
            @endif
        </p>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    @if ($view === 'list')
        {{-- Tuiles = filtres. Une page compte dans une tuile dès qu'une de ses langues est dans cet état. --}}
        <div class="grid gap-2 grid-cols-2 sm:grid-cols-3 lg:grid-cols-5">
            <x-verif.stat-filter label="Toutes les pages" :count="$stats['all']"
                :active="$filterStatus === 'all'" wire:click="applyStatusFilter('all')" />
            @foreach (\App\Models\TranslationCheck::FILTERS as $code => $def)
                <x-verif.stat-filter
                    :label="$def['label']"
                    :tone="$def['tone']"
                    :alert="$code === 'to_fix'"
                    :count="$stats[$code]"
                    :active="$filterStatus === $code"
                    wire:click="applyStatusFilter('{{ $code }}')" />
            @endforeach
        </div>

        {{-- Filtres secondaires --}}
        <div class="flex flex-col lg:flex-row lg:items-center gap-2">
            <div class="relative flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher un titre, une URL…"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 pl-9 pr-3 py-2 text-sm text-gray-900 dark:text-white">
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"></path>
                </svg>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <select wire:model.live="filterLanguage"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                    <option value="all">EN et IT</option>
                    @foreach (\App\Models\TranslationCheck::LANGUAGES as $code => $lang)
                        <option value="{{ $code }}">{{ $lang['flag'] }} {{ $lang['label'] }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterCategory"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                    <option value="">Toutes les catégories</option>
                    @foreach (\App\Models\VerificationPage::CATEGORIES as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
                @if ($this->hasActiveFilters())
                    <button type="button" wire:click="resetFilters"
                        class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white underline underline-offset-2">
                        Réinitialiser
                    </button>
                @endif
            </div>
        </div>

        {{-- Tableau --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Page</th>
                            @foreach ($languages as $code)
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ \App\Models\TranslationCheck::LANGUAGES[$code]['flag'] }} {{ \App\Models\TranslationCheck::LANGUAGES[$code]['short'] }}
                                </th>
                            @endforeach
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Dernière activité</th>
                            <th class="px-3 py-2 w-10"><span class="sr-only">Détail</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        @forelse ($pages as $page)
                            <tr wire:key="tr-page-{{ $page->id }}" wire:click="openPage({{ $page->id }})"
                                class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-3 py-2 align-top max-w-sm">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $page->title }}</span>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 truncate max-w-[22rem]">
                                        {{ \Illuminate\Support\Str::after($page->url, '://') }}
                                    </p>
                                </td>
                                @foreach ($languages as $code)
                                    @php $check = $page->translationCheckFor($code); @endphp
                                    <td class="px-3 py-2 align-top max-w-xs">
                                        <x-verif.status-badge size="xs"
                                            :tone="\App\Models\TranslationCheck::statusToneFor($check)"
                                            :label="\App\Models\TranslationCheck::statusLabelFor($check)" />
                                        @if ($check?->comment && in_array($check->status, ['to_fix', 'in_progress'], true))
                                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 line-clamp-2">{{ $check->comment }}</p>
                                        @elseif (! $page->urlForLanguage($code))
                                            <p class="mt-0.5 text-[11px] text-gray-400 dark:text-gray-500">URL non renseignée</p>
                                        @endif
                                        @php $claudeRequest = $page->latestClaudeRequestFor($code); @endphp
                                        @if ($claudeRequest && ($claudeRequest->isActive() || in_array($check?->status, ['to_fix', 'in_progress'], true)))
                                            <p class="mt-0.5 text-[11px] text-purple-700 dark:text-purple-300">🤖 Claude : {{ $claudeRequest->statusLabel() }}</p>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-2 align-top">
                                    @if ($page->last_check_at)
                                        <span class="text-xs text-gray-600 dark:text-gray-400"
                                            title="{{ \Illuminate\Support\Carbon::parse($page->last_check_at)->format('d/m/Y à H:i') }}">
                                            {{ \Illuminate\Support\Carbon::parse($page->last_check_at)->diffForHumans(short: true) }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-600">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 align-top text-right text-sm text-gray-400">→</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($languages) + 3 }}" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                    @if ($this->hasActiveFilters())
                                        Aucune page ne correspond à ces filtres.
                                        <button type="button" wire:click="resetFilters" class="ml-1 underline underline-offset-2 hover:text-gray-900 dark:hover:text-white">
                                            Réinitialiser
                                        </button>
                                    @else
                                        Aucune page à afficher.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $pages->links() }}</div>
    @else
        {{-- ═══ DÉTAIL D'UNE PAGE ═══ --}}
        <div>
            <button type="button" wire:click="closePage"
                class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">← Retour à la liste</button>
            <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ $page->title }}</h2>
            <a href="{{ $page->url }}" target="_blank" rel="noopener noreferrer"
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">🇫🇷 {{ \Illuminate\Support\Str::after($page->url, '://') }} ↗</a>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            @foreach (\App\Models\TranslationCheck::LANGUAGES as $code => $lang)
                @php
                    $check = $page->translationCheckFor($code);
                    $url = $page->urlForLanguage($code);
                    $actionable = $check && in_array($check->status, ['to_fix', 'in_progress'], true);
                @endphp
                <section wire:key="detail-{{ $code }}"
                    class="rounded-xl border bg-white dark:bg-gray-800 p-4 sm:p-5 flex flex-col gap-3
                        {{ $check?->status === 'to_fix' ? 'border-red-300 dark:border-red-800' : 'border-gray-200 dark:border-gray-700' }}">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $lang['flag'] }} {{ $lang['label'] }}</h3>
                        <x-verif.status-badge
                            :tone="\App\Models\TranslationCheck::statusToneFor($check)"
                            :label="\App\Models\TranslationCheck::statusLabelFor($check)" />
                    </div>

                    @if ($url)
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                            class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline break-all">{{ \Illuminate\Support\Str::after($url, '://') }} ↗</a>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">URL non renseignée.</p>
                    @endif

                    @if (! $check)
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pas encore vérifiée par un traducteur.</p>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Vérifiée par {{ $check->checker?->name ?? 'un utilisateur supprimé' }}
                            le {{ $check->checked_at?->translatedFormat('d F Y à H:i') }}
                            @if ($check->handler)
                                · traitée par {{ $check->handler->name }} le {{ $check->handled_at?->translatedFormat('d F Y à H:i') }}
                            @endif
                        </p>

                        @if ($check->comment)
                            <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-3">
                                <p class="text-xs uppercase font-semibold text-amber-800 dark:text-amber-300 mb-1">Corrections demandées</p>
                                <p class="text-sm text-amber-900 dark:text-amber-100 whitespace-pre-line">{{ $check->comment }}</p>
                            </div>
                        @endif

                        @if ($actionable)
                            <div>
                                <label for="response-{{ $code }}" class="text-sm font-medium text-gray-900 dark:text-white">Réponse au traducteur (facultatif)</label>
                                <textarea id="response-{{ $code }}" wire:model="responses.{{ $code }}" rows="3"
                                    class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-white"></textarea>
                                @error("responses.{$code}") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if ($check->status === 'to_fix')
                                    <button type="button" wire:click="markInProgress('{{ $code }}')"
                                        class="px-3 py-2 text-sm font-medium text-blue-800 bg-blue-100 hover:bg-blue-200 dark:text-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 rounded-lg">
                                        🛠️ Passer en cours
                                    </button>
                                @endif
                                <button type="button" wire:click="markFixed('{{ $code }}')"
                                    class="px-3 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg">
                                    ✅ Marquer corrigée
                                </button>
                            </div>
                        @elseif ($check->admin_response)
                            <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-3">
                                <p class="text-xs uppercase font-semibold text-blue-800 dark:text-blue-300 mb-1">Votre réponse</p>
                                <p class="text-sm text-blue-900 dark:text-blue-100 whitespace-pre-line">{{ $check->admin_response }}</p>
                            </div>
                        @endif

                        {{-- Correction par Claude : issue GitHub, puis PR créée par le panel --}}
                        @php $claudeRequest = $page->latestClaudeRequestFor($code); @endphp
                        @if ($actionable || $claudeRequest)
                            <div class="rounded-lg border border-purple-200 dark:border-purple-800 bg-purple-50/60 dark:bg-purple-900/10 p-3 flex flex-col gap-2">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs uppercase font-semibold text-purple-800 dark:text-purple-300">🤖 Correction par Claude</p>
                                    @if ($claudeRequest)
                                        <x-verif.status-badge size="xs" :tone="$claudeRequest->statusTone()" :label="$claudeRequest->statusLabel()" />
                                    @endif
                                </div>

                                @if ($claudeRequest)
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        Demandée le {{ $claudeRequest->sent_at?->translatedFormat('d F Y à H:i') }}
                                        par {{ $claudeRequest->requester?->name ?? 'un utilisateur supprimé' }}
                                        @if ($claudeRequest->issue_url)
                                            · <a href="{{ $claudeRequest->issue_url }}" target="_blank" rel="noopener noreferrer"
                                                class="text-indigo-600 dark:text-indigo-400 hover:underline">issue #{{ $claudeRequest->issue_number }} ↗</a>
                                        @endif
                                        @if ($claudeRequest->pr_url)
                                            · <a href="{{ $claudeRequest->pr_url }}" target="_blank" rel="noopener noreferrer"
                                                class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">PR #{{ $claudeRequest->pr_number }} ↗</a>
                                        @endif
                                    </p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        @switch($claudeRequest->status)
                                            @case('sent')
                                                Claude corrige la traduction. La PR sera créée automatiquement dès qu'il aura fini (vérification toutes les 5 minutes).
                                                @break
                                            @case('pr_open')
                                                PR à relire et fusionner sur GitHub.
                                                @break
                                            @case('merged')
                                                PR fusionnée : après déploiement, vérifiez la page en ligne puis « Marquer corrigée ».
                                                @break
                                            @case('closed')
                                                PR fermée sans fusion : rien n'a été modifié sur le site.
                                                @break
                                            @case('no_change')
                                                Claude n'a rien modifié dans le dépôt : sa réponse est dans l'issue (page de l'admin WordPress à corriger à la main, ou correction non applicable).
                                                @break
                                        @endswitch
                                    </p>
                                    @if ($claudeRequest->error)
                                        <p class="text-sm {{ $claudeRequest->status === 'failed' ? 'text-red-700 dark:text-red-400' : 'text-amber-700 dark:text-amber-400' }}">{{ $claudeRequest->error }}</p>
                                    @endif
                                    @if ($claudeRequest->isActive())
                                        <button type="button" wire:click="refreshClaude('{{ $code }}')" wire:loading.attr="disabled"
                                            class="self-start text-sm text-purple-800 dark:text-purple-300 hover:underline underline-offset-2">
                                            🔄 Vérifier maintenant
                                        </button>
                                    @endif
                                @endif

                                @if ($actionable && ! $claudeRequest?->isActive())
                                    <div>
                                        <label for="claude-{{ $code }}" class="text-sm font-medium text-gray-900 dark:text-white">Précisions pour Claude (facultatif)</label>
                                        <textarea id="claude-{{ $code }}" wire:model="claudeMessages.{{ $code }}" rows="2"
                                            placeholder="Le commentaire du traducteur est transmis tel quel."
                                            class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-white"></textarea>
                                        @error("claudeMessages.{$code}") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                    </div>
                                    @if ($claudeConfigured)
                                        <button type="button" wire:click="askClaude('{{ $code }}')" wire:loading.attr="disabled"
                                            wire:confirm="Envoyer la correction {{ $lang['short'] }} à Claude ? Une issue GitHub est créée avec le commentaire du traducteur ; Claude corrige la page et une PR est créée automatiquement, à relire avant de fusionner."
                                            class="self-start px-3 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 disabled:opacity-50 rounded-lg">
                                            🤖 {{ $claudeRequest ? 'Renvoyer à Claude' : 'Demander la correction à Claude' }}
                                        </button>
                                    @else
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Connexion à GitHub non configurée (<code>CLAUDE_GITHUB_TOKEN</code>, voir DEMANDES_CLAUDE.md).
                                        </p>
                                    @endif
                                @endif
                                @error("claude.{$code}") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <button type="button" wire:click="requestRecheck('{{ $code }}')"
                            wire:confirm="Repasser la traduction {{ $lang['short'] }} « à vérifier » ? Le verdict actuel sera effacé."
                            class="self-start text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white underline underline-offset-2">
                            🔄 Redemander une vérification
                        </button>
                    @endif
                </section>
            @endforeach
        </div>
    @endif
</div>
