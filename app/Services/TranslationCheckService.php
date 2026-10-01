<?php

namespace App\Services;

use App\Models\TranslationCheck;
use App\Models\User;
use App\Models\VerificationPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Circuit de vérification des traductions EN/IT.
 *
 * Traducteur : verdict « correcte » (validée directement) ou « à corriger »
 * (commentaire obligatoire). Super-admin : à corriger → en cours → corrigée,
 * ou nouvelle vérification demandée (retour à « à vérifier »).
 *
 * Indépendant de VerificationReviewService : la clôture annuelle FR ne touche
 * pas aux traductions, et les traductions ne pèsent pas sur le statut FR.
 */
class TranslationCheckService
{
    /**
     * Enregistre le verdict d'un traducteur sur une langue.
     *
     * @throws \DomainException si la langue est invalide, si l'URL de la langue
     *                          n'est pas renseignée, ou si l'admin traite déjà la correction.
     */
    public function submit(VerificationPage $page, User $translator, string $language, bool $correct, ?string $comment = null): TranslationCheck
    {
        $this->assertLanguage($language);

        $comment = trim((string) $comment);
        if (! $correct && $comment === '') {
            throw new \DomainException('Décrivez les corrections à apporter.');
        }

        return DB::transaction(function () use ($page, $translator, $language, $correct, $comment) {
            // Verrou sur la page : sérialise deux traducteurs qui valident en même temps.
            $lockedPage = VerificationPage::whereKey($page->id)->lockForUpdate()->firstOrFail();

            if (! $lockedPage->urlForLanguage($language)) {
                throw new \DomainException("Renseignez d'abord l'URL de la page en {$this->languageLabel($language)}.");
            }

            $existing = TranslationCheck::where('page_id', $lockedPage->id)->where('language', $language)->first();
            if ($existing && ! $existing->isEditableByTranslator()) {
                throw new \DomainException('Cette traduction est déjà prise en charge par l\'équipe : elle ne peut plus être modifiée.');
            }

            return TranslationCheck::updateOrCreate(
                ['page_id' => $lockedPage->id, 'language' => $language],
                [
                    'status' => $correct ? 'correct' : 'to_fix',
                    'comment' => $correct ? null : $comment,
                    'checked_by' => $translator->id,
                    'checked_at' => now(),
                    // Nouveau verdict : l'éventuel traitement précédent ne s'applique plus.
                    'admin_response' => null,
                    'handled_by' => null,
                    'handled_at' => null,
                ]
            );
        });
    }

    /**
     * Renseigne l'URL EN/IT manquante d'une page. Le traducteur ne fait que
     * compléter : une URL existante se modifie dans « Pages à vérifier ».
     *
     * @throws \DomainException
     */
    public function fillMissingUrl(VerificationPage $page, string $language, string $url): void
    {
        $this->assertLanguage($language);

        DB::transaction(function () use ($page, $language, $url) {
            $lockedPage = VerificationPage::whereKey($page->id)->lockForUpdate()->firstOrFail();

            if ($lockedPage->urlForLanguage($language)) {
                throw new \DomainException("L'URL {$this->languageLabel($language)} est déjà renseignée.");
            }

            $lockedPage->update(["url_{$language}" => trim($url)]);
        });
    }

