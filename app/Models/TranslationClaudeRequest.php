<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Correction d'une traduction EN/IT demandée à Claude : issue GitHub @claude
 * dans le dépôt du site, puis PR créée par le panel (TranslationClaudeService).
 */
class TranslationClaudeRequest extends Model
{
    protected $fillable = [
        'reference',
        'page_id',
        'translation_check_id',
        'language',
        'status',
        'translator_comment',
        'message',
        'issue_number',
        'issue_url',
        'branch',
        'pr_number',
        'pr_url',
        'error',
        'requested_by',
        'sent_at',
        'finished_at',
    ];

    protected $casts = [
        'issue_number' => 'integer',
        'pr_number' => 'integer',
        'sent_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /** Libellés et tons de <x-verif.status-badge>. */
    public const STATUSES = [
        'sent' => ['label' => 'Chez Claude', 'tone' => 'info'],
        'pr_open' => ['label' => 'PR ouverte', 'tone' => 'accent'],
        'merged' => ['label' => 'PR fusionnée', 'tone' => 'success'],
        'closed' => ['label' => 'PR fermée sans fusion', 'tone' => 'neutral'],
        'no_change' => ['label' => 'Aucune modification', 'tone' => 'warn'],
        'failed' => ['label' => 'Échec', 'tone' => 'danger'],
    ];

    /** Demandes encore suivies par le panel : une seule à la fois par traduction. */
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

    public function translationCheck(): BelongsTo
    {
        return $this->belongsTo(TranslationCheck::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
