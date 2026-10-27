<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerificationClaudeRequest;
use App\Models\VerificationPage;
use App\Models\VerificationReview;
use App\Services\Concerns\FollowsClaudeIssues;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Corrections de page confiées à Claude, à partir des retours des relecteurs
 * (fiche d'une page, Vérification des pages). Réservé au super-admin (gate
 * « ask-claude-page-correction »).
 *
 *   1. l'admin choisit les retours signalés à transmettre : issue GitHub
 *
 *      @claude dans le dépôt du site, avec la page, ses adresses et les
 *      retours (sans le nom des relecteurs) ; les retours « Nouveau » passent
 *      « En correction » ;
 *   2. Claude Code (workflow claude.yml du dépôt du site) suit
 *      themes/verdon-site/docs/verification-claude.md : il corrige les
 *      fichiers contenu.*.md de la page sur sa branche et termine par une
 *      ligne repère (MARKER) ;
 *   3. toutes les 5 minutes (verification:claude-sync) ou sur « Vérifier
 *      maintenant » : quand Claude a fini, le panel crée la PR et ferme
 *      l'issue, puis suit la PR (ouverte, fusionnée, fermée).
 *
 * La PR est relue et fusionnée à la main ; marquer les retours « Réglé »
 * reste à l'admin, après déploiement.
 */
class VerificationClaudeService
{
    use FollowsClaudeIssues;

    /** Ligne repère de fin de travail, écrite par Claude dans l'issue. */
    public const MARKER = 'VERDON-VERIFICATION-CLAUDE';

    /** Au-delà, une demande sans fin de travail est en échec (« Renvoyer » la relance). */
    public const TIMEOUT_HOURS = 3;

    public function __construct(
        private GitHubService $github,
        private VerificationReviewService $reviews,
        private VerificationJournal $journal,
    ) {}

