<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne du journal d'une page (voir VerificationJournal). Jamais modifiée ni
 * supprimée par l'application.
 */
class VerificationEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'page_id',
        'cycle',
        'type',
        'review_id',
        'user_id',
        'actor_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(VerificationPage::class, 'page_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** Nom du relecteur concerné, même si son compte a été supprimé depuis. */
    public function userName(): string
    {
        return $this->user?->name ?? ($this->data['user_name'] ?? 'un relecteur supprimé');
    }

    public function actorName(): string
    {
        return $this->actor?->name ?? ($this->data['actor_name'] ?? 'le système');
    }

    /** Phrase affichée dans le journal de la fiche page. */
    public function describe(): string
    {
        $data = $this->data ?? [];
        $user = $this->userName();

        return match ($this->type) {
            'created' => ($data['source'] ?? null) === 'scan'
                ? 'Page ajoutée par le scan du site'
                : 'Page ajoutée par '.$this->actorName(),
            'assigned' => "{$user} assigné(e) par {$this->actorName()}",
            'unassigned' => "{$user} retiré(e) des relecteurs par {$this->actorName()}",
            'distributed' => ($data['mode'] ?? null) === 'manuel'
                ? "Page distribuée à {$user} (distribution immédiate)"
                : "Page distribuée à {$user}",
            'review_submitted' => match (true) {
                (bool) ($data['resubmitted'] ?? false) => "{$user} a renvoyé sa relecture".($data['all_clear'] ?? false ? ' : rien à signaler' : ' avec des points à corriger'),
                (bool) ($data['all_clear'] ?? false) => "{$user} : rien à signaler",
                default => "{$user} a signalé des points à corriger",
            },
            'review_resolved' => sprintf(
                '%s : retour de %s %s%s',
                $this->actorName(),
                $user,
                match ($data['to'] ?? null) {
                    'done' => 'marqué réglé',
                    'in_progress' => 'pris en charge',
                    'revision_requested' => 'renvoyé au relecteur',
                    default => 'traité',
                },
                ! empty($data['message']) ? ', avec une réponse' : ''
            ),
            'closed' => sprintf('Cycle %d clôturé par %s (%d relecture(s) archivée(s))', $data['cycle'] ?? $this->cycle, $this->actorName(), $data['archived'] ?? 0),
            'renewed' => 'Renouvellement annuel : la page repart en relecture',
            'removed' => 'Retirée du suivi par '.$this->actorName(),
            'restored' => 'Restaurée par '.$this->actorName(),
            default => $this->type,
        };
    }
}
