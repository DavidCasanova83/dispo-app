<?php

namespace App\Services;

use App\Jobs\CompressBrochurePdf;
use App\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager as InterventionImageManager;
use InvalidArgumentException;

/**
 * Versions linguistiques (EN, IT) des brochures.
 *
 * Une version est un document de la table images rattaché à sa brochure
 * française (version_of_id) : elle a son fichier, sa couverture, son titre,
 * son lien Calaméo et son année d'édition, et reprend le classement de la
 * brochure (catégorie, secteur, auteur, responsable). Une version EN et une
 * version IT au plus par brochure ; pas de version sans brochure française.
 */
class BrochureVersionService
{
    private const CONTENT_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

    private const CONTENT_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];

    private const COVER_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /**
     * Ajoute la version d'une brochure dans une langue.
     *
     * @param  array{title?: ?string, edition_year?: ?int, description?: ?string, calameo_link_url?: ?string, calameo_link_text?: ?string}  $data
     */
    public function create(
        Image $main,
        string $language,
        array $data,
        ?UploadedFile $content,
        ?UploadedFile $cover,
        ?int $userId,
    ): Image {
        $this->assertCanReceive($main, $language);

        if (! $content && empty($data['calameo_link_url'])) {
            throw new InvalidArgumentException('Ajoutez le fichier de la version (PDF ou image) ou son lien Calaméo.');
        }

        $base = Str::limit($main->slug ?: (Str::slug($main->title ?: 'brochure') ?: 'brochure'), 150, '').'-'.$language;

        // Fichier téléchargeable
        $pdfPath = null;
        $size = null;
        $name = null;
        $contentIsImage = false;
        if ($content) {
            $extension = strtolower($content->getClientOriginalExtension());
            if (! in_array($content->getMimeType(), self::CONTENT_MIME_TYPES, true)
                || ! in_array($extension, self::CONTENT_EXTENSIONS, true)) {
                throw new InvalidArgumentException('Le fichier doit être un PDF, un JPG ou un PNG.');
            }
            $pdfPath = $content->storeAs('pdfs', $this->uniqueName('pdfs', $base, $extension), 'public');
            $size = $content->getSize();
            $name = $content->getClientOriginalName();
            $contentIsImage = $extension !== 'pdf';
        }

        // Couverture : celle fournie, sinon l'image elle-même, sinon celle de la brochure.
        if ($cover) {
            if (! in_array($cover->getMimeType(), self::COVER_MIME_TYPES, true)) {
                $this->deleteFile($pdfPath);
                throw new InvalidArgumentException('La couverture doit être une image (JPG, PNG, GIF ou WebP).');
            }
            $extension = strtolower($cover->getClientOriginalExtension()) ?: 'jpg';
            $coverPath = $cover->storeAs('images', $this->uniqueName('images', $base, $extension), 'public');
            $coverData = $this->processCover($coverPath);
            $coverData['mime_type'] = $cover->getMimeType();
        } elseif ($contentIsImage) {
            $extension = strtolower(pathinfo($pdfPath, PATHINFO_EXTENSION));
            $coverPath = 'images/'.$this->uniqueName('images', $base, $extension);
            Storage::disk('public')->copy($pdfPath, $coverPath);
            $coverData = $this->processCover($coverPath);
            $coverData['mime_type'] = $content->getMimeType();
        } else {
            $coverData = $this->copyMainCover($main, $base);
            if (! $coverData) {
                $this->deleteFile($pdfPath);
                throw new InvalidArgumentException('La couverture de la brochure française est introuvable : ajoutez une couverture à la version.');
            }
        }

        $version = Image::create([
            'name' => $name ?: (Image::LANGUAGES[$language].' - '.($main->title ?: $main->name)),
            'title' => ($data['title'] ?? null) ?: $main->title,
            'language' => $language,
            'version_of_id' => $main->id,
            'filename' => basename($coverData['path']),
            'path' => $coverData['path'],
            'url' => $coverData['path'],
            'thumbnail_path' => $coverData['thumbnail_path'],
            'pdf_path' => $pdfPath,
            'mime_type' => $coverData['mime_type'] ?? null,
            'size' => $size,
            'width' => $coverData['width'] ?? null,
            'height' => $coverData['height'] ?? null,
            'alt_text' => ($data['title'] ?? null) ?: $main->alt_text,
            'description' => ($data['description'] ?? null) ?: null,
            'calameo_link_url' => ($data['calameo_link_url'] ?? null) ?: null,
            'calameo_link_text' => ($data['calameo_link_text'] ?? null) ?: null,
            'edition_year' => ($data['edition_year'] ?? null) ?: $main->edition_year,
            'uploaded_by' => $userId ?? $main->uploaded_by,
            'print_available' => false,
        ]);

        if ($pdfPath && ! $contentIsImage && config('brochures.pdf_compression.enabled')) {
            CompressBrochurePdf::dispatchAfterResponse($version->id, $pdfPath);
        }

        return $version;
    }

    /**
     * Rattache une brochure existante comme version d'une autre (reprise des
     * anciennes brochures « English - … »). Ses fichiers et ses clics sont gardés.
     */
    public function attach(Image $main, Image $document, string $language): Image
    {
        $this->assertCanReceive($main, $language);

        if ($document->is($main)) {
            throw new InvalidArgumentException('Une brochure ne peut pas être sa propre version.');
        }
        if ($document->isVersion()) {
            throw new InvalidArgumentException('Ce document est déjà la version d\'une autre brochure.');
        }
        if ($document->versions()->exists()) {
            throw new InvalidArgumentException('Ce document a lui-même des versions : il ne peut pas devenir une version.');
        }

        $document->update([
            'version_of_id' => $main->id,
            'language' => $language,
        ]);

        return $document->refresh();
    }

    /**
     * Supprime une version (corbeille ; ses fichiers sont effacés comme pour une brochure).
     */
    public function remove(Image $version): void
    {
        if (! $version->isVersion()) {
            throw new InvalidArgumentException('Ce document n\'est pas une version linguistique.');
        }

        $version->delete();
    }

    /**
     * Brochures pouvant devenir une version (pas de version ni de versions
     * elles-mêmes), hors la brochure en cours.
     */
    public function candidates(Image $main)
    {
        return Image::main()
            ->whereKeyNot($main->id)
            ->whereDoesntHave('versions')
            ->orderBy('title')
            ->get(['id', 'title', 'name', 'edition_year']);
    }

    private function assertCanReceive(Image $main, string $language): void
    {
        if ($main->isVersion()) {
            throw new InvalidArgumentException('Une version ne peut pas avoir de versions : ouvrez la brochure française.');
        }
        if (! in_array($language, Image::VERSION_LANGUAGES, true)) {
            throw new InvalidArgumentException('Langue non prise en charge (anglais ou italien seulement).');
        }
        if ($main->versions()->where('language', $language)->exists()) {
            throw new InvalidArgumentException('Cette brochure a déjà une version '.Image::LANGUAGE_ADJECTIVES[$language].'.');
        }
    }

    /**
     * Copie la couverture de la brochure (jamais partagée : la suppression
     * d'un document efface ses fichiers).
     */
    private function copyMainCover(Image $main, string $base): ?array
    {
        $disk = Storage::disk('public');
        if (! $main->path || ! $disk->exists($main->path)) {
            return null;
        }

        $extension = strtolower(pathinfo($main->path, PATHINFO_EXTENSION)) ?: 'jpg';
        $path = 'images/'.$this->uniqueName('images', $base, $extension);
        $disk->copy($main->path, $path);

        $thumbnailPath = null;
        if ($main->thumbnail_path && $disk->exists($main->thumbnail_path)) {
            $thumbnailPath = 'images/thumbnails/thumb_'.basename($path);
            $disk->copy($main->thumbnail_path, $thumbnailPath);
        }

        return [
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'width' => $main->width,
            'height' => $main->height,
            'mime_type' => $main->mime_type,
        ];
    }

    /**
     * Couverture et vignette 300 × 300, comme à l'ajout d'une brochure.
     */
    private function processCover(string $path): array
    {
        $disk = Storage::disk('public');
        $fullPath = $disk->path($path);

        $manager = new InterventionImageManager(new Driver);
        $image = $manager->read($fullPath);
        $width = $image->width();
        $height = $image->height();
        $image->save($fullPath, quality: 85);

        $thumbnailPath = 'images/thumbnails/thumb_'.basename($path);
        $thumbnailFullPath = $disk->path($thumbnailPath);
        if (! is_dir(dirname($thumbnailFullPath))) {
            mkdir(dirname($thumbnailFullPath), 0755, true);
        }
        $manager->read($fullPath)->cover(300, 300)->save($thumbnailFullPath, quality: 80);

        return [
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'width' => $width,
            'height' => $height,
        ];
    }

    private function uniqueName(string $directory, string $base, string $extension): string
    {
        $name = $base.'.'.$extension;
        $n = 1;
        while (Storage::disk('public')->exists($directory.'/'.$name)) {
            $name = $base.'-'.$n++.'.'.$extension;
        }

        return $name;
    }

    private function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
