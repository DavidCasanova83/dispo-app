<?php

namespace App\Console\Commands;

use App\Models\Image;
use App\Services\PdfCompressionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CompressExistingBrochures extends Command
{
    protected $signature = 'brochures:compress-existing
                            {--dry-run : Affiche les fichiers candidats sans rien compresser}
                            {--min-size-kb=500 : Ignore les fichiers plus petits que cette taille}
                            {--limit=0 : Limite le nombre de fichiers traités (0 = tous)}';

    protected $description = 'Compresse en masse tous les PDF de brochures déjà uploadés (Ghostscript)';

    public function handle(PdfCompressionService $compressor): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $minSizeBytes = ((int) $this->option('min-size-kb')) * 1024;
        $limit = (int) $this->option('limit');

        $query = Image::whereNotNull('pdf_path')
            ->where('pdf_path', 'like', '%.pdf')
            ->orderBy('id');

        $images = $query->get();

        $this->info(sprintf(
            'Found %d PDF brochures. Threshold=%d KB. Dry-run=%s. Limit=%s',
            $images->count(),
            (int) $this->option('min-size-kb'),
            $dryRun ? 'YES' : 'NO',
            $limit > 0 ? $limit : 'none'
        ));

        $stats = ['processed' => 0, 'compressed' => 0, 'skipped_small' => 0, 'skipped_no_gain' => 0, 'missing' => 0, 'failed' => 0];
        $totalBefore = 0;
        $totalAfter = 0;
        $rows = [];

        foreach ($images as $img) {
            if ($limit > 0 && $stats['processed'] >= $limit) {
                break;
            }

            $abs = Storage::disk('public')->path($img->pdf_path);

            if (!is_file($abs)) {
                $stats['missing']++;
                continue;
            }

            $liveSize = filesize($abs);
            if ($liveSize < $minSizeBytes) {
                $stats['skipped_small']++;
                continue;
            }

            $stats['processed']++;
            $totalBefore += $liveSize;

            if ($dryRun) {
                $rows[] = [$img->id, basename($img->pdf_path), number_format($liveSize / 1024, 1) . ' KB', 'WOULD COMPRESS'];
                $totalAfter += $liveSize; // unchanged in dry-run
                continue;
            }

            $result = $compressor->compress($abs);

            if ($result['compressed']) {
                $img->update(['size' => $result['size_after']]);
                $stats['compressed']++;
                $totalAfter += $result['size_after'];
                $rows[] = [
                    $img->id,
                    basename($img->pdf_path),
                    number_format($result['size_before'] / 1024, 1) . ' KB',
                    number_format($result['size_after'] / 1024, 1) . ' KB ('
                        . sprintf('%+.1f%%', -(1 - $result['size_after'] / $result['size_before']) * 100) . ')',
                ];
            } else {
                $totalAfter += $result['size_after']; // unchanged
                if ($result['reason'] === 'no_significant_gain') {
                    $stats['skipped_no_gain']++;
                } else {
                    $stats['failed']++;
                    $this->warn(sprintf('FAIL #%d (%s): %s', $img->id, $img->pdf_path, $result['reason']));
                }
            }
        }

        if ($dryRun) {
            $this->table(['ID', 'File', 'Live size', 'Action'], $rows);
        } elseif (!empty($rows)) {
            $this->table(['ID', 'File', 'Before', 'After'], $rows);
        }

        $this->newLine();
        $this->info(sprintf('Processed:       %d', $stats['processed']));
        $this->info(sprintf('Compressed:      %d', $stats['compressed']));
        $this->info(sprintf('Skipped (small): %d', $stats['skipped_small']));
        $this->info(sprintf('Skipped (no gain): %d', $stats['skipped_no_gain']));
        if ($stats['missing'] > 0) {
            $this->warn(sprintf('Missing files:   %d', $stats['missing']));
        }
        if ($stats['failed'] > 0) {
            $this->error(sprintf('Failed:          %d', $stats['failed']));
        }
        if ($totalBefore > 0) {
            $this->info(sprintf(
                'Total: %.1f MB → %.1f MB (saved %.1f MB, %+.1f%%)',
                $totalBefore / 1024 / 1024,
                $totalAfter / 1024 / 1024,
                ($totalBefore - $totalAfter) / 1024 / 1024,
                -(1 - $totalAfter / $totalBefore) * 100
            ));
        }

        return self::SUCCESS;
    }
}
