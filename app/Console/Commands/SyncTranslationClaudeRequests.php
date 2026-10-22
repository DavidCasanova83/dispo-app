<?php

namespace App\Console\Commands;

use App\Models\TranslationClaudeRequest;
use App\Services\GitHubService;
use App\Services\TranslationClaudeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncTranslationClaudeRequests extends Command
{
    /**
     * Pas de cadence dans le nom : la fréquence est définie dans routes/console.php.
     */
    protected $signature = 'translations:claude-sync
                            {--test : Teste seulement la connexion à GitHub (lecture seule)}';

    protected $description = 'Suit les corrections de traduction confiées à Claude : crée la PR quand Claude a fini, puis suit son état.';

    public function handle(TranslationClaudeService $service, GitHubService $github): int
    {
        if ($this->option('test')) {
            try {
                $this->info($github->test());

                return Command::SUCCESS;
            } catch (\DomainException $e) {
                $this->error($e->getMessage());

                return Command::FAILURE;
            }
        }

        if (! $service->isConfigured()) {
            $this->line('Connexion à GitHub non configurée (CLAUDE_GITHUB_TOKEN) : rien à suivre.');

            return Command::SUCCESS;
        }

        // Aucun appel à GitHub quand rien n'est en cours (cas le plus fréquent).
        if (! TranslationClaudeRequest::whereIn('status', TranslationClaudeRequest::ACTIVE_STATUSES)->exists()) {
            $this->line('Aucune demande en cours.');

            return Command::SUCCESS;
        }

        $messages = $service->sync();
        foreach ($messages as $message) {
            $this->line('→ '.$message);
        }

        Log::channel('single')->info('[TRADUCTIONS CLAUDE] Suivi des demandes', ['messages' => $messages]);

        return Command::SUCCESS;
    }
}
