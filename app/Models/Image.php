<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Image extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Langues des brochures : la brochure est en français, ses versions en
     * anglais ou en italien (une version au plus par langue).
     */
    public const LANGUAGES = [
        'fr' => 'Français',
        'en' => 'English',
        'it' => 'Italiano',
    ];

    public const VERSION_LANGUAGES = ['en', 'it'];

    /** « version anglaise », « version italienne » */
    public const LANGUAGE_ADJECTIVES = [
        'fr' => 'française',
        'en' => 'anglaise',
        'it' => 'italienne',
    ];

    /** Classement repris de la brochure française par ses versions. */
    public const INHERITED_FIELDS = [
        'category_id',
        'sub_category_id',
        'author_id',
        'sector_id',
        'responsable_id',
    ];

    /** Auteurs dont les brochures sont réservées aux utilisateurs connectés. */
    public const RESTRICTED_AUTHOR_SLUGS = ['pro-verdon-tourisme', 'documentation-interne'];

    protected $fillable = [
        'name',
        'title',
        'language',
        'version_of_id',
        'extra_languages',
        'slug',
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
        'sub_category_id',
        'author_id',
        'sector_id',
        'responsable_id',
        'is_offline',
        'offline_reason',
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
        'is_offline' => 'boolean',
        'extra_languages' => 'array',
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
     * Relation avec la sous-catégorie
     */
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
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
     * Brochure française dont ce document est la version EN ou IT.
     */
    public function versionOf(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'version_of_id');
    }

    /**
     * Versions EN / IT de la brochure (anglais puis italien).
     */
    public function versions(): HasMany
    {
        return $this->hasMany(Image::class, 'version_of_id')
            ->orderByRaw("CASE language WHEN 'en' THEN 1 WHEN 'it' THEN 2 ELSE 3 END");
    }

    /**
     * Brochures (et non leurs versions).
     */
    public function scopeMain($query)
    {
        return $query->whereNull('version_of_id');
    }

    /**
     * Brochures lisibles dans la langue : une version dans cette langue, ou
     * un document français qui la contient aussi (carte bilingue FR/EN).
     */
    public function scopeAvailableIn($query, string $language, bool $hideOffline = false)
    {
        if (!in_array($language, self::VERSION_LANGUAGES, true)) {
            return $query;
        }

        return $query->where(function ($q) use ($language, $hideOffline) {
            $q->whereHas('versions', fn ($v) => $v->where('language', $language)
                ->when($hideOffline, fn ($v2) => $v2->where('is_offline', false)))
              ->orWhere('extra_languages', 'like', '%"' . $language . '"%');
        });
    }

    public function isVersion(): bool
    {
        return $this->version_of_id !== null;
    }

    /**
     * Brochure française de référence (elle-même si ce n'est pas une version).
     */
    public function mainBrochure(): Image
    {
        return $this->isVersion() && $this->versionOf ? $this->versionOf : $this;
    }

    /**
     * Langues lisibles dans ce document : la sienne et celles qu'il contient aussi.
     */
    public function documentLanguages(): array
    {
        return array_values(array_unique(array_merge(
            [$this->language ?: 'fr'],
            array_intersect((array) $this->extra_languages, array_keys(self::LANGUAGES))
        )));
    }

    /**
     * Document à ouvrir pour chaque langue disponible, dans l'ordre FR, EN, IT :
     * la brochure elle-même pour le français (et pour les langues qu'elle
     * contient aussi), sinon sa version dans la langue.
     *
     * @return array<string, Image>
     */
    public function editions(bool $includeOffline = false): array
    {
        $main = $this->mainBrochure();
        $editions = [];

        foreach (array_keys(self::LANGUAGES) as $language) {
            $version = $language === 'fr' ? null : $main->versions
                ->first(fn (Image $v) => $v->language === $language && ($includeOffline || !$v->is_offline));

            if ($version) {
                $editions[$language] = $version;
            } elseif (in_array($language, $main->documentLanguages(), true)) {
                $editions[$language] = $main;
            }
        }

        return $editions;
    }

    /**
     * Titre avec la langue pour une version (« Hôtels (English) ») : commandes,
     * e-mails, partout où la version est listée sans sa brochure.
     */
    public function titleWithLanguage(): string
    {
        $title = $this->title ?? $this->name;

        return $this->isVersion() && isset(self::LANGUAGES[$this->language])
            ? $title . ' (' . self::LANGUAGES[$this->language] . ')'
            : $title;
    }

    /**
     * Version de la brochure dans la langue (null si elle n'existe pas).
     */
    public function versionIn(string $language): ?Image
    {
        return $this->mainBrochure()->versions->firstWhere('language', $language);
    }

    /**
     * La version est plus ancienne que la brochure française.
     */
    public function isOutdatedVersion(): bool
    {
        $main = $this->isVersion() ? $this->versionOf : null;

        return $main
            && $this->edition_year
            && $main->edition_year
            && $this->edition_year < $main->edition_year;
    }

    /**
     * Adresse pour consulter le document : le fichier, sinon Calaméo, sinon le lien.
     */
    public function consultUrl(): string
    {
        if ($this->pdf_path) {
            return asset('storage/' . $this->pdf_path);
        }

        return $this->calameo_link_url ?: ($this->link_url ?: asset('storage/' . $this->path));
    }

    /**
     * Adresse de téléchargement : le fichier, sinon le lien, sinon l'image.
     */
    public function downloadUrl(): string
    {
        if ($this->pdf_path) {
            return asset('storage/' . $this->pdf_path);
        }

        return $this->link_url ?: asset('storage/' . $this->path);
    }

    /**
     * Adresse permanente de la brochure dans une langue (/brochure/{slug}/{langue}) :
     * elle mène toujours au fichier actuel, ou au français s'il n'y a pas de
     * version dans la langue.
     */
    public function permalink(?string $language = null): ?string
    {
        $slug = $this->mainBrochure()->slug;

        // Route absente (cache des routes d'avant le déploiement) : pas d'adresse
        // permanente plutôt qu'une erreur sur tout le catalogue.
        if (!$slug || !Route::has('brochure.permalink')) {
            return null;
        }

        $parameters = ['slug' => $slug];
        if ($language && $language !== 'fr' && isset(self::LANGUAGES[$language])) {
            $parameters['langue'] = $language;
        }

        return route('brochure.permalink', $parameters);
    }

    /**
     * Slug unique pour l'adresse permanente.
     */
    public static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($base) ?: 'brochure', 180, '');
        $slug = $base;
        $n = 2;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    /**
     * Supprimer le fichier physique quand le model est supprimé
     */
    protected static function booted(): void
    {
        static::saving(function (Image $image) {
            if (!in_array($image->language, array_keys(self::LANGUAGES), true)) {
                $image->language = 'fr';
            }

            if ($image->version_of_id) {
                // Une version reprend le classement de sa brochure et n'a pas d'adresse propre.
                $main = $image->versionOf()->first();
                if ($main) {
                    foreach (self::INHERITED_FIELDS as $field) {
                        $image->{$field} = $main->{$field};
                    }
                }
                $image->slug = null;
                $image->display_order = null;
                $image->extra_languages = null;
            } else {
                $image->language = 'fr';
                $extra = array_values(array_intersect((array) $image->extra_languages, self::VERSION_LANGUAGES));
                $image->extra_languages = $extra ?: null;

                if (empty($image->slug)) {
                    $base = $image->pdf_path
                        ? pathinfo($image->pdf_path, PATHINFO_FILENAME)
                        : ($image->title ?: pathinfo((string) $image->name, PATHINFO_FILENAME));
                    $image->slug = static::uniqueSlug((string) $base, $image->id);
                }
            }
        });

        // Le classement de la brochure suit dans ses versions.
        static::saved(function (Image $image) {
            if ($image->version_of_id || !$image->wasChanged(self::INHERITED_FIELDS)) {
                return;
            }

            static::where('version_of_id', $image->id)
                ->update($image->only(self::INHERITED_FIELDS));
        });

        static::deleting(function (Image $image) {
            // Les versions partent avec la brochure.
            if (!$image->version_of_id) {
                static::where('version_of_id', $image->id)->get()
                    ->each(fn (Image $version) => $image->isForceDeleting() ? $version->forceDelete() : $version->delete());
            }

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
