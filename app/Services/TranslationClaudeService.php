<?php

namespace App\Services;

use App\Models\TranslationCheck;
use App\Models\TranslationClaudeRequest;
use App\Models\User;
use App\Models\VerificationPage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
    /** Ligne repère de fin de travail, écrite par Claude dans l'issue. */
    public const MARKER = 'VERDON-TRADUCTION-CLAUDE';

    /** Au-delà, une demande sans fin de travail est en échec (« Renvoyer » la relance). */
    public const TIMEOUT_HOURS = 3;

    public function __construct(
        private GitHubService $github,
        private TranslationCheckService $checks,
    ) {}

    public function isConfigured(): bool
    {
        return $this->github->isConfigured();
    }

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

    /**
     * Fait avancer les demandes en cours (toutes, ou celle passée) : PR créée
     * quand Claude a fini, état des PR ouvertes. Renvoie des messages lisibles.
     *
     * @return string[]
     */
    public function sync(?TranslationClaudeRequest $only = null): array
    {
        if (! $this->isConfigured()) {
            return ['Connexion à GitHub non configurée (CLAUDE_GITHUB_TOKEN).'];
        }

        $requests = $only
            ? collect([$only->fresh()])->filter(fn ($r) => $r && $r->isActive())
            : TranslationClaudeRequest::with('page')->whereIn('status', TranslationClaudeRequest::ACTIVE_STATUSES)->orderBy('id')->get();

        $messages = [];
        foreach ($requests as $request) {
            try {
                $messages[] = $request->status === 'sent'
                    ? $this->syncSent($request)
                    : $this->syncPullRequest($request);
            } catch (\DomainException $e) {
                // Erreur passagère (réseau, jeton) : la demande reste suivie, jusqu'au délai.
                if ($request->status === 'sent' && $request->sent_at?->lt(now()->subHours(self::TIMEOUT_HOURS))) {
                    $messages[] = $this->fail($request, 'Suivi impossible après '.self::TIMEOUT_HOURS.' heures : '.$e->getMessage());

                    continue;
                }
                $request->update(['error' => $e->getMessage()]);
                $messages[] = "Demande {$request->reference} : {$e->getMessage()}";
                Log::warning('[TRADUCTIONS CLAUDE] Suivi impossible', ['reference' => $request->reference, 'error' => $e->getMessage()]);
            }
        }

        return $messages;
    }

    /** Demande chez Claude : a-t-il fini ? Si oui, crée la PR. */
    private function syncSent(TranslationClaudeRequest $request): string
    {
        $progress = $this->progress($request);

        if ($progress['error'] && ! $progress['done']) {
            return $this->fail($request, 'Claude a rencontré une erreur : voir l\'issue. « Renvoyer à Claude » relance la demande.');
        }

        if (! $progress['done']) {
            if ($request->sent_at && $request->sent_at->lt(now()->subHours(self::TIMEOUT_HOURS))) {
                return $this->fail($request, 'Pas de fin de travail après '.self::TIMEOUT_HOURS.' heures : voir l\'issue. « Renvoyer à Claude » relance la demande.');
            }
            $request->update(['error' => null]);

            return "Demande {$request->reference} : Claude corrige encore.";
        }

        $branch = $progress['branch'];
        $base = $this->github->defaultBranch();
        $ahead = 0;
        if ($branch !== '') {
            try {
                $compare = $this->github->call('GET', sprintf('compare/%s...%s', $this->ref($base), $this->ref($branch)));
                $ahead = (int) ($compare['ahead_by'] ?? 0);
            } catch (\DomainException $e) {
                // Branche annoncée mais jamais poussée : rien à proposer.
                if ($e->getCode() !== 404) {
                    throw $e;
                }
            }
        }

        if ($ahead === 0) {
            $request->update([
                'status' => 'no_change',
                'branch' => $branch !== '' ? $branch : null,
                'error' => null,
                'finished_at' => now(),
            ]);
            $this->closeIssue($request, 'Aucune modification du dépôt : pas de PR. La réponse de Claude ci-dessus explique pourquoi (page de l\'admin WordPress, correction à faire à la main, ou correction non applicable).');

            return "Demande {$request->reference} : aucune modification (voir l'issue).";
        }

        [$number, $url] = $this->createPullRequest($request, $branch, $base);
        $request->update([
            'status' => 'pr_open',
            'branch' => $branch,
            'pr_number' => $number,
            'pr_url' => $url,
            'error' => null,
        ]);
        $this->closeIssue($request, "Corrections terminées : PR #{$number} créée par le panel, à relire avant de fusionner.");

        return "Demande {$request->reference} : PR #{$number} créée.";
    }

    /** PR ouverte : fusionnée, fermée ou toujours ouverte ? */
    private function syncPullRequest(TranslationClaudeRequest $request): string
    {
        $pr = $this->github->call('GET', 'pulls/'.$request->pr_number);

        if (! empty($pr['merged_at'])) {
            $request->update(['status' => 'merged', 'error' => null, 'finished_at' => now()]);

            return "Demande {$request->reference} : PR #{$request->pr_number} fusionnée.";
        }
        if (($pr['state'] ?? '') === 'closed') {
            $request->update(['status' => 'closed', 'error' => null, 'finished_at' => now()]);

            return "Demande {$request->reference} : PR #{$request->pr_number} fermée sans fusion.";
        }
        $request->update(['error' => null]);

        return "Demande {$request->reference} : PR #{$request->pr_number} toujours ouverte.";
    }

    /**
     * Où en est Claude : ligne repère dans les commentaires de l'issue (ou, à
     * défaut, fin de travail de l'action et branche claude/issue-N-…).
     *
     * @return array{done: bool, error: bool, branch: string}
     */
    private function progress(TranslationClaudeRequest $request): array
    {
        $comments = $this->github->call('GET', "issues/{$request->issue_number}/comments?per_page=100");
        $state = ['done' => false, 'error' => false, 'branch' => ''];
        $pattern = '/'.self::MARKER.'\s+demande='.preg_quote($request->reference, '/').'\s+branche=`?([^\s`]+)`?/';

        foreach ($comments as $comment) {
            $body = (string) ($comment['body'] ?? '');
            if (preg_match($pattern, $body, $m)) {
                $state['done'] = true;
                $state['branch'] = strtolower($m[1]) === 'aucune' ? '' : $m[1];

                return $state;
            }
            if (preg_match('/Claude finished\b/i', $body)) {
                $state['done'] = true;   // fin sans ligne repère : branche cherchée ci-dessous
            } elseif (preg_match('/Claude encountered an error/i', $body)) {
                $state['error'] = true;
            }
        }

        if ($state['done']) {
            $refs = $this->github->call('GET', "git/matching-refs/heads/claude/issue-{$request->issue_number}-");
            $last = end($refs);
            if (is_array($last) && isset($last['ref'])) {
                $state['branch'] = Str::after((string) $last['ref'], 'refs/heads/');
            }
        }

        return $state;
    }

    /** Crée la PR (ou reprend celle qui existe déjà pour la branche). */
    private function createPullRequest(TranslationClaudeRequest $request, string $branch, string $base): array
    {
        $page = $request->page;
        $lang = strtoupper($request->language);

        try {
            $pr = $this->github->call('POST', 'pulls', [
                'title' => Str::limit("Traduction {$lang} — {$page->title} (correction Claude)", 117),
                'head' => $branch,
                'base' => $base,
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
            ]);
        } catch (\DomainException $e) {
            // Une PR existe déjà pour cette branche : la reprendre.
            $owner = Str::before($this->github->repository(), '/');
            $existing = $this->github->call('GET', 'pulls?state=all&head='.rawurlencode("{$owner}:{$branch}"));
            if (empty($existing[0]['number'])) {
                throw $e;
            }
            $pr = $existing[0];
        }

        return [(int) $pr['number'], (string) ($pr['html_url'] ?? '')];
    }

    /** Ferme l'issue avec un commentaire, sans mentionner Claude (pour ne pas le relancer). */
    private function closeIssue(TranslationClaudeRequest $request, string $text): void
    {
        $this->github->call('POST', "issues/{$request->issue_number}/comments", [
            'body' => $text."\n\n_Message automatique du panel DISPO-APP (Vérification > Suivi des traductions)._",
        ]);
        $this->github->call('PATCH', "issues/{$request->issue_number}", ['state' => 'closed', 'state_reason' => 'completed']);
    }

    private function fail(TranslationClaudeRequest $request, string $error): string
    {
        $request->update(['status' => 'failed', 'error' => $error, 'finished_at' => now()]);

        return "Demande {$request->reference} : {$error}";
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

        $body = implode("\n", $lines);

        // Limite d'une issue GitHub : 65 536 caractères.
        return mb_strlen($body) > 60000 ? mb_substr($body, 0, 60000)."\n\n_(texte tronqué)_" : $body;
    }

    /** Valeur d'une cellule de tableau Markdown. */
    private function cell(string $value): string
    {
        return str_replace(['|', "\r", "\n"], ['\|', '', ' '], $value);
    }

    /** Texte cité ligne à ligne (« > »). */
    private function quote(string $text): string
    {
        return collect(preg_split('/\R/', trim($text)))->map(fn ($line) => rtrim('> '.$line))->implode("\n");
    }

    /** Nom de branche pour une adresse de l'API (« / » gardés, le reste encodé). */
    private function ref(string $branch): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $branch)));
    }
}
