<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VerificationPage extends Model
{
    use HasFactory;

    // Les FK page_id sont en ON DELETE CASCADE : sans SoftDeletes, supprimer une
    // page effacerait aussi définitivement ses relectures et ses assignations.
    use SoftDeletes;

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
        'cycle',
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
        if ($this->url_en) {
            $langs[] = 'en';
        }
        if ($this->url_it) {
            $langs[] = 'it';
        }

        return $langs;
    }

    protected $casts = [
        'deadline' => 'date',
        'last_seen_in_sitemap_at' => 'datetime',
        'validated_at' => 'datetime',
        'is_in_sitemap' => 'boolean',
        'cycle' => 'integer',
    ];

    public const CATEGORIES = [
        'decouvrir' => 'Découvrir',
        'sejourner' => 'Séjourner',
        'activites' => 'Activités',
        'agenda' => 'Agenda',
        'infos_pratiques' => 'Infos Pratiques',
    ];

    /**
     * Source unique des libellés et couleurs de statut.
     * Les vues passent par <x-verif.status-badge> plutôt que de refaire un match().
     */
    public const STATUSES = [
        'pending' => ['label' => 'En relecture', 'tone' => 'info'],
        'in_progress' => ['label' => 'Correction en cours', 'tone' => 'info'],
        'needs_fix' => ['label' => 'Retours à traiter', 'tone' => 'warn'],
        'awaiting_validation' => ['label' => 'Prête à clôturer', 'tone' => 'accent'],
        'validated' => ['label' => 'Vérifiée', 'tone' => 'success'],
    ];

    /**
     * Étapes affichées dans l'espace « Vérification des pages ». Le statut
     * 'pending' y est détaillé : sans relecteur, en attente de distribution,
     * ou en relecture. Dans l'ordre du circuit.
     */
    public const STAGES = [
        'unassigned' => ['label' => 'Sans relecteur', 'short' => 'Sans relecteur', 'tone' => 'danger'],
        'queued' => ['label' => 'En attente de distribution', 'short' => 'En attente', 'tone' => 'neutral'],
        'reviewing' => ['label' => 'En relecture', 'short' => 'En relecture', 'tone' => 'info'],
        'to_handle' => ['label' => 'Retours à traiter', 'short' => 'Retours à traiter', 'tone' => 'warn'],
        'correcting' => ['label' => 'Correction en cours', 'short' => 'Correction en cours', 'tone' => 'info'],
        'ready' => ['label' => 'Prête à clôturer', 'short' => 'Prêtes à clôturer', 'tone' => 'accent'],
        'verified' => ['label' => 'Vérifiée', 'short' => 'Vérifiées', 'tone' => 'success'],
    ];

    /** Statut de page correspondant directement à une étape. */
    private const STAGE_STATUSES = [
        'to_handle' => 'needs_fix',
        'correcting' => 'in_progress',
        'ready' => 'awaiting_validation',
        'verified' => 'validated',
    ];

    /**
     * Où en est chaque relecteur sur la page (colonne « Avancement »).
     */
    public const REVIEWER_STATES = [
        'done' => ['label' => 'A répondu', 'icon' => '✓', 'tone' => 'success'],
        'new' => ['label' => 'Retour à traiter', 'icon' => '!', 'tone' => 'warn'],
        'correcting' => ['label' => 'Retour en correction', 'icon' => '…', 'tone' => 'info'],
        'back' => ['label' => 'Renvoyée, à revoir', 'icon' => '↺', 'tone' => 'accent'],
        'open' => ['label' => 'Page chez le relecteur', 'icon' => '●', 'tone' => 'info'],
        'wait' => ['label' => 'En attente de distribution', 'icon' => '⏳', 'tone' => 'neutral'],
    ];

    /** Ordre de tri des priorités, de la plus urgente à la moins urgente. */
    public const PRIORITY_ORDER = ['high', 'medium', 'low'];

    /**
     * Expression SQL portable (MySQL/MariaDB et SQLite) équivalente à
     * FIELD($column, ...$values) : rang 1, 2, 3… selon l'ordre de $values, 0 sinon.
     * $column et $values viennent du code, jamais de l'utilisateur.
     */
    public static function orderBySql(string $column, array $values): string
    {
        $cases = collect($values)
            ->values()
            ->map(fn (string $value, int $i) => "WHEN '{$value}' THEN ".($i + 1))
            ->implode(' ');

        return "CASE {$column} {$cases} ELSE 0 END";
    }

    public static function priorityOrderSql(string $column = 'priority'): string
    {
        return self::orderBySql($column, self::PRIORITY_ORDER);
    }

    public static function statusOrderSql(string $column = 'status'): string
    {
        return self::orderBySql($column, array_keys(self::STATUSES));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status]['label'] ?? (string) $this->status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status]['tone'] ?? 'neutral';
    }

    // ─── ÉTAPES ───────────────────────────────────────────────────

    /**
     * Expression SQL portable (MySQL/MariaDB et SQLite) donnant l'étape d'une
     * page. Même règle que stage() et scopeInStage() : les trois doivent rester
     * alignées (les compteurs du bandeau correspondent aux lignes filtrées).
     */
    public static function stageSql(): string
    {
        $assignments = 'SELECT 1 FROM verification_assignments va WHERE va.page_id = verification_pages.id';

        return "CASE
            WHEN verification_pages.status = 'validated' THEN 'verified'
            WHEN verification_pages.status = 'awaiting_validation' THEN 'ready'
            WHEN verification_pages.status = 'in_progress' THEN 'correcting'
            WHEN verification_pages.status = 'needs_fix' THEN 'to_handle'
            WHEN NOT EXISTS ({$assignments}) THEN 'unassigned'
            WHEN NOT EXISTS ({$assignments} AND va.released_at IS NOT NULL) THEN 'queued'
            ELSE 'reviewing'
        END";
    }

    public function scopeInStage($query, string $stage)
    {
        if (isset(self::STAGE_STATUSES[$stage])) {
            return $query->where('status', self::STAGE_STATUSES[$stage]);
        }

        $query->whereNotIn('status', array_values(self::STAGE_STATUSES));

        return match ($stage) {
            'unassigned' => $query->whereDoesntHave('assignees'),
            'queued' => $query->whereHas('assignees')
                ->whereDoesntHave('assignees', fn ($q) => $q->whereNotNull('verification_assignments.released_at')),
            'reviewing' => $query->whereHas('assignees', fn ($q) => $q->whereNotNull('verification_assignments.released_at')),
            default => $query,
        };
    }

    /** Étape de la page, à partir des assignations (chargées si elles le sont). */
    public function stage(): string
    {
        if ($stage = array_search($this->status, self::STAGE_STATUSES, true)) {
            return $stage;
        }

        if ($this->assignees->isEmpty()) {
            return 'unassigned';
        }

        return $this->assignees->contains(fn ($u) => $u->pivot->released_at !== null) ? 'reviewing' : 'queued';
    }

    public function stageLabel(): string
    {
        return self::STAGES[$this->stage()]['label'];
    }

    public function stageTone(): string
    {
        return self::STAGES[$this->stage()]['tone'];
    }

    /**
     * Avancement de chaque relecteur sur le cycle en cours : les relecteurs
     * assignés, puis ceux qui ont rendu une relecture FR sans être (ou sans
     * être encore) assignés. S'appuie sur les relations assignees et reviews,
     * chargées une fois.
     *
     * @return \Illuminate\Support\Collection<int, array{user_id: int|null, name: string, assigned: bool, state: string, label: string, since: \Illuminate\Support\Carbon|null, review: VerificationReview|null}>
     */
    public function reviewerProgress(): \Illuminate\Support\Collection
    {
        // Clé « deleted-<id> » : les relectures de comptes supprimés (user_id nul)
        // gardent chacune leur ligne.
        $frReviews = $this->reviews->where('language', 'fr')
            ->keyBy(fn ($review) => $review->user_id ?? 'deleted-'.$review->id);

        $rows = $this->assignees->map(function (User $user) use ($frReviews) {
            $review = $frReviews->get($user->id);
            $releasedAt = $user->pivot->released_at ? \Illuminate\Support\Carbon::parse($user->pivot->released_at) : null;

            return $this->progressRow($user->id, $user->name, true, $review, $releasedAt);
        });

        $extra = $frReviews
            ->reject(fn ($review) => $review->user_id !== null && $this->assignees->contains('id', $review->user_id))
            ->map(fn ($review) => $this->progressRow($review->user_id, $review->user?->name ?? 'Utilisateur supprimé', false, $review, null));

        return $rows->concat($extra->values())->values();
    }

    private function progressRow(?int $userId, string $name, bool $assigned, ?VerificationReview $review, ?\Illuminate\Support\Carbon $releasedAt): array
    {
        [$state, $since] = match (true) {
            $review?->status === 'done' => ['done', $review->updated_at],
            $review?->status === 'pending_admin' => ['new', $review->submittedAt()],
            $review?->status === 'in_progress' => ['correcting', $review->updated_at],
            $review?->status === 'revision_requested' => ['back', $review->updated_at],
            $releasedAt !== null => ['open', $releasedAt],
            default => ['wait', null],
        };

        $label = $state === 'done'
            ? ($review->isAllClear() ? 'Rien à signaler' : 'Retour réglé')
            : self::REVIEWER_STATES[$state]['label'];

        return [
            'user_id' => $userId,
            'name' => $name,
            'assigned' => $assigned,
            'state' => $state,
            'label' => $label,
            'since' => $since,
            'review' => $review,
        ];
    }

    /** « 1/3 » : relecteurs assignés ayant une relecture FR réglée, sur le total. */
    public function progressSummary(): ?string
    {
        $assigned = $this->reviewerProgress()->where('assigned', true);

        return $assigned->isEmpty() ? null : $assigned->where('state', 'done')->count().'/'.$assigned->count();
    }

    // ─── PRÉSENCE SUR LE SITE ─────────────────────────────────────

    /** Vue lors d'un scan du site, absente du dernier. */
    public function isOutOfSite(): bool
    {
        return ! $this->is_in_sitemap && $this->last_seen_in_sitemap_at !== null;
    }

    public function scopeOutOfSite($query)
    {
        return $query->where('is_in_sitemap', false)->whereNotNull('last_seen_in_sitemap_at');
    }

    /** Pages du site : dans le dernier scan, ou créées à la main et jamais scannées. */
    public function scopeOnSite($query)
    {
        return $query->where(fn ($q) => $q
            ->where('is_in_sitemap', true)
            ->orWhereNull('last_seen_in_sitemap_at'));
    }

    /**
     * Nombre de jours avant la deadline : négatif si elle est dépassée,
     * null si aucune deadline n'est fixée ou si la page est déjà validée.
     */
    public function daysToDeadline(): ?int
    {
        if (! $this->deadline || $this->status === 'validated') {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->deadline->startOfDay(), false);
    }

    public function isOverdue(): bool
    {
        $days = $this->daysToDeadline();

        return $days !== null && $days < 0;
    }

    /**
     * Durée d'un cycle de vérification. Une page clôturée repasse en « à vérifier »
     * après ce délai (commande verification:revalidate-aged, exécutée chaque nuit).
     */
    public const REVALIDATION_DAYS = 365;

    /**
     * Date du prochain passage en « à vérifier » pour une page clôturée.
     * null si la page n'est pas validée (elle est déjà dans le circuit).
     */
    public function nextVerificationAt(): ?\Illuminate\Support\Carbon
    {
        if ($this->status !== 'validated' || ! $this->validated_at) {
            return null;
        }

        return $this->validated_at->copy()->addDays(self::REVALIDATION_DAYS);
    }

    /**
     * Nombre de jours avant la prochaine vérification. Négatif ou nul si le
     * renouvellement est dû (le cron n'est simplement pas encore passé).
     */
    public function daysUntilNextVerification(): ?int
    {
        $next = $this->nextVerificationAt();

        return $next === null ? null : (int) now()->startOfDay()->diffInDays($next->startOfDay(), false);
    }

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

    /**
     * Relectures du cycle en cours. Celles des cycles clôturés sont archivées
     * (archived_at) : conservées, consultables, hors de tout calcul.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(VerificationReview::class, 'page_id')->whereNull('archived_at');
    }

    /** Toutes les relectures, cycles clôturés compris. */
    public function allReviews(): HasMany
    {
        return $this->hasMany(VerificationReview::class, 'page_id');
    }

    public function archivedReviews(): HasMany
    {
        return $this->hasMany(VerificationReview::class, 'page_id')->whereNotNull('archived_at');
    }

    /** Journal de la page, du plus récent au plus ancien. */
    public function events(): HasMany
    {
        return $this->hasMany(VerificationEvent::class, 'page_id')->latest('created_at')->latest('id');
    }

    public function translationChecks(): HasMany
    {
        return $this->hasMany(TranslationCheck::class, 'page_id');
    }

    /**
     * État de traduction d'une langue (null = à vérifier). S'appuie sur la
     * relation chargée quand elle l'est, pour éviter une requête par ligne.
     */
    public function translationCheckFor(string $language): ?TranslationCheck
    {
        return $this->translationChecks->firstWhere('language', $language);
    }

    /** Corrections de traduction demandées à Claude (toutes langues, historique). */
    public function claudeRequests(): HasMany
    {
        return $this->hasMany(TranslationClaudeRequest::class, 'page_id');
    }

    /** Dernière demande à Claude pour une langue (relation chargée quand elle l'est). */
    public function latestClaudeRequestFor(string $language): ?TranslationClaudeRequest
    {
        return $this->claudeRequests
            ->where('language', $language)
            ->sortByDesc('id')
            ->first();
    }

    /**
     * Pages concernées par la vérification des traductions : toutes, sauf celles
     * sorties du sitemap (vues lors d'un scan, absentes du dernier). Les pages
     * créées à la main, jamais scannées, restent incluses.
     */
    public function scopeForTranslation($query)
    {
        return $query->onSite();
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
