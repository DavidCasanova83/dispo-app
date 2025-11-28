<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImageOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'image_order_id',
        'image_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Relation avec la commande
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(ImageOrder::class, 'image_order_id');
    }

    /**
     * Relation avec l'image
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }
}
