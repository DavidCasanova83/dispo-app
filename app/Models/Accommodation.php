<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    'email_sent_at',
    'email_response_token',
    'last_response_at',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'email_sent_at' => 'datetime',
    'last_response_at' => 'datetime',
  ];

  /**
   * Get the status options.
   */
  public static function getStatusOptions(): array
  {
    return [
      'en_attente' => 'En attente',
      'disponible' => 'Disponible',
      'indisponible' => 'Indisponible',
    ];
  }

  /**
   * Scope a query to only include active accommodations.
   */
  public function scopeActive($query)
  {
    return $query->where('status', 'disponible');
  }

  /**
   * Scope a query to only include pending accommodations.
   */
  public function scopePending($query)
  {
    return $query->where('status', 'en_attente');
  }

  /**
   * Get the accommodation's display name with type.
   */
  public function getDisplayNameAttribute(): string
  {
    if ($this->type) {
      return "{$this->name} ({$this->type})";
    }
    return $this->name;
  }

  /**
   * Check if the accommodation has contact information.
   */
  public function hasContactInfo(): bool
  {
    return !empty($this->email) || !empty($this->phone) || !empty($this->website);
  }

  /**
   * Generate a unique response token for email tracking.
   */
  public function generateResponseToken(): string
  {
    $token = bin2hex(random_bytes(32));
    $this->update(['email_response_token' => $token]);
    return $token;
  }

  /**
   * Mark the accommodation as having received an email.
   */
  public function markEmailSent(): void
  {
    $this->update(['email_sent_at' => now()]);
  }

  /**
   * Update the availability status based on email response.
   */
  public function updateAvailability(bool $available): void
  {
    $this->update([
      'status' => $available ? 'disponible' : 'indisponible',
      'last_response_at' => now(),
    ]);
  }
}
