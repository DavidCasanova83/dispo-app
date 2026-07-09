<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VerificationPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'url',
        'url_en',
        'url_it',
        'theme',
        'category',
        'priority',
        'deadline',
        'last_seen_in_sitemap_at',
        'is_in_sitemap',
        'status',
        'validated_at',
        'created_by',
    ];

    public const LANGUAGES = [
        'fr' => ['label' => 'Français', 'flag' => '🇫🇷'],
        'en' => ['label' => 'English', 'flag' => '🇬🇧'],
        'it' => ['label' => 'Italiano', 'flag' => '🇮🇹'],
    ];

    public function urlForLanguage(string $lang): ?string
    {
        return match ($lang) {
            'fr' => $this->url,
            'en' => $this->url_en,
            'it' => $this->url_it,
            default => null,
        };
    }

    public function availableLanguages(): array
    {
        $langs = ['fr'];
        if ($this->url_en) $langs[] = 'en';
        if ($this->url_it) $langs[] = 'it';
        return $langs;
    }

    protected $casts = [
        'deadline' => 'date',
        'last_seen_in_sitemap_at' => 'datetime',
        'validated_at' => 'datetime',
        'is_in_sitemap' => 'boolean',
    ];

    public const CATEGORIES = [
        'decouvrir' => 'Découvrir',
        'sejourner' => 'Séjourner',
        'activites' => 'Activités',
        'agenda' => 'Agenda',
        'infos_pratiques' => 'Infos Pratiques',
    ];

    public function categoryLabel(): ?string
    {
        return self::CATEGORIES[$this->category] ?? null;
    }

    public function scopeWithoutAssignee($query)
    {
        return $query->whereDoesntHave('assignees');
    }

    /**
     * Retourne la review FR de l'utilisateur passée en "revision_requested" (avec ou sans message admin),
     * pour afficher au relecteur ce que l'admin lui demande de revoir.
     */
    public function revisionRequestedReviewFor(User $user): ?VerificationReview
    {
        return $this->reviews()
            ->where('user_id', $user->id)
            ->where('language', 'fr')
            ->where('status', 'revision_requested')
            ->latest('updated_at')
            ->first();
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'verification_assignments', 'page_id', 'user_id')
            ->withPivot('released_at')
            ->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(VerificationReview::class, 'page_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePendingForUser($query, User $user)
    {
        // Une page est "pending" pour un user si :
        // - elle lui est assignée ET cette assignation a été LIBÉRÉE (released_at NOT NULL)
        // - le statut page n'est pas 'validated'
        // - il n'a pas de review FR active ('done', 'pending_admin' ou 'in_progress').
        //   Une review 'revision_requested' fait au contraire réapparaître la page (l'admin redemande un passage).
        return $query->whereHas('assignees', fn ($q) => $q
                ->where('users.id', $user->id)
                ->whereNotNull('verification_assignments.released_at'))
            ->where('status', '!=', 'validated')
            ->whereDoesntHave('reviews', fn ($q) => $q->where('user_id', $user->id)
                ->where('language', 'fr')
                ->whereIn('status', ['done', 'pending_admin', 'in_progress']));
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            'high' => 'Priorité haute',
            'medium' => 'Priorité moyenne',
            'low' => 'Priorité basse',
            default => 'Priorité moyenne',
        };
    }

    public function priorityColor(): string
    {
        return match ($this->priority) {
            'high' => 'red',
            'medium' => 'orange',
            'low' => 'green',
            default => 'orange',
        };
    }

    public function priorityIcon(): string
    {
        return match ($this->priority) {
            'high' => '🔴',
            'medium' => '🟠',
            'low' => '🟢',
            default => '🟠',
        };
    }
}
