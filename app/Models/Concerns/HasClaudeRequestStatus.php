<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Models\VerificationPage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande confiée à Claude (issue GitHub, puis PR créée par le panel) :
 * états, libellés et relations communs aux corrections de traduction et aux
 * corrections de page. Voir App\Services\Concerns\FollowsClaudeIssues.
 */
trait HasClaudeRequestStatus
{
    /** Libellés et tons de <x-verif.status-badge>. */
    public const STATUSES = [
        'sent' => ['label' => 'Chez Claude', 'tone' => 'info'],
        'pr_open' => ['label' => 'PR ouverte', 'tone' => 'accent'],
        'merged' => ['label' => 'PR fusionnée', 'tone' => 'success'],
        'closed' => ['label' => 'PR fermée sans fusion', 'tone' => 'neutral'],
        'no_change' => ['label' => 'Aucune modification', 'tone' => 'warn'],
        'failed' => ['label' => 'Échec', 'tone' => 'danger'],
    ];

    /** Demandes encore suivies par le panel : une seule à la fois par sujet. */
    public const ACTIVE_STATUSES = ['sent', 'pr_open'];

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status]['label'] ?? (string) $this->status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status]['tone'] ?? 'neutral';
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(VerificationPage::class, 'page_id')->withTrashed();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
