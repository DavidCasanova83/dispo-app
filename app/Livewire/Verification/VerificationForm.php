<?php

namespace App\Livewire\Verification;

use App\Models\VerificationPage;
use App\Models\VerificationReview;
use App\Services\VerificationReviewService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class VerificationForm extends Component
{
    public VerificationPage $page;
    public string $language = 'fr';

    /**
     * 'choice'  : écran d'entrée — validation express ou questionnaire
     * 'wizard'  : questionnaire pas-à-pas
     */
    public string $mode = 'choice';
    public int $step = 1;

    /** Étapes 1 à 3 = section obligatoire, 4 à 9 = section optionnelle. */
    public const FIRST_OPTIONAL_STEP = 4;
    public const LAST_STEP = 9;

    // Bandeau "déjà soumis" (mis à jour à mount() et lors d'un switchLanguage).
    public ?string $existingReviewSubmittedAt = null;
    public ?string $existingReviewStatus = null;

    // Message de l'admin associé à la review précédente (visible si re-vérification demandée).
    public ?string $adminMessage = null;
    public ?string $adminMessageAt = null;

    // Section 1 — Vérification factuelle (obligatoire)
    public ?bool $contentOk = null;
    public string $contentComment = '';
    public ?bool $mediaOk = null;
    public string $mediaComment = '';
    public string $remarks = '';

    // Section 2 — Évaluation éditoriale (optionnelle)
    public ?string $relevance = null;
    /**
     * Volontairement non typée : Livewire assigne la valeur brute envoyée par
     * le client, et une propriété `array` lève alors une TypeError (500) dès
     * qu'autre chose qu'un tableau arrive — un brouillon localStorage périmé
     * suffisait. La normalisation est faite dans updatedContentToAdd().
     *
     * @var array<int, string>
     */
    public $contentToAdd = [];
    public string $contentToAddDetails = '';
    public ?string $contentToRemove = null;
    public string $contentToRemoveDetails = '';
    public ?string $visitorPerspective = null;
    public string $visitorUnanswered = '';
    public ?int $rating = null;
    public string $suggestions = '';

    protected VerificationReviewService $service;

    public function boot(VerificationReviewService $service): void
    {
        $this->service = $service;
    }

    public function mount(VerificationPage $page): void
    {
        $user = auth()->user();

        // L'assignation ne suffit pas : elle doit avoir été libérée. Sans ce
        // contrôle, une URL devinée donnait accès à une page encore en file
        // d'attente, ce qui contournait le plafond de pages actives.
        $isReleasedToUser = $page->assignees()
            ->where('users.id', $user->id)
            ->whereNotNull('verification_assignments.released_at')
            ->exists();

        if (! $isReleasedToUser) {
            throw new AuthorizationException('Cette page ne vous est pas assignée, ou ne vous a pas encore été attribuée.');
        }

        $this->page = $page;

        $requested = strtolower((string) request()->query('lang', 'fr'));
        if (! in_array($requested, ['fr', 'en', 'it'], true)) {
            $requested = 'fr';
        }
        // On refuse de basculer sur une langue dont l'URL n'est pas renseignée.
        if ($requested !== 'fr' && ! $page->urlForLanguage($requested)) {
            $requested = 'fr';
        }
        $this->language = $requested;

        $this->loadExistingReviewIfAny();

        // Une relecture déjà soumise ou une re-vérification demandée : on ouvre
        // directement le questionnaire, l'écran de choix n'aurait pas de sens.
        if ($this->existingReviewSubmittedAt !== null) {
            $this->mode = 'wizard';
        }
    }

    // ─── NAVIGATION ───────────────────────────────────────────────

    public function startWizard(): void
    {
        $this->mode = 'wizard';
        $this->step = 1;
        $this->resetErrorBag();
    }

    public function backToChoice(): void
    {
        $this->mode = 'choice';
        $this->resetErrorBag();
    }

    public function nextStep(): void
    {
        $this->validate($this->rulesForStep($this->step), $this->messages());

        if ($this->step < self::LAST_STEP) {
            $this->step++;
        }
    }

    public function previousStep(): void
    {
        $this->resetErrorBag();

        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function goToOptional(): void
    {
        $this->validate($this->rulesForStep($this->step), $this->messages());
        $this->step = self::FIRST_OPTIONAL_STEP;
    }

    /**
     * Règles applicables à une seule étape : l'erreur s'affiche là où elle se
     * produit, au lieu d'attendre l'envoi final.
     *
     * @return array<string, string>
     */
    protected function rulesForStep(int $step): array
    {
        return array_intersect_key($this->rules(), array_flip(self::FIELDS_BY_STEP[$step] ?? []));
    }

    /** Champs rattachés à chaque étape : sert aux règles et au saut vers l'erreur. */
    private const FIELDS_BY_STEP = [
        1 => ['contentOk', 'contentComment'],
        2 => ['mediaOk', 'mediaComment'],
        3 => ['remarks'],
        4 => ['relevance'],
        5 => ['contentToAdd', 'contentToAdd.*', 'contentToAddDetails'],
        6 => ['contentToRemove', 'contentToRemoveDetails'],
        7 => ['visitorPerspective', 'visitorUnanswered'],
        8 => ['rating'],
        9 => ['suggestions'],
    ];

    /**
     * Étape où le champ fautif est affiché. Sans ce saut, une erreur sur une
     * question précédente restait invisible : le bouton d'envoi semblait
     * simplement ne rien faire.
     */
    private function stepForField(string $field): ?int
    {
        $field = explode('.', $field)[0];

        foreach (self::FIELDS_BY_STEP as $step => $fields) {
            foreach ($fields as $candidate) {
                if (explode('.', $candidate)[0] === $field) {
                    return $step;
                }
            }
        }

        return null;
    }

    /**
     * Les réponses fermées optionnelles peuvent arriver corrompues (brouillon
     * localStorage d'une version antérieure, payload forgé). On les remet à
     * null plutôt que de laisser la validation échouer sur une question que
     * l'utilisateur ne voit pas — l'envoi paraissait alors sans effet.
     */
    private function sanitizeOptionalAnswers(): void
    {
        $keep = function (mixed $value, array $allowed): ?string {
            return is_string($value) && in_array($value, $allowed, true) ? $value : null;
        };

        $this->relevance = $keep($this->relevance, array_keys(VerificationReview::RELEVANCE_LABELS));
        $this->contentToRemove = $keep($this->contentToRemove, array_keys(VerificationReview::CONTENT_TO_REMOVE_LABELS));
        $this->visitorPerspective = $keep($this->visitorPerspective, array_keys(VerificationReview::VISITOR_PERSPECTIVE_LABELS));
        $this->contentToAdd = $this->normalizeContentToAdd($this->contentToAdd);

        if ($this->rating !== null && ($this->rating < 1 || $this->rating > 5)) {
            $this->rating = null;
        }
    }

    /**
     * Validation express : le cas courant, où la page n'appelle aucune remarque.
     * Renseigne les deux questions obligatoires et soumet, sans faire dérouler
     * les neuf questions.
     */
    public function quickValidate()
    {
        $this->contentOk = true;
        $this->mediaOk = true;
        $this->contentComment = '';
        $this->mediaComment = '';

        return $this->submit();
    }

    /**
     * Si l'utilisateur a déjà soumis une review pour cette page+langue,
     * pré-remplir le formulaire avec les valeurs existantes et exposer
     * la date de soumission pour afficher un bandeau d'information.
     */
    private function loadExistingReviewIfAny(): void
    {
        $existing = VerificationReview::query()
            ->where('page_id', $this->page->id)
            ->where('user_id', auth()->id())
            ->where('language', $this->language)
            ->first();

        if (! $existing) {
            $this->existingReviewSubmittedAt = null;
            $this->existingReviewStatus = null;
            $this->adminMessage = null;
            $this->adminMessageAt = null;
            return;
        }

        $this->existingReviewSubmittedAt = $existing->updated_at?->translatedFormat('d F Y à H:i');
        $this->existingReviewStatus = $existing->status;
        $this->adminMessage = $existing->admin_response ?: null;
        $this->adminMessageAt = $existing->admin_response_at?->translatedFormat('d F Y à H:i');

        $this->contentOk = $existing->content_ok;
        $this->contentComment = $existing->content_comment ?? '';
        $this->mediaOk = $existing->media_ok;
        $this->mediaComment = $existing->media_comment ?? '';
        $this->remarks = $existing->remarks ?? '';
        $this->relevance = $existing->relevance;
        $this->contentToAdd = $this->normalizeContentToAdd($existing->content_to_add);
        $this->contentToAddDetails = $existing->content_to_add_details ?? '';
        $this->contentToRemove = $existing->content_to_remove;
        $this->contentToRemoveDetails = $existing->content_to_remove_details ?? '';
        $this->visitorPerspective = $existing->visitor_perspective;
        $this->visitorUnanswered = $existing->visitor_unanswered ?? '';
        $this->rating = $existing->rating;
        $this->suggestions = $existing->suggestions ?? '';
    }

    public function switchLanguage(string $lang)
    {
        if (! in_array($lang, ['fr', 'en', 'it'], true)) {
            return;
        }
        if ($lang !== 'fr' && ! $this->page->urlForLanguage($lang)) {
            return;
        }
        return redirect()->route('verification.form', ['page' => $this->page->id, 'lang' => $lang]);
    }

    public function updatedContentToAdd($value, $key): void
    {
        // Le client peut envoyer n'importe quoi (brouillon localStorage, payload
        // forgé) : on ramène toujours la propriété à une liste de codes connus.
        $this->contentToAdd = $this->normalizeContentToAdd($this->contentToAdd);

        // Si "none" vient d'être coché, on déselectionne les autres.
        if (in_array('none', $this->contentToAdd, true)) {
            $latest = end($this->contentToAdd);
            if ($latest === 'none') {
                $this->contentToAdd = ['none'];
            } else {
                // Une autre case a été cochée alors que "none" l'était : on retire "none".
                $this->contentToAdd = array_values(array_filter($this->contentToAdd, fn ($v) => $v !== 'none'));
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function normalizeContentToAdd(mixed $value): array
    {
        $codes = match (true) {
            is_array($value) => $value,
            is_string($value) && $value !== '' => [$value],
            default => [],
        };

        $allowed = array_keys(VerificationReview::CONTENT_TO_ADD_OPTIONS);

        return array_values(array_filter(
            $codes,
            fn ($code) => is_string($code) && in_array($code, $allowed, true),
        ));
    }

    protected function rules(): array
    {
        $rules = [
            // Section 1
            'contentOk' => 'required|boolean',
            'mediaOk' => 'required|boolean',
            'contentComment' => 'nullable|string|max:5000',
            'mediaComment' => 'nullable|string|max:5000',
            'remarks' => 'nullable|string|max:5000',

            // Section 2 — toutes optionnelles
            'relevance' => 'nullable|in:very,rather,medium,low',
            'contentToAdd' => 'array',
            'contentToAdd.*' => 'in:practical_info,media,providers,context,links,none',
            'contentToAddDetails' => 'nullable|string|max:5000',
            'contentToRemove' => 'nullable|in:no,yes,dont_know',
            'contentToRemoveDetails' => 'nullable|string|max:5000',
            'visitorPerspective' => 'nullable|in:yes,partial,no',
            'visitorUnanswered' => 'nullable|string|max:5000',
            'rating' => 'nullable|integer|min:1|max:5',
            'suggestions' => 'nullable|string|max:5000',
        ];

        if ($this->contentOk === false) {
            $rules['contentComment'] = 'required|string|min:10|max:5000';
        }

        if ($this->mediaOk === false) {
            $rules['mediaComment'] = 'required|string|min:10|max:5000';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'contentOk.required' => 'Veuillez répondre à la question 1.',
            'mediaOk.required' => 'Veuillez répondre à la question 2.',
            'contentComment.required' => 'Vous avez signalé un problème de contenu, merci de préciser ce qui ne va pas.',
            'contentComment.min' => 'Merci de décrire le problème de contenu en au moins 10 caractères.',
            'contentComment.max' => 'Le commentaire ne doit pas dépasser 5000 caractères.',
            'mediaComment.required' => 'Vous avez signalé un problème de médias/liens, merci de préciser ce qui ne va pas.',
            'mediaComment.min' => 'Merci de décrire le problème de médias/liens en au moins 10 caractères.',
            'mediaComment.max' => 'Le commentaire ne doit pas dépasser 5000 caractères.',
            'remarks.max' => 'Vos remarques ne doivent pas dépasser 5000 caractères.',
            'rating.min' => 'La note doit être entre 1 et 5.',
            'rating.max' => 'La note doit être entre 1 et 5.',
            'contentToAddDetails.max' => 'La précision ne doit pas dépasser 5000 caractères.',
            'contentToRemoveDetails.max' => 'La précision ne doit pas dépasser 5000 caractères.',
            'visitorUnanswered.max' => 'La précision ne doit pas dépasser 5000 caractères.',
            'suggestions.max' => 'Vos suggestions ne doivent pas dépasser 5000 caractères.',
        ];
    }

    public function setRating(int $value): void
    {
        $this->rating = ($this->rating === $value) ? null : $value;
    }

    public function submit()
    {
        $this->sanitizeOptionalAnswers();

        try {
            $data = $this->validate();
        } catch (ValidationException $e) {
            // On ramène l'utilisateur sur la question fautive : autrement le
            // message s'affichait dans une étape non rendue, et le clic sur
            // « Envoyer » restait sans effet visible.
            $firstField = array_key_first($e->validator->errors()->toArray());
            $step = $firstField ? $this->stepForField($firstField) : null;

            if ($step !== null) {
                $this->mode = 'wizard';
                $this->step = $step;
            }

            throw $e;
        }

        $review = $this->service->submit($this->page, auth()->user(), [
            'language' => $this->language,
            'content_ok' => $data['contentOk'],
            'content_comment' => $data['contentComment'] ?? '',
            'media_ok' => $data['mediaOk'],
            'media_comment' => $data['mediaComment'] ?? '',
            'remarks' => $data['remarks'] ?? '',
            'relevance' => $data['relevance'] ?? null,
            'content_to_add' => $data['contentToAdd'] ?? [],
            'content_to_add_details' => $data['contentToAddDetails'] ?? '',
            'content_to_remove' => $data['contentToRemove'] ?? null,
            'content_to_remove_details' => $data['contentToRemoveDetails'] ?? '',
            'visitor_perspective' => $data['visitorPerspective'] ?? null,
            'visitor_unanswered' => $data['visitorUnanswered'] ?? '',
            'rating' => $data['rating'] ?? null,
            'suggestions' => $data['suggestions'] ?? '',
        ]);

        $langLabel = strtoupper($this->language);

        if ($this->language === 'fr') {
            $message = $review->status === 'done'
                ? "Merci ! La page \"{$this->page->title}\" est validée. Bon travail !"
                : "Merci ! Vous avez signalé des points à corriger sur \"{$this->page->title}\". David va les examiner.";
        } else {
            $message = "Merci ! Votre vérification {$langLabel} de \"{$this->page->title}\" a bien été enregistrée.";
        }

        session()->flash('success', $message);

        return redirect()->route('verification.index');
    }

    public function render()
    {
        return view('livewire.verification.verification-form')
            ->layout('components.layouts.app');
    }
}