    /**
     * Retours du cycle en cours qu'on peut confier à Claude : un problème
     * signalé, pas encore réglé (Nouveau ou En correction).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, VerificationReview>
     */
    public function openReviews(VerificationPage $page)
    {
        return $page->reviews()
            ->whereIn('status', ['pending_admin', 'in_progress'])
            ->with('user:id,name')
            ->orderByRaw("CASE language WHEN 'fr' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();
    }

    /** Demande en cours (chez Claude ou PR ouverte) pour une page. */
    public function activeRequest(VerificationPage $page): ?VerificationClaudeRequest
    {
        return VerificationClaudeRequest::where('page_id', $page->id)
            ->whereIn('status', VerificationClaudeRequest::ACTIVE_STATUSES)
            ->latest('id')
            ->first();
    }

    /**
     * Envoie à Claude la correction d'une page, à partir des retours choisis.
     *
     * @param  int[]  $reviewIds
     *
     * @throws \DomainException
     */
    public function send(VerificationPage $page, User $admin, array $reviewIds, ?string $message = null): VerificationClaudeRequest
    {
        $lock = Cache::lock('verification-claude-send-'.$page->id, 30);
        if (! $lock->get()) {
            throw new \DomainException('Demande déjà en cours d\'envoi.');
        }

        try {
            $page = VerificationPage::find($page->id);
            if (! $page) {
                throw new \DomainException('Cette page est dans la corbeille : restaurez-la avant de demander une correction.');
            }

            $ids = array_map('intval', $reviewIds);
            $reviews = $this->openReviews($page)->filter(fn (VerificationReview $r) => in_array($r->id, $ids, true))->values();
            if ($reviews->isEmpty()) {
                throw new \DomainException('Choisissez au moins un retour signalé et pas encore réglé.');
            }
            if ($this->activeRequest($page)) {
                throw new \DomainException('Une demande à Claude est déjà en cours pour cette page.');
            }

            $reference = sprintf('vp-%d-c%d-%s', $page->id, $page->cycle, now()->format('YmdHis'));
            $message = trim((string) $message);
            $feedback = $this->feedbackText($reviews);

            $issue = $this->github->call('POST', 'issues', [
                'title' => Str::limit(sprintf('[Vérification] %s — retours des relecteurs', $page->title), 117),
                'body' => $this->issueBody($page, $admin, $reference, $feedback, $message),
            ]);

            $request = VerificationClaudeRequest::create([
                'reference' => $reference,
                'page_id' => $page->id,
                'cycle' => $page->cycle,
                'status' => 'sent',
                'review_ids' => $reviews->pluck('id')->all(),
                'feedback' => $feedback,
                'message' => $message !== '' ? $message : null,
                'issue_number' => (int) ($issue['number'] ?? 0),
                'issue_url' => (string) ($issue['html_url'] ?? ''),
                'requested_by' => $admin->id,
                'sent_at' => now(),
            ]);

            // Prise en charge : le relecteur voit « En cours de correction ».
            foreach ($reviews->where('status', 'pending_admin') as $review) {
                $this->reviews->resolve($review, 'in_progress');
            }

            $this->journal->record($page, 'claude_requested', [
                'reference' => $reference,
                'issue_number' => $request->issue_number,
                'reviews' => $reviews->count(),
            ], actorId: $admin->id);

            return $request;
        } finally {
            $lock->release();
        }
    }

    // ─── SUIVI (FollowsClaudeIssues) ──────────────────────────────

    protected function activeRequestsQuery(): Builder
    {
        return VerificationClaudeRequest::whereIn('status', VerificationClaudeRequest::ACTIVE_STATUSES);
    }

    protected function issueFooter(): string
    {
        return '_Message automatique du panel DISPO-APP (Vérification des pages > fiche de la page)._';
    }

    protected function logTag(): string
    {
        return '[VÉRIFICATION CLAUDE]';
    }

    protected function afterStatusChange(Model $request): void
    {
        if ($request->page) {
            $this->journal->record($request->page, 'claude_updated', array_filter([
                'reference' => $request->reference,
                'status' => $request->status,
                'pr_number' => $request->pr_number,
                'issue_number' => $request->issue_number,
            ]));
        }
    }

    protected function pullRequestContent(Model $request): array
    {
        $page = $request->page;
        $lines = [
            "Correction de la page **{$page->title}**, à partir des retours des relecteurs, envoyée à Claude depuis le panel (**Vérification des pages > fiche de la page**).",
            '',
            '- **Page FR** : '.$page->url,
        ];
        foreach (['en', 'it'] as $lang) {
            if ($page->urlForLanguage($lang)) {
                $lines[] = '- **Page '.strtoupper($lang).'** : '.$page->urlForLanguage($lang);
            }
        }

        $lines = array_merge($lines, [
            "- **Demande, réponse de Claude et textes à relire** : #{$request->issue_number}",
            '',
            '## Retours des relecteurs',
            '',
            Str::limit((string) $request->feedback, 50000, "\n\n_(retours tronqués : voir l'issue)_"),
            '',
            '## Après la fusion',
            '',
            'Déployer (`git pull` sur le serveur, vider la minification W3TC), vérifier la page en ligne, puis dans le panel (fiche de la page) : marquer chaque retour **Réglé** (le relecteur le voit), et clôturer la page quand tout est réglé.',
            '',
            '_PR créée automatiquement par le panel DISPO-APP : à relire avant de fusionner._',
        ]);

        return [
            'title' => "Vérification — {$page->title} (correction Claude)",
            'body' => $this->truncateIssueBody(implode("\n", $lines)),
        ];
    }

    // ─── TEXTES ───────────────────────────────────────────────────

    /**
     * Retours au format Markdown, sans le nom des relecteurs (l'issue est
     * visible de toutes les personnes qui ont accès au dépôt du site).
     *
     * @param  Collection<int, VerificationReview>  $reviews
     */
    public function feedbackText(Collection $reviews): string
    {
        $blocks = [];

        foreach ($reviews->values() as $i => $review) {
            $lines = [sprintf(
                '### Retour %d — %s, rendu le %s%s',
                $i + 1,
                strtoupper($review->language),
                $review->submittedAt()?->format('d/m/Y') ?? '?',
                $review->status === 'in_progress' ? ' (déjà pris en charge)' : ''
            ), ''];

            if (! $review->content_ok) {
                $lines = array_merge($lines, ['**Problème de contenu**', '', $this->quote($review->content_comment ?: 'Non précisé'), '']);
            }
            if (! $review->media_ok) {
                $lines = array_merge($lines, ['**Problème de médias ou de liens**', '', $this->quote($review->media_comment ?: 'Non précisé'), '']);
            }
            if ($review->remarks) {
                $lines = array_merge($lines, ['**Remarques**', '', $this->quote($review->remarks), '']);
            }

            $toAdd = array_values(array_filter($review->content_to_add ?? [], fn ($code) => $code !== 'none'));
            if ($toAdd !== []) {
                $labels = collect($toAdd)->map(fn ($code) => VerificationReview::CONTENT_TO_ADD_OPTIONS[$code] ?? $code)->implode(' ; ');
                $lines[] = "**À ajouter** : {$labels}";
                $lines = array_merge($lines, $review->content_to_add_details ? ['', $this->quote($review->content_to_add_details)] : [], ['']);
            }
            if ($review->content_to_remove === 'yes') {
                $lines[] = '**À supprimer** : '.$review->contentToRemoveLabel();
                $lines = array_merge($lines, $review->content_to_remove_details ? ['', $this->quote($review->content_to_remove_details)] : [], ['']);
            }
            if ($review->visitor_perspective && $review->visitor_perspective !== 'yes') {
                $lines[] = '**La page répond-elle au visiteur ?** '.$review->visitorPerspectiveLabel();
                $lines = array_merge($lines, $review->visitor_unanswered ? ['', $this->quote($review->visitor_unanswered)] : [], ['']);
            }
            if ($review->suggestions) {
                $lines = array_merge($lines, ['**Suggestions**', '', $this->quote($review->suggestions), '']);
            }
            if ($review->relevance || $review->rating) {
                $lines[] = trim(($review->relevance ? 'Pertinence : '.$review->relevanceLabel().'. ' : '').($review->rating ? "Note : {$review->rating}/5." : ''));
                $lines[] = '';
            }
            if ($review->admin_response) {
                $lines = array_merge($lines, ['**Réponse déjà faite au relecteur**', '', $this->quote($review->admin_response), '']);
            }

            $blocks[] = rtrim(implode("\n", $lines));
        }

        return implode("\n\n", $blocks);
    }

    private function issueBody(VerificationPage $page, User $admin, string $reference, string $feedback, string $message): string
    {
        $path = parse_url($page->url, PHP_URL_PATH) ?: $page->url;

        $context = [
            'Écran' => 'Vérification des pages > fiche de la page (panel DISPO-APP)',
            'Fiche dans le panel' => route('verification.admin.page', $page->id),
            'Demandé par' => $admin->name.' — '.now()->format('d/m/Y à H:i'),
            'Page' => $page->title,
            'Adresse FR' => $page->url,
        ];
        foreach (['en', 'it'] as $lang) {
            if ($page->urlForLanguage($lang)) {
                $context['Adresse '.strtoupper($lang)] = $page->urlForLanguage($lang);
            }
        }
        $context['Catégorie'] = (string) $page->categoryLabel();
        $context['Référence'] = $reference;

        $lines = ['@claude', '', '## Correction de page demandée depuis le panel DISPO-APP', '', '| | |', '|---|---|'];
        foreach ($context as $key => $value) {
            if ((string) $value !== '') {
                $lines[] = '| **'.$this->cell($key).'** | '.$this->cell($value).' |';
            }
        }

        $lines = array_merge($lines, [
            '',
            '## Retours des relecteurs',
            '',
            $feedback,
            '',
            '## Message',
            '',
            $message !== '' ? $message : '_(pas de message : corriger la page selon les retours ci-dessus)_',
            '',
            '## Consignes',
            '',
            '- Suis la méthode de `themes/verdon-site/docs/verification-claude.md` ; réponds en français.',
            "- Retrouve la page : fichier `themes/verdon-site/pages-php/<dossier>/contenu.fr.md` dont l'en-tête contient l'adresse `{$path}` (recherche dans `themes/verdon-site/pages-php/`, ou dans `PAGES-MD.md`).",
            '- Traite **chaque retour** dans `contenu.fr.md` ; reporte chaque correction de fond (information, lien, image) dans `contenu.en.md` et `contenu.it.md` ; ne change ni la structure ni les `design:` sans nécessité, et n\'invente aucune information (une information à vérifier ou manquante se signale dans ton commentaire).',
            '- Ne modifie que les `contenu.*.md` de cette page (ni `PAGES-MD.md`, ni `A-RENSEIGNER.md`, ni le moteur).',
            '- Page sans fichier `.md` (contenu de l\'admin WordPress) : ne modifie rien ; donne dans ton commentaire le texte corrigé, bloc par bloc, et où le changer dans l\'admin (page FR, puis traductions WPML).',
            '- Commite et pousse sur ta branche ; **ne crée pas de PR** : le panel la crée dès que tu as fini.',
            sprintf('- Termine ton commentaire par la ligne : `%s demande=%s branche=<nom de ta branche>` (`branche=aucune` si tu n\'as rien modifié).', self::MARKER, $reference),
        ]);

        return $this->truncateIssueBody(implode("\n", $lines));
    }
}
