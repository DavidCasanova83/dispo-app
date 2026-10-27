<?php

namespace App\Services;

use App\Models\TranslationCheck;
use App\Models\TranslationClaudeRequest;
use App\Models\User;
use App\Models\VerificationPage;
use App\Services\Concerns\FollowsClaudeIssues;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Corrections de traduction confiées à Claude (Suivi des traductions).
 *
 *   1. l'admin envoie une traduction « à corriger » : issue GitHub @claude
 *      dans le dépôt du site, avec la page, les adresses et le commentaire du
 *      traducteur ; la traduction passe « en cours » ;
 *   2. Claude Code (workflow claude.yml du dépôt du site) suit
 *      themes/verdon-site/docs/traductions-claude.md : il corrige le fichier
 *      contenu.<langue>.md de la page sur sa branche et termine par une
 *      ligne repère (MARKER) ;
 *   3. toutes les 5 minutes (translations:claude-sync) ou sur « Vérifier
 *      maintenant » : quand Claude a fini, le panel crée la PR et ferme
 *      l'issue, puis suit la PR (ouverte, fusionnée, fermée).
 *
 * La PR est relue et fusionnée à la main ; « Marquer corrigée » reste à
 * l'admin, après déploiement.
 */
class TranslationClaudeService
{
    use FollowsClaudeIssues;

    /** Ligne repère de fin de travail, écrite par Claude dans l'issue. */
    public const MARKER = 'VERDON-TRADUCTION-CLAUDE';

    /** Au-delà, une demande sans fin de travail est en échec (« Renvoyer » la relance). */
    public const TIMEOUT_HOURS = 3;

    public function __construct(
        private GitHubService $github,
        private TranslationCheckService $checks,
    ) {}

    /**
     * Envoie la correction d'une traduction signalée à Claude.
     *
     * @throws \DomainException
     */
    public function send(TranslationCheck $check, User $admin, ?string $message = null): TranslationClaudeRequest
    {
        $lock = Cache::lock('translation-claude-send-'.$check->id, 30);
        if (! $lock->get()) {
            throw new \DomainException('Demande déjà en cours d\'envoi.');
        }

        try {
            $check = TranslationCheck::with('page')->findOrFail($check->id);
            $page = $check->page;

            if (! in_array($check->status, ['to_fix', 'in_progress'], true) || trim((string) $check->comment) === '') {
                throw new \DomainException('Seule une traduction signalée « à corriger » (avec un commentaire) peut être envoyée à Claude.');
            }
            if (! $page || ! $page->urlForLanguage($check->language)) {
                throw new \DomainException('L\'URL de la page dans cette langue n\'est pas renseignée.');
            }
            if ($this->activeRequest($page, $check->language)) {
                throw new \DomainException('Une demande à Claude est déjà en cours pour cette traduction.');
            }

            $reference = sprintf('tr-%d-%s-%s', $page->id, $check->language, now()->format('YmdHis'));
            $message = trim((string) $message);

            $issue = $this->github->call('POST', 'issues', [
                'title' => Str::limit(sprintf('[Traduction %s] %s — correction signalée par le traducteur', strtoupper($check->language), $page->title), 117),
                'body' => $this->issueBody($page, $check, $admin, $reference, $message),
            ]);

            $request = TranslationClaudeRequest::create([
                'reference' => $reference,
                'page_id' => $page->id,
                'translation_check_id' => $check->id,
                'language' => $check->language,
                'status' => 'sent',
                'translator_comment' => $check->comment,
                'message' => $message !== '' ? $message : null,
                'issue_number' => (int) ($issue['number'] ?? 0),
                'issue_url' => (string) ($issue['html_url'] ?? ''),
                'requested_by' => $admin->id,
                'sent_at' => now(),
            ]);

            // Prise en charge : le traducteur voit « en cours » et ne modifie plus son verdict.
            if ($check->status === 'to_fix') {
                $this->checks->resolve($check, $admin, 'in_progress');
            }

            return $request;
        } finally {
            $lock->release();
        }
    }

    /** Demande en cours (chez Claude ou PR ouverte) pour une traduction. */
    public function activeRequest(VerificationPage $page, string $language): ?TranslationClaudeRequest
    {
        return TranslationClaudeRequest::where('page_id', $page->id)
            ->where('language', $language)
            ->whereIn('status', TranslationClaudeRequest::ACTIVE_STATUSES)
            ->latest('id')
            ->first();
    }

    protected function activeRequestsQuery(): Builder
    {
        return TranslationClaudeRequest::whereIn('status', TranslationClaudeRequest::ACTIVE_STATUSES);
    }

    protected function issueFooter(): string
    {
        return '_Message automatique du panel DISPO-APP (Vérification > Suivi des traductions)._';
    }

