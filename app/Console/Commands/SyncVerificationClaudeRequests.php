<?php

namespace App\Console\Commands;

use App\Models\VerificationClaudeRequest;
use App\Services\VerificationClaudeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncVerificationClaudeRequests extends Command
{
    /**
     * Pas de cadence dans le nom : la fréquence est définie dans routes/console.php.
     * Test de la connexion à GitHub : translations:claude-sync --test (même jeton).
     */
    protected $signature = 'verification:claude-sync';

    protected $description = 'Suit les corrections de page confiées à Claude : crée la PR quand Claude a fini, puis suit son état.';

    public function handle(VerificationClaudeService $service): int
    {
        if (! $service->isConfigured()) {
            $this->line('Connexion à GitHub non configurée (CLAUDE_GITHUB_TOKEN) : rien à suivre.');

            return Command::SUCCESS;
        }

        // Aucun appel à GitHub quand rien n'est en cours (cas le plus fréquent).
        if (! VerificationClaudeRequest::whereIn('status', VerificationClaudeRequest::ACTIVE_STATUSES)->exists()) {
            $this->line('Aucune demande en cours.');

            return Command::SUCCESS;
        }

        $messages = $service->sync();
        foreach ($messages as $message) {
            $this->line('→ '.$message);
        }

        Log::channel('single')->info('[VÉRIFICATION CLAUDE] Suivi des demandes', ['messages' => $messages]);

        return Command::SUCCESS;
    }
}
