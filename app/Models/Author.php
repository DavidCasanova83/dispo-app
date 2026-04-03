<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Author extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'default_image_path',
    ];

    protected static function booted(): void
    {
        static::creating(function (Author $author) {
            if (empty($author->slug)) {
                $author->slug = Str::slug($author->name);
            }
        });

        static::updating(function (Author $author) {
            if ($author->isDirty('name')) {
                $author->slug = Str::slug($author->name);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Les images de cet auteur
     */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    /**
     * Vérifie si l'auteur a une image par défaut
     */
    public function hasDefaultImage(): bool
    {
        return !empty($this->default_image_path) && file_exists(storage_path('app/public/' . $this->default_image_path));
    }

    /**
     * Retourne l'URL de l'image par défaut
     */
    public function getDefaultImageUrl(): ?string
    {
        if ($this->hasDefaultImage()) {
            return asset('storage/' . $this->default_image_path);
        }

        return null;
    }
}