    protected function logTag(): string
    {
        return '[TRADUCTIONS CLAUDE]';
    }

    protected function pullRequestContent(Model $request): array
    {
        $page = $request->page;
        $lang = strtoupper($request->language);

        return [
            'title' => "Traduction {$lang} — {$page->title} (correction Claude)",
            'body' => implode("\n", [
                "Correction de la traduction **{$lang}** de la page **{$page->title}**, signalée par un traducteur et envoyée à Claude depuis le panel (**Vérification > Suivi des traductions**).",
                '',
                "- **Page {$lang}** : ".$page->urlForLanguage($request->language),
                '- **Page FR** : '.$page->url,
                "- **Demande, réponse de Claude et textes à relire** : #{$request->issue_number}",
                '',
                '## Corrections signalées par le traducteur',
                '',
                $this->quote((string) $request->translator_comment),
                '',
                '## Après la fusion',
                '',
                'Déployer (`git pull` sur le serveur, vider la minification W3TC), vérifier la page en ligne, puis dans le panel : **Marquer corrigée** (la traduction passe « Corrigée » pour le traducteur).',
                '',
                '_PR créée automatiquement par le panel DISPO-APP : à relire avant de fusionner._',
            ]),
        ];
    }

    private function issueBody(VerificationPage $page, TranslationCheck $check, User $admin, string $reference, string $message): string
    {
        $lang = $check->language;
        $short = strtoupper($lang);
        $label = TranslationCheck::LANGUAGES[$lang]['label'] ?? $short;
        $url = $page->urlForLanguage($lang);
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        $context = [
            'Écran' => 'Vérification > Suivi des traductions (panel DISPO-APP)',
            'Fiche dans le panel' => route('verification.admin.translations', ['fiche' => $page->id]),
            'Demandé par' => $admin->name.' — '.now()->format('d/m/Y à H:i'),
            'Page' => $page->title,
            'Langue à corriger' => "{$label} ({$short})",
            "Adresse {$short}" => $url,
            'Adresse FR (référence)' => $page->url,
            'Signalée le' => $check->checked_at?->format('d/m/Y à H:i') ?? '',
            'Référence' => $reference,
        ];
        foreach (array_keys(TranslationCheck::LANGUAGES) as $other) {
            if ($other !== $lang && $page->urlForLanguage($other)) {
                $context['Adresse '.strtoupper($other).' (pour information)'] = $page->urlForLanguage($other);
            }
        }

        $lines = ['@claude', '', '## Correction de traduction demandée depuis le panel DISPO-APP', '', '| | |', '|---|---|'];
        foreach ($context as $key => $value) {
            if ((string) $value !== '') {
                $lines[] = '| **'.$this->cell($key).'** | '.$this->cell($value).' |';
            }
        }

        $lines = array_merge($lines, ['', '## Corrections signalées par le traducteur', '', $this->quote((string) $check->comment)]);

        if ($check->admin_response) {
            $lines = array_merge($lines, ['', '## Réponse déjà faite au traducteur', '', $this->quote($check->admin_response)]);
        }

        $lines = array_merge($lines, [
            '',
            '## Message',
            '',
            $message !== '' ? $message : "_(pas de message : corriger la traduction {$short} selon le signalement ci-dessus)_",
            '',
            '## Consignes',
            '',
            '- Suis la méthode de `themes/verdon-site/docs/traductions-claude.md` ; réponds en français.',
            "- Retrouve la page : fichier `themes/verdon-site/pages-php/<dossier>/contenu.{$lang}.md` dont l'en-tête contient l'adresse `{$path}` (recherche dans `themes/verdon-site/pages-php/`, ou dans `PAGES-MD.md`).",
            "- Corrige **chaque point** signalé par le traducteur dans `contenu.{$lang}.md`, en t'appuyant sur `contenu.fr.md` (référence) ; ne change ni la structure ni les `design:`, et n'invente aucune information.",
            "- Ne modifie que `contenu.{$lang}.md` de cette page (ni `PAGES-MD.md`, ni `A-RENSEIGNER.md`) : si le même problème existe dans une autre langue, signale-le dans ton commentaire sans le corriger.",
            '- Page sans fichier `.md` (contenu de l\'admin WordPress) : ne modifie rien ; donne dans ton commentaire le texte corrigé, bloc par bloc, et où le coller dans l\'admin (traduction WPML de la page).',
            '- Commite et pousse sur ta branche ; **ne crée pas de PR** : le panel la crée dès que tu as fini.',
            sprintf('- Termine ton commentaire par la ligne : `%s demande=%s branche=<nom de ta branche>` (`branche=aucune` si tu n\'as rien modifié).', self::MARKER, $reference),
        ]);

        return $this->truncateIssueBody(implode("\n", $lines));
    }
}
