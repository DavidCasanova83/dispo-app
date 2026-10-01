<div class="flex h-full w-full flex-1 flex-col gap-5 max-w-4xl mx-auto">
    {{-- Header --}}
    <div>
        <a href="{{ route('verification.translations.index') }}" wire:navigate
            class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">← Toutes les pages</a>
        <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $page->title }}</h1>
        <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
            @if ($page->categoryLabel())
                <span>{{ $page->categoryLabel() }}</span>
            @endif
            <a href="{{ $page->url }}" target="_blank" rel="noopener noreferrer"
                class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">🇫🇷 Ouvrir la page en français ↗</a>
        </div>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Un bloc par langue --}}
    <div class="grid gap-4 md:grid-cols-2">
        @foreach (\App\Models\TranslationCheck::LANGUAGES as $code => $lang)
            @php
                $check = $page->translationCheckFor($code);
                $url = $page->urlForLanguage($code);
                $editable = ! $check || $check->isEditableByTranslator();
            @endphp
            <section wire:key="lang-{{ $code }}"
                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 sm:p-5 flex flex-col gap-4">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $lang['flag'] }} {{ $lang['label'] }}</h2>
                    <x-verif.status-badge
                        :tone="\App\Models\TranslationCheck::statusToneFor($check)"
                        :label="\App\Models\TranslationCheck::statusLabelFor($check)" />
                </div>

                {{-- URL de la version traduite --}}
                @if ($url)
                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                        class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline break-all">
                        Ouvrir la page en {{ strtolower($lang['label']) }} ↗
                        <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::after($url, '://') }}</span>
                    </a>
                @else
                    <form wire:submit="saveUrl('{{ $code }}')" class="flex flex-col gap-2">
                        <label for="url-{{ $code }}" class="text-sm text-gray-700 dark:text-gray-300">
                            L'adresse de la page {{ $lang['short'] }} n'est pas renseignée. Collez-la ici :
                        </label>
                        <div class="flex gap-2">
                            <input id="url-{{ $code }}" type="url" wire:model="urls.{{ $code }}" placeholder="https://www.verdontourisme.com/{{ $code }}/…"
                                class="flex-1 min-w-0 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-white">
                            <button type="submit"
                                class="px-3 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200 rounded-lg">
                                Enregistrer
                            </button>
                        </div>
                        @error("urls.{$code}") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </form>
                @endif

                {{-- Dernier verdict --}}
                @if ($check?->checker)
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Vérifiée par {{ $check->checker->name }} le {{ $check->checked_at?->translatedFormat('d F Y à H:i') }}
                    </p>
                @endif

                @if ($check?->admin_response)
                    <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-3">
                        <p class="text-xs uppercase font-semibold text-blue-800 dark:text-blue-300 mb-1">Réponse de l'équipe</p>
                        <p class="text-sm text-blue-900 dark:text-blue-100 whitespace-pre-line">{{ $check->admin_response }}</p>
                    </div>
                @endif

                @if (! $editable)
                    {{-- Prise en charge par l'admin : lecture seule --}}
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900/40 p-3 text-sm text-gray-700 dark:text-gray-300">
                        @if ($check->status === 'in_progress')
                            🛠️ Les corrections sont en cours.
                        @else
                            ✅ Corrections faites et validées
                            @if ($check->handled_at) le {{ $check->handled_at->translatedFormat('d F Y') }}@endif.
                        @endif
                        @if ($check->comment)
                            <p class="mt-2 text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Corrections demandées</p>
                            <p class="mt-0.5 whitespace-pre-line">{{ $check->comment }}</p>
                        @endif
                    </div>
                @elseif ($url)
                    <form wire:submit="submit('{{ $code }}')" class="flex flex-col gap-3">
                        <fieldset>
                            <legend class="text-sm font-medium text-gray-900 dark:text-white mb-2">La traduction est-elle correcte ?</legend>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center gap-2 rounded-lg border px-3 py-2 cursor-pointer text-sm
                                    {{ $verdicts[$code] === 'correct' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                                    <input type="radio" value="correct" wire:model.live="verdicts.{{ $code }}" class="text-green-600">
                                    <span class="text-gray-900 dark:text-white">✅ Oui, correcte</span>
                                </label>
                                <label class="flex items-center gap-2 rounded-lg border px-3 py-2 cursor-pointer text-sm
                                    {{ $verdicts[$code] === 'to_fix' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                                    <input type="radio" value="to_fix" wire:model.live="verdicts.{{ $code }}" class="text-red-600">
                                    <span class="text-gray-900 dark:text-white">✏️ Non, à corriger</span>
                                </label>
                            </div>
                            @error("verdicts.{$code}") <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </fieldset>

                        @if ($verdicts[$code] === 'to_fix')
                            <div>
                                <label for="comment-{{ $code }}" class="text-sm font-medium text-gray-900 dark:text-white">Corrections à apporter</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    Citez le passage concerné et proposez la bonne traduction.
                                </p>
                                <textarea id="comment-{{ $code }}" wire:model="comments.{{ $code }}" rows="6"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-white"
                                    placeholder="Ex. : 2e paragraphe, « booking the room » → « booking a room »"></textarea>
                                @error("comments.{$code}") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <button type="submit" wire:loading.attr="disabled"
                            class="inline-flex justify-center px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg disabled:opacity-60">
                            {{ $check ? 'Mettre à jour' : 'Enregistrer' }} le verdict {{ $lang['short'] }}
                        </button>
                    </form>
                @endif
            </section>
        @endforeach
    </div>

    {{-- Enchaînement --}}
    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-gray-200 dark:border-gray-700 pt-4">
        <a href="{{ route('verification.translations.index') }}" wire:navigate
            class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">← Retour à la liste</a>
        @if ($nextPage)
            <a href="{{ route('verification.translations.form', $nextPage) }}" wire:navigate
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-700 bg-indigo-100 hover:bg-indigo-200 dark:text-indigo-200 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 rounded-lg">
                Page suivante à vérifier : {{ \Illuminate\Support\Str::limit($nextPage->title, 40) }} →
            </a>
        @endif
    </div>
</div>
