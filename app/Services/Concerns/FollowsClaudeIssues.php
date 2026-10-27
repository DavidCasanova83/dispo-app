<?php

namespace App\Services\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Suivi d'une demande confiée à Claude par une issue GitHub @claude dans le
 * dépôt du site (corrections de traduction, corrections de page) :
 *
 *   - Claude termine par une ligne repère « <MARKER> demande=<référence>
 *     branche=<branche> » dans l'issue ;
 *   - le panel crée alors la PR (ou constate « aucune modification »), ferme
 *     l'issue, puis suit la PR (ouverte, fusionnée, fermée) ;
 *   - sans fin de travail après TIMEOUT_HOURS, la demande est en échec.
 *
 * La classe hôte fournit $this->github (GitHubService), les constantes MARKER
 * et TIMEOUT_HOURS, et ce qui lui est propre : demandes à suivre, contenu de
 * la PR, pied des messages automatiques, étiquette des journaux. Le modèle de
 * demande a les colonnes de HasClaudeRequestStatus.
 */
trait FollowsClaudeIssues
{
    /** Demandes encore suivies (chez Claude ou PR ouverte). */
    abstract protected function activeRequestsQuery(): Builder;

    /**
     * Titre et texte de la PR créée quand Claude a fini.
     *
     * @return array{title: string, body: string}
     */
    abstract protected function pullRequestContent(Model $request): array;

    /** Pied des commentaires automatiques (écran du panel d'où vient la demande). */
    abstract protected function issueFooter(): string;

    /** Étiquette des lignes de journal Laravel (« [TRADUCTIONS CLAUDE] »). */
    abstract protected function logTag(): string;

    /** Appelé après chaque changement d'état d'une demande (journal…). */
    protected function afterStatusChange(Model $request): void {}

    public function isConfigured(): bool
    {
        return $this->github->isConfigured();
    }

    /**
     * Fait avancer les demandes en cours (toutes, ou celle passée) : PR créée
     * quand Claude a fini, état des PR ouvertes. Renvoie des messages lisibles.
     *
     * @return string[]
     */
    public function sync(?Model $only = null): array
    {
        if (! $this->isConfigured()) {
            return ['Connexion à GitHub non configurée (CLAUDE_GITHUB_TOKEN).'];
        }

        $requests = $only
            ? collect([$only->fresh()])->filter(fn ($r) => $r && $r->isActive())
            : $this->activeRequestsQuery()->with('page')->orderBy('id')->get();

        $messages = [];
        foreach ($requests as $request) {
            try {
                $messages[] = $request->status === 'sent'
                    ? $this->syncSent($request)
                    : $this->syncPullRequest($request);
            } catch (\DomainException $e) {
                // Erreur passagère (réseau, jeton) : la demande reste suivie, jusqu'au délai.
                if ($request->status === 'sent' && $request->sent_at?->lt(now()->subHours(static::TIMEOUT_HOURS))) {
                    $messages[] = $this->fail($request, 'Suivi impossible après '.static::TIMEOUT_HOURS.' heures : '.$e->getMessage());

                    continue;
                }
                $request->update(['error' => $e->getMessage()]);
                $messages[] = "Demande {$request->reference} : {$e->getMessage()}";
                Log::warning($this->logTag().' Suivi impossible', ['reference' => $request->reference, 'error' => $e->getMessage()]);
            }
        }

        return $messages;
    }

    /** Demande chez Claude : a-t-il fini ? Si oui, crée la PR. */
    private function syncSent(Model $request): string
    {
        $progress = $this->progress($request);

        if ($progress['error'] && ! $progress['done']) {
            return $this->fail($request, 'Claude a rencontré une erreur : voir l\'issue. « Renvoyer à Claude » relance la demande.');
        }

        if (! $progress['done']) {
            if ($request->sent_at && $request->sent_at->lt(now()->subHours(static::TIMEOUT_HOURS))) {
                return $this->fail($request, 'Pas de fin de travail après '.static::TIMEOUT_HOURS.' heures : voir l\'issue. « Renvoyer à Claude » relance la demande.');
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
            $this->afterStatusChange($request);

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
        $this->afterStatusChange($request);

        return "Demande {$request->reference} : PR #{$number} créée.";
    }

    /** PR ouverte : fusionnée, fermée ou toujours ouverte ? */
    private function syncPullRequest(Model $request): string
    {
        $pr = $this->github->call('GET', 'pulls/'.$request->pr_number);

        if (! empty($pr['merged_at'])) {
            $request->update(['status' => 'merged', 'error' => null, 'finished_at' => now()]);
            $this->afterStatusChange($request);

            return "Demande {$request->reference} : PR #{$request->pr_number} fusionnée.";
        }
        if (($pr['state'] ?? '') === 'closed') {
            $request->update(['status' => 'closed', 'error' => null, 'finished_at' => now()]);
            $this->afterStatusChange($request);

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
    private function progress(Model $request): array
    {
        $comments = $this->github->call('GET', "issues/{$request->issue_number}/comments?per_page=100");
        $state = ['done' => false, 'error' => false, 'branch' => ''];
        $pattern = '/'.static::MARKER.'\s+demande='.preg_quote($request->reference, '/').'\s+branche=`?([^\s`]+)`?/';

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
    private function createPullRequest(Model $request, string $branch, string $base): array
    {
        $content = $this->pullRequestContent($request);

        try {
            $pr = $this->github->call('POST', 'pulls', [
                'title' => Str::limit($content['title'], 117),
                'head' => $branch,
                'base' => $base,
                'body' => $content['body'],
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
    private function closeIssue(Model $request, string $text): void
    {
        $this->github->call('POST', "issues/{$request->issue_number}/comments", [
            'body' => $text."\n\n".$this->issueFooter(),
        ]);
        $this->github->call('PATCH', "issues/{$request->issue_number}", ['state' => 'closed', 'state_reason' => 'completed']);
    }

    private function fail(Model $request, string $error): string
    {
        $request->update(['status' => 'failed', 'error' => $error, 'finished_at' => now()]);
        $this->afterStatusChange($request);

        return "Demande {$request->reference} : {$error}";
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

    /** Limite d'une issue GitHub : 65 536 caractères. */
    private function truncateIssueBody(string $body): string
    {
        return mb_strlen($body) > 60000 ? mb_substr($body, 0, 60000)."\n\n_(texte tronqué)_" : $body;
    }
}
