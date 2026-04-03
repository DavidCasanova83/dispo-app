<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Sector extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sector $sector) {
            if (empty($sector->slug)) {
                $sector->slug = Str::slug($sector->name);
            }
        });

        static::updating(function (Sector $sector) {
            if ($sector->isDirty('name')) {
                $sector->slug = Str::slug($sector->name);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Les images de ce secteur
     */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }
}
