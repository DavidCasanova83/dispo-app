<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Export Apidae</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Sélectionnez les sélections Apidae à exporter (nom, email de la fiche et emails des contacts)
            </p>
        </div>
        <div class="flex items-end gap-3">
            <div class="flex flex-col gap-1">
                <label for="csvSheet" class="text-xs font-medium text-gray-600 dark:text-gray-300">Onglet à exporter en CSV</label>
                <div class="flex items-center gap-2">
                    <select id="csvSheet" wire:model="csvSheet"
                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm py-2 focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                        <option value="Emails">Emails seuls</option>
                        <option value="Détails">Détails (nom, email, origine, sélection)</option>
                    </select>
                    <button wire:click="export" wire:loading.attr="disabled" wire:target="export,exportXlsx"
                        @disabled(empty($selected))
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="export">Exporter CSV</span>
                        <span wire:loading wire:target="export">Export en cours…</span>
                    </button>
                </div>
            </div>
            <button wire:click="exportXlsx" wire:loading.attr="disabled" wire:target="export,exportXlsx"
                @disabled(empty($selected))
                class="px-4 py-2 rounded-lg bg-[#3E9B90] text-white font-medium hover:bg-[#2d7a72] transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-400 dark:disabled:bg-gray-600">
                <span wire:loading.remove wire:target="exportXlsx">
                    Exporter Excel @if (count($selected)) ({{ count($selected) }}) @endif
                </span>
                <span wire:loading wire:target="exportXlsx">Export en cours…</span>
            </button>
        </div>
    </div>

    @if (session('error'))
        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4 border border-red-200 dark:border-red-800">
            <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
        </div>
    @endif

    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    @can('sync-mailjet')
        @php($mailjetLists = $this->mailjetLists)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Liste de contacts Mailjet</h2>
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                Aligne une liste Mailjet sur les emails des sélections cochées : les nouveaux contacts sont ajoutés,
                ceux qui n'y sont plus sont retirés. Les contacts désinscrits ne sont ni réabonnés ni supprimés.
            </p>

            @if (empty($mailjetLists))
                <p class="mt-3 text-sm text-red-700 dark:text-red-300">
                    Aucune liste de contacts n'existe sur le compte Mailjet. Créez-la depuis Mailjet, puis rechargez cette page.
                </p>
            @else
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <select wire:model="mailjetListId" @disabled($mailjetPreview !== null)
                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm py-2 disabled:opacity-50 focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                        <option value="">Choisir une liste…</option>
                        @foreach ($mailjetLists as $list)
                            <option value="{{ $list['id'] }}">{{ $list['nom'] }} ({{ $list['abonnes'] }} abonnés)</option>
                        @endforeach
                    </select>

                    @if ($mailjetPreview === null)
                        <button wire:click="previewMailjetSync" wire:loading.attr="disabled"
                            wire:target="previewMailjetSync"
                            @disabled(empty($selected))
                            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="previewMailjetSync">Prévisualiser la mise à jour</span>
                            <span wire:loading wire:target="previewMailjetSync">Calcul en cours…</span>
                        </button>
                    @endif
                </div>

                @if ($mailjetPreview !== null)
                    <div class="mt-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-3">
                        <p class="text-sm text-gray-900 dark:text-white">
                            <strong>{{ $mailjetPreview['ajouts'] }}</strong> ajout(s),
                            <strong>{{ $mailjetPreview['retraits'] }}</strong> retrait(s),
                            <strong>{{ $mailjetPreview['inchanges'] }}</strong> inchangé(s),
                            <strong>{{ $mailjetPreview['desinscrits_conserves'] }}</strong> désinscrit(s) conservé(s).
                        </p>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                            Rien n'a encore été modifié sur Mailjet.
                        </p>
                        <div class="mt-3 flex items-center gap-2">
                            <button wire:click="confirmMailjetSync" wire:loading.attr="disabled"
                                wire:target="confirmMailjetSync"
                                class="px-4 py-2 rounded-lg bg-[#3E9B90] text-white font-medium hover:bg-[#2d7a72] transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="confirmMailjetSync">Appliquer à la liste</span>
                                <span wire:loading wire:target="confirmMailjetSync">Mise à jour en cours…</span>
                            </button>
                            <button wire:click="cancelMailjetSync"
                                class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                Annuler
                            </button>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    @endcan

    @php
        $selections = $this->selections;
        $filtered = $this->filteredSelections;
    @endphp

    @if (empty($selections))
        <div class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 p-4 border border-yellow-200 dark:border-yellow-800">
            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                Impossible de récupérer les sélections depuis Apidae. Vérifiez la configuration de l'API
                puis consultez les logs pour le détail de l'erreur.
            </p>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sélections disponibles</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($selections) }}</p>
            </div>
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Affichées</p>
                <p class="text-2xl font-bold text-blue-600">{{ count($filtered) }}</p>
            </div>
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cochées</p>
                <p class="text-2xl font-bold text-[#3E9B90]">{{ count($selected) }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
            <div class="flex flex-wrap gap-4 mb-4 items-center">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Rechercher une sélection (ex. HEB, restaurants, ANNOT…)"
                    class="flex-1 min-w-64 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500">

                <button wire:click="toggleAllFiltered"
                    class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Tout cocher / décocher
                </button>

                @if (count($selected))
                    <button wire:click="clearSelection"
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Réinitialiser
                    </button>
                @endif
            </div>

            @if (empty($filtered))
                <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    Aucune sélection ne correspond à « {{ $search }} ».
                </p>
            @else
                <div class="max-h-[32rem] overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($filtered as $selection)
                        <label class="flex items-center gap-3 py-2.5 px-1 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded">
                            <input type="checkbox" wire:model.live="selected" value="{{ $selection['id'] }}"
                                class="rounded border-gray-300 dark:border-gray-600 text-[#3E9B90] focus:ring-[#3E9B90]">
                            <span class="flex-1 text-sm text-gray-900 dark:text-white">{{ $selection['nom'] }}</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $selection['id'] }}</span>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">
            L'export interroge l'API Apidae sélection par sélection : comptez quelques secondes par sélection cochée.
            Les emails des contacts de la fiche sont ajoutés dans la colonne Email ; la colonne Origine indique « Fiche » ou « Contact ».
            Les doublons d'email sont supprimés ; les fiches sans email sont conservées avec une colonne email vide.
            Le fichier Excel contient deux onglets : « Emails » (les adresses seules, sans ligne vide) et « Détails » (nom, email, origine, sélection). Le CSV ne gère qu'une table : choisissez l'onglet à télécharger.
        </p>
    @endif
</div>
