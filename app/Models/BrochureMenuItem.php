<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BrochureMenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'title',
        'url',
        'sort_order',
        'is_active',
        'auth_only',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auth_only' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Élément parent (null si c'est un item de premier niveau)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Sous-éléments de ce menu
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Scope : uniquement les items de premier niveau
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope : uniquement les items actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Récupère le menu complet ordonné (items parents + enfants actifs)
     * Filtre les items auth_only si l'utilisateur n'est pas connecté
     */
    public static function getOrderedMenu(bool $isAuthenticated = false)
    {
        return self::topLevel()
            ->active()
            ->when(!$isAuthenticated, fn($q) => $q->where('auth_only', false))
            ->with(['children' => fn($q) => $q->active()
                ->when(!$isAuthenticated, fn($q2) => $q2->where('auth_only', false))
                ->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();
    }
}