    /**
     * Traitement admin d'une traduction signalée.
     *
     * @param  string|null  $adminResponse  null : laisse le message existant ; '' : l'efface.
     *
     * @throws \DomainException
     */
    public function resolve(TranslationCheck $check, User $admin, string $newStatus, ?string $adminResponse = null): void
    {
        if (! in_array($newStatus, ['in_progress', 'fixed'], true)) {
            throw new \InvalidArgumentException("Statut invalide : {$newStatus}");
        }

        DB::transaction(function () use ($check, $admin, $newStatus, $adminResponse) {
            $locked = TranslationCheck::whereKey($check->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, ['to_fix', 'in_progress'], true)) {
                throw new \DomainException('Seule une traduction signalée « à corriger » peut être traitée.');
            }

            $payload = [
                'status' => $newStatus,
                'handled_by' => $admin->id,
                'handled_at' => now(),
            ];

            if ($adminResponse !== null) {
                $message = trim($adminResponse);
                $payload['admin_response'] = $message !== '' ? $message : null;
            }

            $locked->update($payload);
        });
    }

    /**
     * Redemande une vérification : la langue repasse « à vérifier ».
     * Seul le dernier état étant conservé, l'ancien verdict est supprimé.
     */
    public function requestRecheck(VerificationPage $page, string $language): void
    {
        $this->assertLanguage($language);

        TranslationCheck::where('page_id', $page->id)->where('language', $language)->delete();
    }

    /**
     * Restreint la requête aux pages dont au moins une des langues demandées
     * est dans l'état du filtre (clé de TranslationCheck::FILTERS).
     */
    public function applyFilter(Builder $query, string $filter, array $languages): Builder
    {
        $languages = $this->normalizeLanguages($languages);

        if (! isset(TranslationCheck::FILTERS[$filter])) {
            return $query;
        }

        $statuses = TranslationCheck::FILTERS[$filter]['statuses'];

        if ($statuses === null) {
            // « À vérifier » = aucune ligne pour la langue.
            return $query->where(function (Builder $q) use ($languages) {
                foreach ($languages as $language) {
                    $q->orWhereDoesntHave('translationChecks', fn ($c) => $c->where('language', $language));
                }
            });
        }

        return $query->whereHas('translationChecks', fn ($c) => $c
            ->whereIn('language', $languages)
            ->whereIn('status', $statuses));
    }

    /**
     * Nombre de pages par filtre. Chaque compteur est le nombre exact de lignes
     * que le filtre affichera (une page peut compter dans plusieurs tuiles si
     * ses deux langues sont dans des états différents).
     *
     * @return array<string, int>
     */
    public function stats(Builder $baseQuery, array $languages): array
    {
        $stats = ['all' => (clone $baseQuery)->count()];

        foreach (array_keys(TranslationCheck::FILTERS) as $filter) {
            $stats[$filter] = $this->applyFilter(clone $baseQuery, $filter, $languages)->count();
        }

        return $stats;
    }

    /** Traductions signalées qui attendent une action de l'admin (badge du menu). */
    public function countToHandle(): int
    {
        return TranslationCheck::where('status', 'to_fix')
            ->whereHas('page', fn ($q) => $q->forTranslation())
            ->count();
    }

    /** Nombre de traductions encore à vérifier (tuile du tableau de bord). */
    public function countToCheck(): int
    {
        $pages = VerificationPage::forTranslation()->count();
        $checked = TranslationCheck::whereHas('page', fn ($q) => $q->forTranslation())->count();

        return max(0, $pages * count(TranslationCheck::LANGUAGES) - $checked);
    }

    /**
     * Page suivante (par titre) ayant encore une langue à vérifier, pour
     * enchaîner les vérifications sans repasser par la liste.
     */
    public function nextPageToCheck(VerificationPage $current): ?VerificationPage
    {
        $query = VerificationPage::forTranslation()
            ->whereKeyNot($current->id)
            ->orderBy('title');

        $this->applyFilter($query, 'to_check', array_keys(TranslationCheck::LANGUAGES));

        return (clone $query)->where('title', '>=', $current->title)->first()
            ?? $query->first();
    }

    /**
     * @return string[]
     */
    public function normalizeLanguages(array $languages): array
    {
        $valid = array_values(array_intersect($languages, array_keys(TranslationCheck::LANGUAGES)));

        return $valid === [] ? array_keys(TranslationCheck::LANGUAGES) : $valid;
    }

    private function assertLanguage(string $language): void
    {
        if (! isset(TranslationCheck::LANGUAGES[$language])) {
            throw new \DomainException("Langue invalide : {$language}");
        }
    }

    private function languageLabel(string $language): string
    {
        return TranslationCheck::LANGUAGES[$language]['short'] ?? strtoupper($language);
    }
}
