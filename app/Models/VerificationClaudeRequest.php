<?php

namespace App\Models;

use App\Models\Concerns\HasClaudeRequestStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Correction d'une page demandée à Claude à partir des retours des
 * relecteurs : issue GitHub @claude dans le dépôt du site, puis PR créée par
 * le panel (VerificationClaudeService).
 */
class VerificationClaudeRequest extends Model
{
    use HasClaudeRequestStatus;

    protected $fillable = [
        'reference',
        'page_id',
        'cycle',
        'status',
        'review_ids',
        'feedback',
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
        'cycle' => 'integer',
        'review_ids' => 'array',
        'issue_number' => 'integer',
        'pr_number' => 'integer',
        'sent_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /** Nombre de retours envoyés à Claude. */
    public function reviewCount(): int
    {
        return count($this->review_ids ?? []);
    }
}
