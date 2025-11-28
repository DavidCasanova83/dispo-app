<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactFormSubmission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'form_id',
        // Visiteur
        'visiteur_nom',
        'visiteur_prenom',
        'visiteur_email',
        'visiteur_telephone',
        'visiteur_message',
        // Etablissement
        'etablissement_apidae_id',
        'etablissement_nom',
        'etablissement_email',
        // Metadata
        'url_page',
        'date_soumission',
        'ip_visiteur',
        'user_agent',
        // Gestion
        'is_read',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'form_id' => 'integer',
        'date_soumission' => 'datetime',
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope for unread submissions.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read submissions.
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Mark submission as read.
     */
    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Mark submission as unread.
     */
    public function markAsUnread(): void
    {
        $this->update(['is_read' => false]);
    }

    /**
     * Get the visitor's full name.
     */
    public function getVisiteurNomCompletAttribute(): string
    {
        return trim("{$this->visiteur_prenom} {$this->visiteur_nom}");
    }

    /**
     * Get distinct form IDs for filtering.
     */
    public static function getFormIds(): array
    {
        return static::distinct()
            ->pluck('form_id')
            ->sort()
            ->values()
            ->toArray();
    }
}
