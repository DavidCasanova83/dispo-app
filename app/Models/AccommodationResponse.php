<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccommodationResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'accommodation_id',
        'is_available',
        'response_token',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the accommodation that owns this response.
     */
    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    /**
     * Scope for available responses.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope for unavailable responses.
     */
    public function scopeUnavailable($query)
    {
        return $query->where('is_available', false);
    }
}
