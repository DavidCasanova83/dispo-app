<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'user_id',
        'language',
        'content_ok',
        'content_comment',
        'media_ok',
        'media_comment',
        'remarks',
        'relevance',
        'content_to_add',
        'content_to_add_details',
        'content_to_remove',
        'content_to_remove_details',
        'visitor_perspective',
        'visitor_unanswered',
        'rating',
        'suggestions',
        'admin_response',
        'admin_response_at',
        'status',
    ];

    public const LANGUAGE_FLAGS = [
        'fr' => '🇫🇷',
        'en' => '🇬🇧',
        'it' => '🇮🇹',
    ];

    public function languageFlag(): string
    {
        return self::LANGUAGE_FLAGS[$this->language] ?? '🏳️';
    }

    public function languageLabel(): string
    {
        return strtoupper($this->language ?? 'fr');
    }

    protected $casts = [
        'content_ok' => 'boolean',
        'media_ok' => 'boolean',
        'content_to_add' => 'array',
        'rating' => 'integer',
        'admin_response_at' => 'datetime',
    ];

    public const RELEVANCE_LABELS = [
        'very' => 'Très pertinent',
        'rather' => 'Plutôt pertinent',
        'medium' => 'Moyennement pertinent',
        'low' => 'Peu pertinent',
    ];

    public const CONTENT_TO_ADD_OPTIONS = [
        'practical_info' => 'Informations pratiques (horaires, accès, tarifs…)',
        'media' => 'Photos ou vidéos',
        'providers' => 'Prestataires / lieux / événements à mentionner',
        'context' => 'Contexte (histoire, anecdotes, conseils locaux)',
        'links' => 'Liens vers d\'autres pages utiles',
        'none' => 'Non, le contenu actuel est suffisant',
    ];

    public const CONTENT_TO_REMOVE_LABELS = [
        'no' => 'Non, tout le contenu est utile',
        'yes' => 'Oui, certains éléments sont obsolètes ou inutiles',
        'dont_know' => 'Je ne sais pas',
    ];

    public const VISITOR_PERSPECTIVE_LABELS = [
        'yes' => 'Oui, toutes les infos sont là',
        'partial' => 'Partiellement',
        'no' => 'Non, la page ne répond pas vraiment',
    ];

    public function relevanceLabel(): ?string
    {
        return self::RELEVANCE_LABELS[$this->relevance] ?? null;
    }

    public function contentToRemoveLabel(): ?string
    {
        return self::CONTENT_TO_REMOVE_LABELS[$this->content_to_remove] ?? null;
    }

    public function visitorPerspectiveLabel(): ?string
    {
        return self::VISITOR_PERSPECTIVE_LABELS[$this->visitor_perspective] ?? null;
    }

    public function contentToAddLabels(): array
    {
        $codes = $this->content_to_add ?? [];

        return collect($codes)
            ->map(fn ($code) => self::CONTENT_TO_ADD_OPTIONS[$code] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    public function hasEditorialAssessment(): bool
    {
        return $this->relevance !== null
            || ! empty($this->content_to_add)
            || $this->content_to_remove !== null
            || $this->visitor_perspective !== null
            || $this->rating !== null
            || ! empty($this->suggestions);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(VerificationPage::class, 'page_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Source unique des libellés et couleurs de statut côté admin.
     * (userFacingStatus() reste la variante émoji destinée au relecteur.)
     */
    public const STATUSES = [
        'pending_admin' => ['label' => 'À traiter', 'tone' => 'warn'],
        'in_progress' => ['label' => 'En cours', 'tone' => 'info'],
        'revision_requested' => ['label' => 'À ré-vérifier', 'tone' => 'accent'],
        'done' => ['label' => 'Validée', 'tone' => 'success'],
    ];

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status]['label'] ?? (string) $this->status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status]['tone'] ?? 'neutral';
    }

    public function userFacingStatus(): string
    {
        return match ($this->status) {
            'done' => '✅ Validée',
            'in_progress' => '🛠️ En cours de modification',
            'revision_requested' => '🔄 À ré-vérifier',
            'pending_admin' => '⏳ En attente de traitement',
            default => '⏳ En attente de traitement',
        };
    }

    public function userFacingStatusColor(): string
    {
        return match ($this->status) {
            'done' => 'green',
            'in_progress' => 'orange',
            'revision_requested' => 'blue',
            'pending_admin' => 'gray',
            default => 'gray',
        };
    }

    public function hasIssues(): bool
    {
        return ! $this->content_ok
            || ! $this->media_ok
            || ! empty($this->content_comment)
            || ! empty($this->media_comment)
            || ! empty($this->remarks);
    }
}
