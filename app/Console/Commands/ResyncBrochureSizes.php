<?php

namespace App\Console\Commands;

use App\Models\Image;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ResyncBrochureSizes extends Command
{
    protected $signature = 'brochures:resync-sizes {--dry-run : N\'écrit rien, affiche seulement les écarts détectés}';

    protected $description = 'Resynchronise la colonne Image::size avec la taille réelle des fichiers (PDF + images de contenu)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $images = Image::whereNotNull('pdf_path')->orderBy('id')->get();

        $this->info(sprintf('Found %d images with a content file. Dry-run=%s', $images->count(), $dryRun ? 'YES' : 'NO'));

        $updated = 0;
        $missing = 0;
        $alreadyOk = 0;
        $rows = [];

        foreach ($images as $img) {
            $abs = Storage::disk('public')->path($img->pdf_path);

            if (!is_file($abs)) {
                $missing++;
                $rows[] = [$img->id, $img->pdf_path, $img->size ?? 0, 'MISSING', '-'];
                continue;
            }

            $live = filesize($abs);
            if ((int) $img->size === $live) {
                $alreadyOk++;
                continue;
            }

            if (!$dryRun) {
                $img->update(['size' => $live]);
            }

            $rows[] = [
                $img->id,
                $img->pdf_path,
                number_format(($img->size ?? 0) / 1024, 1) . ' KB',
                number_format($live / 1024, 1) . ' KB',
                $dryRun ? 'WOULD UPDATE' : 'UPDATED',
            ];
            $updated++;
        }

        if (!empty($rows)) {
            $this->table(['ID', 'Path', 'DB size', 'Live size', 'Action'], $rows);
        }

        $this->newLine();
        $this->info(sprintf('Already in sync : %d', $alreadyOk));
        $this->info(sprintf('%s: %d', $dryRun ? 'Would update' : 'Updated', $updated));
        if ($missing > 0) {
            $this->warn(sprintf('Missing files: %d (pdf_path points to a file that no longer exists on disk)', $missing));
        }

        return self::SUCCESS;
    }
}
