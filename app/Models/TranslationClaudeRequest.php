<?php

namespace App\Models;

use App\Models\Concerns\HasClaudeRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Correction d'une traduction EN/IT demandée à Claude : issue GitHub @claude
 * dans le dépôt du site, puis PR créée par le panel (TranslationClaudeService).
 */
class TranslationClaudeRequest extends Model
{
    use HasClaudeRequestStatus;

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

    public function translationCheck(): BelongsTo
    {
        return $this->belongsTo(TranslationCheck::class);
    }
}
