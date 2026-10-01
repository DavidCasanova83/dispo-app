<?php

namespace App\Livewire\Verification\Translation;

use App\Models\TranslationCheck;
use App\Models\VerificationPage;
use App\Services\TranslationCheckService;
use Livewire\Component;

/**
 * Vérification des traductions d'une page : un bloc par langue (EN, IT),
 * chacun avec son URL, son verdict et, si besoin, les corrections à apporter.
 */
class TranslationForm extends Component
{
    public VerificationPage $page;

    /** @var array<string, string> URL saisie pour une langue sans URL */
    public array $urls = [];

    /** @var array<string, string> 'correct' | 'to_fix' | '' */
    public array $verdicts = [];

    /** @var array<string, string> */
    public array $comments = [];

    protected TranslationCheckService $service;

    public function boot(TranslationCheckService $service): void
    {
        $this->service = $service;
    }

    public function mount(VerificationPage $page): void
    {
        // Une page sortie du sitemap n'est plus proposée à la vérification.
        abort_unless(VerificationPage::forTranslation()->whereKey($page->id)->exists(), 404);

        $this->page = $page;

        foreach (array_keys(TranslationCheck::LANGUAGES) as $language) {
            $check = $page->translationCheckFor($language);
            $this->urls[$language] = '';
            $this->verdicts[$language] = $check && in_array($check->status, ['correct', 'to_fix'], true) ? $check->status : '';
            $this->comments[$language] = $check?->comment ?? '';
        }
    }

    public function saveUrl(string $language): void
    {
        $this->ensureLanguage($language);

        $this->validate([
            "urls.{$language}" => ['required', 'url:http,https', 'max:500'],
        ], [
            "urls.{$language}.required" => 'Indiquez l\'adresse de la page traduite.',
            "urls.{$language}.url" => 'L\'adresse doit commencer par http:// ou https://.',
            "urls.{$language}.max" => 'L\'adresse ne doit pas dépasser 500 caractères.',
        ]);

        try {
            $this->service->fillMissingUrl($this->page, $language, $this->urls[$language]);
        } catch (\DomainException $e) {
            $this->addError("urls.{$language}", $e->getMessage());

            return;
        }

        $this->urls[$language] = '';
        $this->page->refresh();
        session()->flash('success', 'URL '.TranslationCheck::LANGUAGES[$language]['short'].' enregistrée.');
    }

    public function submit(string $language): void
    {
        $this->ensureLanguage($language);

        $this->validate([
            "verdicts.{$language}" => ['required', 'in:correct,to_fix'],
            "comments.{$language}" => ["required_if:verdicts.{$language},to_fix", 'nullable', 'string', 'max:5000'],
        ], [
            "verdicts.{$language}.required" => 'Indiquez si la traduction est correcte.',
            "verdicts.{$language}.in" => 'Choix invalide.',
            "comments.{$language}.required_if" => 'Décrivez les corrections à apporter.',
            "comments.{$language}.max" => 'Le commentaire ne doit pas dépasser 5000 caractères.',
        ]);

        try {
            $this->service->submit(
                $this->page,
                auth()->user(),
                $language,
                $this->verdicts[$language] === 'correct',
                $this->comments[$language],
            );
        } catch (\DomainException $e) {
            $this->addError("verdicts.{$language}", $e->getMessage());

            return;
        }

        if ($this->verdicts[$language] === 'correct') {
            $this->comments[$language] = '';
        }

        $this->page->unsetRelation('translationChecks');
        session()->flash('success', 'Verdict '.TranslationCheck::LANGUAGES[$language]['short'].' enregistré. Merci !');
    }

    private function ensureLanguage(string $language): void
    {
        abort_unless(isset(TranslationCheck::LANGUAGES[$language]), 404);
    }

    public function render()
    {
        $this->page->loadMissing('translationChecks.checker:id,name', 'translationChecks.handler:id,name');

        return view('livewire.verification.translation.translation-form', [
            'nextPage' => $this->service->nextPageToCheck($this->page),
        ])->layout('components.layouts.app');
    }
}
