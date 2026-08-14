<div class="flex h-full w-full flex-1 flex-col gap-6 max-w-2xl mx-auto"
    x-data="verificationFormDraft({{ $page->id }}, {{ auth()->id() }}, '{{ $language }}')">

    {{-- Back link --}}
    <div>
        <a href="{{ route('verification.index') }}" wire:navigate
            class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
            ← Retour au dashboard
        </a>
    </div>

    {{-- Title --}}
    <div>
        <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 dark:text-white">
            {{ $page->title }}
        </h1>
        <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
            <span>{{ $page->priorityLabel() }}</span>
            @if ($page->theme)
                <span>·</span>
                <span>{{ $page->theme }}</span>
            @endif
        </div>
    </div>

    {{-- Language selector --}}
    <div class="flex flex-wrap items-center gap-2 text-sm">
        <span class="text-gray-500 dark:text-gray-400">Langue :</span>
        @foreach (['fr', 'en', 'it'] as $code)
            @php
                $label = strtoupper($code);
                $available = $code === 'fr' ? true : ! empty($page->urlForLanguage($code));
                $isActive = $language === $code;
            @endphp
            @if ($isActive)
                <span class="inline-flex items-center px-2.5 py-1 font-medium rounded border border-gray-900 bg-gray-900 text-white dark:bg-white dark:text-gray-900 dark:border-white">
                    {{ $label }}
                </span>
            @elseif ($available)
                <button type="button" wire:click="switchLanguage('{{ $code }}')"
                    class="inline-flex items-center px-2.5 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    {{ $label }}
                </button>
            @else
                <span class="inline-flex items-center px-2.5 py-1 rounded border border-gray-200 dark:border-gray-700 text-gray-400 dark:text-gray-600 cursor-not-allowed"
                    title="L'URL {{ $label }} n'est pas renseignée pour cette page">
                    {{ $label }}
                </span>
            @endif
        @endforeach
    </div>

    {{-- Bandeau message admin --}}
    @if ($adminMessage)
        <div class="rounded-lg border border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 px-4 py-3">
            <p class="text-xs uppercase font-semibold text-blue-800 dark:text-blue-300 mb-1">
                @if ($existingReviewStatus === 'revision_requested')
                    Ré-vérification demandée — message de l'admin
                @else
                    Message de l'admin
                @endif
            </p>
            <p class="text-sm text-blue-900 dark:text-blue-100 whitespace-pre-line">{{ $adminMessage }}</p>
            @if ($adminMessageAt)
                <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">{{ $adminMessageAt }}</p>
            @endif
        </div>
    @endif

    {{-- Bandeau "déjà soumis" --}}
    @if ($existingReviewSubmittedAt)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
            Vous avez déjà soumis cette vérification le {{ $existingReviewSubmittedAt }}. Vos réponses ci-dessous sont pré-remplies. Toute modification remplacera votre soumission précédente.
        </div>
    @endif

    {{-- Ouvrir la page --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm text-gray-700 dark:text-gray-300">
            Avant de répondre, ouvrez la page et lisez son contenu.
        </p>
        <a href="{{ $page->urlForLanguage($language) }}" target="_blank" rel="noopener noreferrer"
            class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded whitespace-nowrap">
            Ouvrir la page {{ strtoupper($language) }} ↗
        </a>
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- ÉCRAN DE CHOIX                                              --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if ($mode === 'choice')
        <div class="space-y-3">
            {{-- Validation express : la carte reste informative, seul le bouton engage --}}
            <div class="rounded-xl border-2 border-green-600 dark:border-green-500 bg-green-50 dark:bg-green-900/20 px-5 py-5">
                <p class="text-lg font-semibold text-green-900 dark:text-green-200">
                    ✅ Tout est bon sur cette page
                </p>
                <p class="mt-1 text-sm text-green-800 dark:text-green-300">
                    Le contenu est à jour, les images et les liens fonctionnent. Validation immédiate, rien d'autre à remplir.
                </p>

                <div class="mt-4" x-data="{ confirming: false }">
                    {{-- Premier clic : on demande confirmation sur place, sans boîte de dialogue --}}
                    <button type="button" x-show="! confirming" @click="confirming = true"
                        class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 rounded-lg transition-colors">
                        Valider cette page
                    </button>

                    <div x-show="confirming" x-cloak class="flex flex-col sm:flex-row sm:items-center gap-2">
                        <span class="text-sm text-green-900 dark:text-green-200">
                            Vous confirmez avoir vérifié le contenu, les images et les liens ?
                        </span>
                        <div class="flex gap-2">
                            <button type="button"
                                wire:click="quickValidate"
                                wire:loading.attr="disabled"
                                wire:target="quickValidate"
                                @click="clearDraft()"
                                class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 rounded-lg transition-colors disabled:opacity-50">
                                <span wire:loading.remove wire:target="quickValidate">Oui, valider</span>
                                <span wire:loading wire:target="quickValidate">Envoi…</span>
                            </button>
                            <button type="button" @click="confirming = false"
                                class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-green-900 dark:text-green-200 border border-green-600/40 dark:border-green-500/40 hover:bg-green-100 dark:hover:bg-green-900/40 rounded-lg">
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Questionnaire complet --}}
            <div class="rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-5 py-5">
                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                    📝 Répondre au questionnaire
                </p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Pour signaler un problème, ou donner votre avis sur la page. 3 questions obligatoires, 6 optionnelles.
                </p>
                <button type="button" wire:click="startWizard"
                    class="mt-4 inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded-lg transition-colors">
                    Commencer le questionnaire
                </button>
            </div>
        </div>

    @else
    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- ASSISTANT PAS-À-PAS                                         --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}

        {{-- Progression --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                    Question {{ $step }} sur {{ \App\Livewire\Verification\VerificationForm::LAST_STEP }}
                </span>
                <span class="text-xs {{ $step < \App\Livewire\Verification\VerificationForm::FIRST_OPTIONAL_STEP
                        ? 'text-gray-600 dark:text-gray-400'
                        : 'text-gray-400 dark:text-gray-500' }}">
                    {{ $step < \App\Livewire\Verification\VerificationForm::FIRST_OPTIONAL_STEP ? 'Section obligatoire' : 'Section optionnelle' }}
                </span>
            </div>
            <div class="flex gap-1">
                @for ($i = 1; $i <= \App\Livewire\Verification\VerificationForm::LAST_STEP; $i++)
                    <span class="h-1.5 flex-1 rounded-full transition-colors
                        {{ $i < $step ? 'bg-gray-900 dark:bg-white' : ($i === $step ? 'bg-gray-500 dark:bg-gray-300' : 'bg-gray-200 dark:bg-gray-700') }}"></span>
                @endfor
            </div>
        </div>

        <form wire:submit.prevent="submit" class="space-y-6">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 sm:p-6">

                {{-- ─── Q1 ─── --}}
                @if ($step === 1)
                    <p class="text-base font-medium text-gray-900 dark:text-white mb-3">
                        Le contenu de la page est-il exact et à jour ?
                    </p>
                    <div class="space-y-2">
                        <label class="flex items-start gap-3 p-3 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer">
                            <input type="radio" wire:model.live="contentOk" value="1"
                                class="mt-0.5 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white">
                            <span class="text-sm text-gray-900 dark:text-white">Oui, tout est correct</span>
                        </label>
                        <label class="flex items-start gap-3 p-3 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer">
                            <input type="radio" wire:model.live="contentOk" value="0"
                                class="mt-0.5 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white">
                            <span class="text-sm text-gray-900 dark:text-white">Non, il y a des erreurs ou des choses à modifier</span>
                        </label>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Vérifiez : tarifs, horaires, dates, contacts, informations pratiques, descriptions.
                    </p>
                    @error('contentOk') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                    @if ($contentOk === false)
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-1.5">
                                Décrivez le problème de contenu <span class="text-red-600 font-normal">— obligatoire</span>
                            </label>
                            <textarea wire:model.blur="contentComment" x-model="draft.contentComment"
                                rows="6" maxlength="5000"
                                placeholder="Ex : le tarif indiqué est 12 €, mais c'est 14 € depuis janvier…"
                                class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"></textarea>
                            @error('contentComment') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                {{-- ─── Q2 ─── --}}
                @elseif ($step === 2)
                    <p class="text-base font-medium text-gray-900 dark:text-white mb-3">
                        Les images et les liens fonctionnent-ils ?
                    </p>
                    <div class="space-y-2">
                        <label class="flex items-start gap-3 p-3 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer">
                            <input type="radio" wire:model.live="mediaOk" value="1"
                                class="mt-0.5 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white">
                            <span class="text-sm text-gray-900 dark:text-white">Oui, tout fonctionne et les images sont pertinentes</span>
                        </label>
                        <label class="flex items-start gap-3 p-3 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer">
                            <input type="radio" wire:model.live="mediaOk" value="0"
                                class="mt-0.5 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white">
                            <span class="text-sm text-gray-900 dark:text-white">Non, il y a des problèmes</span>
                        </label>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Vérifiez : photos représentatives, liens cliquables qui mènent à la bonne page, pas d'image cassée.
                    </p>
                    @error('mediaOk') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                    @if ($mediaOk === false)
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-1.5">
                                Décrivez le problème de médias / liens <span class="text-red-600 font-normal">— obligatoire</span>
                            </label>
                            <textarea wire:model.blur="mediaComment" x-model="draft.mediaComment"
                                rows="6" maxlength="5000"
                                placeholder="Ex : la 2ᵉ photo est cassée, le lien vers la page tarifs renvoie une erreur 404…"
                                class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"></textarea>
                            @error('mediaComment') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                {{-- ─── Q3 ─── --}}
                @elseif ($step === 3)
                    <p class="text-base font-medium text-gray-900 dark:text-white mb-2">
                        Remarques ou suggestions
                        <span class="text-gray-500 text-sm font-normal">— optionnel</span>
                    </p>
                    <textarea wire:model.blur="remarks" x-model="draft.remarks"
                        rows="8" maxlength="5000"
                        placeholder="Une suggestion d'amélioration, une idée, un détail à signaler…"
                        class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"></textarea>
                    @error('remarks') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                    <div class="mt-5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            L'essentiel est renseigné. Vous pouvez envoyer votre vérification dès maintenant,
                            ou continuer avec 6 questions optionnelles sur la qualité éditoriale de la page.
                        </p>
                    </div>

                {{-- ─── Q4 ─── --}}
                @elseif ($step === 4)
                    <p class="text-base font-medium text-gray-900 dark:text-white mb-3">
                        Le contenu de cette page vous semble-t-il pertinent ?
                    </p>
                    <div class="space-y-2">
                        @foreach (\App\Models\VerificationReview::RELEVANCE_LABELS as $code => $label)
                            <label class="flex items-start gap-3 p-3 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer">
                                <input type="radio" wire:model.live="relevance" value="{{ $code }}"
                                    class="mt-0.5 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white">
                                <span class="text-sm text-gray-900 dark:text-white">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Mettez-vous à la place d'un visiteur qui découvre votre territoire.
                    </p>
                    @error('relevance') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                {{-- ─── Q5 ─── --}}
                @elseif ($step === 5)
                    <p class="text-base font-medium text-gray-900 dark:text-white mb-3">
                        Faudrait-il ajouter du contenu sur cette page ?
                    </p>
                    <div class="space-y-2">
                        @foreach (\App\Models\VerificationReview::CONTENT_TO_ADD_OPTIONS as $code => $label)
                            <label class="flex items-start gap-3 p-3 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer">
                                <input type="checkbox" wire:model.live="contentToAdd" value="{{ $code }}"
                                    class="mt-0.5 rounded text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white">
                                <span class="text-sm text-gray-900 dark:text-white">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @php $hasYesAdd = ! empty(array_filter($contentToAdd, fn ($c) => $c !== 'none')); @endphp
                    @if ($hasYesAdd)
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-1.5">
                                Précisez ce qu'il faudrait ajouter
                                <span class="text-gray-500 font-normal">— optionnel</span>
                            </label>
                            <textarea wire:model.blur="contentToAddDetails" x-model="draft.contentToAddDetails"
                                rows="5" maxlength="5000"
                                placeholder="Ex : nom du prestataire, type d'info précise…"
                                class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"></textarea>
                            @error('contentToAddDetails') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                {{-- ─── Q6 ─── --}}
                @elseif ($step === 6)
                    <p class="text-base font-medium text-gray-900 dark:text-white mb-3">
                        Y a-t-il du contenu à supprimer ou à alléger ?
                    </p>
                    <div class="space-y-2">
                        @foreach (\App\Models\VerificationReview::CONTENT_TO_REMOVE_LABELS as $code => $label)
                            <label class="flex items-start gap-3 p-3 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer">
                                <input type="radio" wire:model.live="contentToRemove" value="{{ $code }}"
                                    class="mt-0.5 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white">
                                <span class="text-sm text-gray-900 dark:text-white">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('contentToRemove') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    @if ($contentToRemove === 'yes')
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-1.5">
                                Qu'est-ce qui mériterait d'être supprimé ou allégé ?
                                <span class="text-gray-500 font-normal">— optionnel</span>
                            </label>
                            <textarea wire:model.blur="contentToRemoveDetails" x-model="draft.contentToRemoveDetails"
                                rows="5" maxlength="5000"
                                placeholder="Ex : un paragraphe trop long, une info périmée, une image qui ne représente plus l'activité…"
                                class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"></textarea>
                            @error('contentToRemoveDetails') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                {{-- ─── Q7 ─── --}}
                @elseif ($step === 7)
                    <p class="text-base font-medium text-gray-900 dark:text-white mb-3">
                        Si vous étiez un visiteur, cette page vous suffirait-elle ?
                    </p>
                    <div class="space-y-2">
                        @foreach (\App\Models\VerificationReview::VISITOR_PERSPECTIVE_LABELS as $code => $label)
                            <label class="flex items-start gap-3 p-3 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer">
                                <input type="radio" wire:model.live="visitorPerspective" value="{{ $code }}"
                                    class="mt-0.5 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white">
                                <span class="text-sm text-gray-900 dark:text-white">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('visitorPerspective') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    @if (in_array($visitorPerspective, ['partial', 'no'], true))
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-1.5">
                                Quelle question resterait sans réponse ?
                                <span class="text-gray-500 font-normal">— optionnel</span>
                            </label>
                            <textarea wire:model.blur="visitorUnanswered" x-model="draft.visitorUnanswered"
                                rows="5" maxlength="5000"
                                placeholder="Ex : « Comment y accéder en transports en commun ? »"
                                class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"></textarea>
                            @error('visitorUnanswered') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                {{-- ─── Q8 ─── --}}
                @elseif ($step === 8)
                    <p class="text-base font-medium text-gray-900 dark:text-white mb-2">
                        Note globale de la page
                        <span class="text-gray-500 text-sm font-normal">— optionnel</span>
                    </p>
                    <div class="flex items-center gap-2">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button" wire:click="setRating({{ $i }})"
                                class="text-3xl leading-none transition-transform hover:scale-110 focus:outline-none {{ ($rating !== null && $i <= $rating) ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600' }}"
                                title="{{ $i }} étoile{{ $i > 1 ? 's' : '' }}">★</button>
                        @endfor
                        @if ($rating !== null)
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                @switch($rating)
                                    @case(1) à refaire complètement @break
                                    @case(2) beaucoup à améliorer @break
                                    @case(3) correct, peut être amélioré @break
                                    @case(4) très bonne page @break
                                    @case(5) excellente page @break
                                @endswitch
                            </span>
                        @endif
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Cliquez sur une étoile pour noter. Re-cliquez pour annuler.
                    </p>
                    @error('rating') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                {{-- ─── Q9 ─── --}}
                @elseif ($step === 9)
                    <p class="text-base font-medium text-gray-900 dark:text-white mb-2">
                        Idées d'amélioration pour cette page
                        <span class="text-gray-500 text-sm font-normal">— optionnel</span>
                    </p>
                    <textarea wire:model.blur="suggestions" x-model="draft.suggestions"
                        rows="6" maxlength="5000"
                        placeholder="Toutes les idées sont bienvenues : nouveau bloc, photo à ajouter, restructuration, ton à changer…"
                        class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"></textarea>
                    @error('suggestions') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                @endif
            </div>

            {{-- ─── Navigation ─── --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    @if ($step > 1)
                        <button type="button" wire:click="previousStep"
                            class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                            ← Précédent
                        </button>
                    @else
                        <button type="button" wire:click="backToChoice"
                            class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                            ← Retour
                        </button>
                    @endif
                </div>

                <div class="flex flex-col sm:flex-row gap-2">
                    @if ($step === 3)
                        <button type="button" wire:click="goToOptional"
                            class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                            Continuer (6 questions)
                        </button>
                        <button type="submit" @click="clearDraft()"
                            class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded">
                            Envoyer ma vérification
                        </button>
                    @elseif ($step === \App\Livewire\Verification\VerificationForm::LAST_STEP)
                        <button type="submit" @click="clearDraft()"
                            class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded">
                            Envoyer ma vérification
                        </button>
                    @else
                        @if ($step >= \App\Livewire\Verification\VerificationForm::FIRST_OPTIONAL_STEP)
                            <button type="submit" @click="clearDraft()"
                                class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                                Envoyer maintenant
                            </button>
                        @endif
                        <button type="button" wire:click="nextStep"
                            class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded">
                            Suivant →
                        </button>
                    @endif
                </div>
            </div>
        </form>
    @endif
</div>

@push('scripts')
<script>
function verificationFormDraft(pageId, userId, language) {
    const key = `verification-draft-${pageId}-${userId}-${language}`;

    // Champs texte : conservés côté Alpine pour être sauvegardés à chaque frappe,
    // sans attendre le blur qui synchronise Livewire.
    const textFields = [
        'contentComment', 'mediaComment', 'remarks', 'contentToAddDetails',
        'contentToRemoveDetails', 'visitorUnanswered', 'suggestions',
    ];

    // Réponses fermées + position dans l'assistant : lues depuis Livewire.
    // Elles étaient absentes du brouillon, et donc perdues à chaque rechargement.
    const wireFields = [
        'contentOk', 'mediaOk', 'relevance', 'contentToAdd', 'contentToRemove',
        'visitorPerspective', 'rating', 'mode', 'step',
    ];

    const draft = {};
    textFields.forEach(f => { draft[f] = ''; });

    return {
        draft,
        restoring: false,

        init() {
            let saved = null;
            try {
                saved = JSON.parse(localStorage.getItem(key) || 'null');
            } catch (e) { /* brouillon illisible : on repart de zéro */ }

            if (saved) {
                this.restoring = true;
                textFields.forEach(f => {
                    if (saved[f]) {
                        this.draft[f] = saved[f];
                        this.$wire.set(f, saved[f], false);
                    }
                });
                wireFields.forEach(f => {
                    if (saved[f] !== undefined && saved[f] !== null && saved[f] !== '') {
                        this.$wire.set(f, saved[f], false);
                    }
                });
                this.$nextTick(() => { this.restoring = false; });
            }

            textFields.forEach(f => this.$watch(`draft.${f}`, () => this.persist()));
            wireFields.forEach(f => this.$wire.$watch(f, () => this.persist()));
        },

        persist() {
            if (this.restoring) return;
            try {
                const out = {};
                textFields.forEach(f => { out[f] = this.draft[f]; });
                wireFields.forEach(f => { out[f] = this.$wire.get(f); });
                localStorage.setItem(key, JSON.stringify(out));
            } catch (e) { /* quota dépassé ou stockage indisponible */ }
        },

        clearDraft() {
            try { localStorage.removeItem(key); } catch (e) { /* ignore */ }
        },
    };
}
</script>
@endpush
