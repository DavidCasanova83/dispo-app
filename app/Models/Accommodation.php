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
   * Get the status options.
   */
  public static function getStatusOptions(): array
  {
    return [
      'pending' => 'En attente',
      'active' => 'Actif',
      'inactive' => 'Inactif',
    ];
  }

  /**
   * Scope a query to only include active accommodations.
   */
  public function scopeActive($query)
  {
    return $query->where('status', 'active');
  }

  /**
   * Scope a query to only include pending accommodations.
   */
  public function scopePending($query)
  {
    return $query->where('status', 'pending');
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
}
