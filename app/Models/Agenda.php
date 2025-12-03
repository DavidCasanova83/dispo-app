<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Agenda extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'pdf_path',
        'pdf_filename',
        'description',
        'start_date',
        'end_date',
        'is_current',
        'archived_at',
        'uploaded_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'archived_at' => 'datetime',
        'is_current' => 'boolean',
    ];

    /**
     * Chemins fixes pour l'image de couverture globale
     */
    public const COVER_IMAGE_PATH = 'agendas/couverture.jpg';
    public const COVER_THUMBNAIL_PATH = 'agendas/couverture-thumb.jpg';

    /**
     * Relation avec l'utilisateur qui a uploadé l'agenda
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope pour l'agenda en cours
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope pour les agendas archivés
     */
    public function scopeArchived($query)
    {
        return $query->where('is_current', false)->whereNotNull('archived_at');
    }

    /**
     * Supprimer les fichiers physiques quand le model est supprimé
     */
    protected static function booted(): void
    {
        static::deleting(function (Agenda $agenda) {
            // Supprimer le PDF si il existe (sauf si c'est agenda-en-cours.pdf)
            if ($agenda->pdf_path && Storage::disk('public')->exists($agenda->pdf_path)) {
                // Ne pas supprimer agenda-en-cours.pdf
                if ($agenda->pdf_path !== 'agendas/agenda-en-cours.pdf') {
                    Storage::disk('public')->delete($agenda->pdf_path);
                }
            }
        });
    }

    /**
     * Obtenir l'URL de l'image de couverture globale
     */
    public static function getCoverImageUrl(): ?string
    {
        if (Storage::disk('public')->exists(self::COVER_IMAGE_PATH)) {
            return asset('storage/' . self::COVER_IMAGE_PATH);
        }
        return null;
    }

    /**
     * Obtenir l'URL du thumbnail de couverture globale
     */
    public static function getCoverThumbnailUrl(): ?string
    {
        if (Storage::disk('public')->exists(self::COVER_THUMBNAIL_PATH)) {
            return asset('storage/' . self::COVER_THUMBNAIL_PATH);
        }
        return null;
    }

    /**
     * Vérifier si une image de couverture existe
     */
    public static function hasCoverImage(): bool
    {
        return Storage::disk('public')->exists(self::COVER_IMAGE_PATH);
    }

    /**
     * Obtenir la période formatée
     */
    public function getPeriodAttribute(): string
    {
        return $this->start_date->format('d/m/Y') . ' - ' . $this->end_date->format('d/m/Y');
    }
}
