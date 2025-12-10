<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrochureClick extends Model
{
    use HasFactory;

    // Constantes pour les types de boutons
    public const TYPE_CONSULTER = 'consulter';
    public const TYPE_TELECHARGER = 'telecharger';
    public const TYPE_COPIER_LIEN = 'copier_lien';

    public const BUTTON_TYPES = [
        self::TYPE_CONSULTER,
        self::TYPE_TELECHARGER,
        self::TYPE_COPIER_LIEN,
    ];

    public const BUTTON_LABELS = [
        self::TYPE_CONSULTER => 'Consulter',
        self::TYPE_TELECHARGER => 'Télécharger',
        self::TYPE_COPIER_LIEN => 'Copier Lien',
    ];

    protected $fillable = [
        'image_id',
        'user_id',
        'button_type',
        'ip_address',
        'user_agent',
    ];

    /**
     * Relation avec la brochure (Image)
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    /**
     * Relation avec l'utilisateur (nullable pour anonymes)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope pour filtrer par type de bouton
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('button_type', $type);
    }

    /**
     * Scope pour filtrer par plage de dates
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
