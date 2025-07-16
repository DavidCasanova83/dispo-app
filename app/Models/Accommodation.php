<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Accommodation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'apidae_id',
        'name',
        'city',
        'email',
        'phone',
        'website',
        'description',
        'type',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the status options with labels.
     */
    public static function getStatusOptions(): array
    {
        return [
            'pending' => 'En attente',
            'active' => 'Actif',
            'inactive' => 'Inactif',
        ];
    }

    // ==================== SCOPES OPTIMISÉS ====================

    /**
     * Scope a query to only include active accommodations.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include pending accommodations.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include inactive accommodations.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope a query to include accommodations with contact information.
     */
    public function scopeWithContact(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNotNull('email')
              ->orWhereNotNull('phone')
              ->orWhereNotNull('website');
        });
    }

    /**
     * Scope a query to filter by city.
     */
    public function scopeByCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to search by name (optimized for LIKE queries).
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', "%{$search}%");
    }

    /**
     * Scope a query to include accommodations with email.
     */
    public function scopeWithEmail(Builder $query): Builder
    {
        return $query->whereNotNull('email');
    }

    /**
     * Scope a query to include accommodations with phone.
     */
    public function scopeWithPhone(Builder $query): Builder
    {
        return $query->whereNotNull('phone');
    }

    /**
     * Scope a query to include accommodations with website.
     */
    public function scopeWithWebsite(Builder $query): Builder
    {
        return $query->whereNotNull('website');
    }

    /**
     * Scope a query to get recent accommodations.
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to order by most popular (for future use with ratings/reviews).
     */
    public function scopePopular(Builder $query): Builder
    {
        return $query->orderBy('name'); // For now, order by name
    }

    // ==================== ACCESSEURS OPTIMISÉS ====================

    /**
     * Get the accommodation's display name with type.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->type ? "{$this->name} ({$this->type})" : $this->name;
    }

    /**
     * Get the status label in French.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Actif',
            'pending' => 'En attente',
            'inactive' => 'Inactif',
            default => 'Inconnu'
        };
    }

    /**
     * Get a formatted creation date.
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('d/m/Y à H:i');
    }

    /**
     * Get the first letter of the name for avatars.
     */
    public function getInitialAttribute(): string
    {
        return strtoupper(substr($this->name, 0, 1));
    }

    // ==================== MÉTHODES MÉTIER ====================

    /**
     * Check if the accommodation has contact information.
     */
    public function hasContactInfo(): bool
    {
        return !empty($this->email) || !empty($this->phone) || !empty($this->website);
    }

    /**
     * Check if the accommodation has all contact information.
     */
    public function hasCompleteContactInfo(): bool
    {
        return !empty($this->email) && !empty($this->phone) && !empty($this->website);
    }

    /**
     * Get the contact information count.
     */
    public function getContactInfoCount(): int
    {
        $count = 0;
        if (!empty($this->email)) $count++;
        if (!empty($this->phone)) $count++;
        if (!empty($this->website)) $count++;
        return $count;
    }

    /**
     * Check if accommodation is recently created.
     */
    public function isRecent(int $days = 7): bool
    {
        return $this->created_at >= now()->subDays($days);
    }

    /**
     * Get formatted phone number for display.
     */
    public function getFormattedPhoneAttribute(): ?string
    {
        if (empty($this->phone)) {
            return null;
        }

        // Basic French phone number formatting
        $phone = preg_replace('/[^0-9]/', '', $this->phone);
        if (strlen($phone) === 10) {
            return substr($phone, 0, 2) . ' ' . 
                   substr($phone, 2, 2) . ' ' . 
                   substr($phone, 4, 2) . ' ' . 
                   substr($phone, 6, 2) . ' ' . 
                   substr($phone, 8, 2);
        }

        return $this->phone;
    }

    /**
     * Get safe website URL with protocol.
     */
    public function getSafeWebsiteAttribute(): ?string
    {
        if (empty($this->website)) {
            return null;
        }

        if (!str_starts_with($this->website, 'http://') && !str_starts_with($this->website, 'https://')) {
            return 'https://' . $this->website;
        }

        return $this->website;
    }

    /**
     * Get the unique management URL for this accommodation.
     */
    public function getManageUrl(): string
    {
        return route('accommodation.manage', ['apidae_id' => $this->apidae_id]);
    }
}
