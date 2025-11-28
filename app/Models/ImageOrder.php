<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImageOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_type',
        'language',
        'company',
        'civility',
        'last_name',
        'first_name',
        'address_line1',
        'address_line2',
        'postal_code',
        'city',
        'country',
        'email',
        'phone_country_code',
        'phone_number',
        'status',
        'customer_notes',
        'admin_notes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec les items de commande
     */
    public function items(): HasMany
    {
        return $this->hasMany(ImageOrderItem::class, 'image_order_id');
    }

    /**
     * Générer un numéro de commande unique
     */
    public static function generateOrderNumber(): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;

        return 'CMD-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Obtenir le nom complet du client
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Obtenir l'adresse complète
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->address_line1;

        if ($this->address_line2) {
            $address .= ', ' . $this->address_line2;
        }

        $address .= ', ' . $this->postal_code . ' ' . $this->city;
        $address .= ', ' . $this->country;

        return $address;
    }

    /**
     * Obtenir le téléphone complet
     */
    public function getFullPhoneAttribute(): ?string
    {
        if (!$this->phone_number) {
            return null;
        }

        return ($this->phone_country_code ? $this->phone_country_code . ' ' : '') . $this->phone_number;
    }

    /**
     * Scope pour filtrer par statut
     */
    public function scopeByStatus($query, $status)
    {
        if ($status && $status !== 'all') {
            return $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Scope pour rechercher
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%')
                  ->orWhere('first_name', 'like', '%' . $search . '%')
                  ->orWhere('last_name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('company', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }

    /**
     * Obtenir le label du statut en français
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'processing' => 'En cours',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée',
            default => $this->status,
        };
    }

    /**
     * Obtenir la couleur du statut pour l'affichage
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200',
            'processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200',
            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200',
            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-200',
        };
    }
}
