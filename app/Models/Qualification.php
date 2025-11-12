<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Qualification extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<string>
   */
  protected $fillable = [
    'city',
    'user_id',
    'current_step',
    'form_data',
    'completed',
    'completed_at',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'form_data' => 'array',
    'completed' => 'boolean',
    'current_step' => 'integer',
    'completed_at' => 'datetime',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
  ];

  /**
   * Get the user that owns the qualification.
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get the available cities.
   */
  public static function getCities(): array
  {
    return [
      'annot' => 'Annot',
      'colmars-les-alpes' => 'Colmars-les-Alpes',
      'entrevaux' => 'Entrevaux',
      'la-palud-sur-verdon' => 'La Palud-sur-Verdon',
      'saint-andre-les-alpes' => 'Saint-André-les-Alpes',
    ];
  }

  /**
   * Get the city display name.
   */
  public function getCityDisplayNameAttribute(): string
  {
    return self::getCities()[$this->city] ?? $this->city;
  }

  /**
   * Scope a query to only include completed qualifications.
   */
  public function scopeCompleted($query)
  {
    return $query->where('completed', true);
  }

  /**
   * Scope a query to only include incomplete qualifications.
   */
  public function scopeIncomplete($query)
  {
    return $query->where('completed', false);
  }

  /**
   * Scope a query to filter by city.
   */
  public function scopeForCity($query, string $city)
  {
    return $query->where('city', $city);
  }

  /**
   * Mark the qualification as completed.
   */
  public function markAsCompleted(): void
  {
    $this->update([
      'completed' => true,
      'completed_at' => now(),
    ]);
  }

  /**
   * Get the specific requests for the city from form_data.
   */
  public function getSpecificRequestsAttribute(): array
  {
    return $this->form_data['specificRequests'] ?? [];
  }

  /**
   * Get the general requests from form_data.
   */
  public function getGeneralRequestsAttribute(): array
  {
    return $this->form_data['generalRequests'] ?? [];
  }
}
