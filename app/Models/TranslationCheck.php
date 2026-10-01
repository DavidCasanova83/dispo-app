<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * État de la traduction EN ou IT d'une page (une ligne par page et par langue).
 * Une traduction sans ligne est « à vérifier ».
 */
class TranslationCheck extends Model
{
    protected $fillable = [
        'page_id',
        'language',
        'status',
        'comment',
        'checked_by',
        'checked_at',
        'admin_response',
        'handled_by',
        'handled_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'handled_at' => 'datetime',
    ];

    public const LANGUAGES = [
        'en' => ['label' => 'Anglais', 'short' => 'EN', 'flag' => '🇬🇧'],
        'it' => ['label' => 'Italien', 'short' => 'IT', 'flag' => '🇮🇹'],
    ];

    /**
     * Source unique des libellés et couleurs (tons de <x-verif.status-badge>).
     * 'to_check' est virtuel : il correspond à l'absence de ligne.
     */
    public const STATUSES = [
        'to_check' => ['label' => 'À vérifier', 'tone' => 'neutral'],
        'to_fix' => ['label' => 'À corriger', 'tone' => 'danger'],
        'in_progress' => ['label' => 'En cours', 'tone' => 'info'],
        'correct' => ['label' => 'Correcte', 'tone' => 'success'],
        'fixed' => ['label' => 'Corrigée', 'tone' => 'success'],
    ];

    /** Statuts considérés comme validés (plus rien à faire). */
    public const VALIDATED_STATUSES = ['correct', 'fixed'];

    /**
     * Filtres proposés dans les écrans : les deux statuts validés y sont
     * regroupés, la distinction reste visible sur les badges.
     */
    public const FILTERS = [
        'to_check' => ['label' => 'À vérifier', 'tone' => 'neutral', 'statuses' => null],
        'to_fix' => ['label' => 'À corriger', 'tone' => 'danger', 'statuses' => ['to_fix']],
        'in_progress' => ['label' => 'En cours', 'tone' => 'info', 'statuses' => ['in_progress']],
        'validated' => ['label' => 'Validées', 'tone' => 'success', 'statuses' => self::VALIDATED_STATUSES],
    ];

    public static function statusLabelFor(?self $check): string
    {
        return self::STATUSES[$check?->status ?? 'to_check']['label'];
    }

    public static function statusToneFor(?self $check): string
    {
        return self::STATUSES[$check?->status ?? 'to_check']['tone'];
    }

    public function isValidated(): bool
    {
        return in_array($this->status, self::VALIDATED_STATUSES, true);
    }

    /**
     * Le traducteur peut modifier son verdict tant que l'admin n'a pas
     * commencé à traiter la correction.
     */
    public function isEditableByTranslator(): bool
    {
        return in_array($this->status, ['correct', 'to_fix'], true);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(VerificationPage::class, 'page_id');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
