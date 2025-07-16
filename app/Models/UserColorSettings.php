<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserColorSettings extends Model
{
    protected $fillable = [
        'user_id',
        'primary_color',
        'secondary_color',
        'accent_color',
        'background_color',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getDefaultColors(): array
    {
        return [
            'primary_color' => '#3A9C92',
            'secondary_color' => '#7AB6A8',
            'accent_color' => '#FFFDF4',
            'background_color' => '#FAF7F3',
        ];
    }

    public function toCssVariables(): array
    {
        return [
            '--color-primary' => $this->primary_color,
            '--color-secondary' => $this->secondary_color,
            '--color-accent' => $this->accent_color,
            '--color-background' => $this->background_color,
        ];
    }
}
