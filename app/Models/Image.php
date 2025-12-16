<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'title',
        'filename',
        'path',
        'url',
        'alt_text',
        'description',
        'link_url',
        'link_text',
        'calameo_link_url',
        'calameo_link_text',
        'mime_type',
        'size',
        'width',
        'height',
        'thumbnail_path',
        'pdf_path',
        'uploaded_by',
        'quantity_available',
        'max_order_quantity',
        'print_available',
        'edition_year',
        'display_order',
        'category_id',
        'author_id',
        'sector_id',
        'responsable_id',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'quantity_available' => 'integer',
        'max_order_quantity' => 'integer',
        'print_available' => 'boolean',
        'edition_year' => 'integer',
        'display_order' => 'integer',
    ];

    /**
     * Relation avec l'utilisateur qui a uploadé l'image
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Relation avec la catégorie
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relation avec l'auteur
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /**
     * Relation avec le secteur
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * Relation avec le responsable (utilisateur approuvé)
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Relation avec les signalements
     */
    public function reports(): HasMany
    {
        return $this->hasMany(BrochureReport::class);
    }

    /**
     * Relation avec les clics
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(BrochureClick::class);
    }

    /**
     * Supprimer le fichier physique quand le model est supprimé
     */
    protected static function booted(): void
    {
        static::deleting(function (Image $image) {
            // Supprimer l'image principale
            if (Storage::disk('public')->exists($image->path)) {
                Storage::disk('public')->delete($image->path);
            }

            // Supprimer le thumbnail si il existe
            if ($image->thumbnail_path && Storage::disk('public')->exists($image->thumbnail_path)) {
                Storage::disk('public')->delete($image->thumbnail_path);
            }

            // Supprimer le PDF si il existe
            if ($image->pdf_path && Storage::disk('public')->exists($image->pdf_path)) {
                Storage::disk('public')->delete($image->pdf_path);
            }
        });
    }

    /**
     * Formater la taille du fichier
     */
    public function formattedSize(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
