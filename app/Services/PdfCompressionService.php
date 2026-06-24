<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class PdfCompressionService
{
    public function compress(string $sourceAbsolutePath): array
    {
        $config = config('brochures.pdf_compression');
        $start = microtime(true);
        $sizeBefore = file_exists($sourceAbsolutePath) ? filesize($sourceAbsolutePath) : 0;

        $result = [
            'compressed' => false,
            'size_before' => $sizeBefore,
            'size_after' => $sizeBefore,
            'duration_ms' => 0,
            'reason' => null,
        ];

        if ($sizeBefore === 0) {
            $result['reason'] = 'source_missing';
            return $result;
        }

        // Tmp file in the same directory as the source: same filesystem (atomic rename),
        // same permission scope (only one writable user to think about), no separate
        // tmp dir to provision. The leading dot keeps it hidden from default ls.
        $tmpPath = dirname($sourceAbsolutePath) . '/.pdf-compress-' . Str::uuid() . '.pdf';

        try {
            $process = new Process([
                $config['ghostscript'],
                '-sDEVICE=pdfwrite',
                '-dPDFSETTINGS=' . $config['preset'],
                '-dCompatibilityLevel=1.5',
                '-dNOPAUSE',
                '-dBATCH',
                '-dQUIET',
                '-dSAFER',
                '-dEmbedAllFonts=true',
                '-dSubsetFonts=true',
                '-dCompressFonts=true',
                '-dDetectDuplicateImages=true',
                '-dColorImageDownsampleType=/Bicubic',
                '-dGrayImageDownsampleType=/Bicubic',
                '-dMonoImageDownsampleType=/Subsample',
                '-dColorImageResolution=150',
                '-dGrayImageResolution=150',
                '-dMonoImageResolution=300',
                '-dAutoFilterColorImages=true',
                '-dAutoFilterGrayImages=true',
                '-dColorConversionStrategy=/LeaveColorUnchanged',
                '-dPreserveAnnots=true',
                '-dPreserveMarkedContent=true',
                '-sOutputFile=' . $tmpPath,
                $sourceAbsolutePath,
            ]);

            $process->setTimeout($config['process_timeout']);
            $process->run();

            if (!$process->isSuccessful()) {
                $result['reason'] = 'gs_failed: ' . trim($process->getErrorOutput() ?: $process->getOutput());
                $this->cleanup($tmpPath);
                $result['duration_ms'] = (int) round((microtime(true) - $start) * 1000);
                return $result;
            }

            if (!$this->isValidPdf($tmpPath)) {
                $result['reason'] = 'invalid_output_pdf';
                $this->cleanup($tmpPath);
                $result['duration_ms'] = (int) round((microtime(true) - $start) * 1000);
                return $result;
            }

            $sizeAfter = filesize($tmpPath);
            $minSavingsBytes = ((int) ($config['min_savings_kb'] ?? 50)) * 1024;

            if ($sizeAfter >= $sizeBefore - $minSavingsBytes) {
                $result['size_after'] = $sizeAfter;
                $result['reason'] = 'no_significant_gain';
                $this->cleanup($tmpPath);
                $result['duration_ms'] = (int) round((microtime(true) - $start) * 1000);
                return $result;
            }

            if (!@rename($tmpPath, $sourceAbsolutePath)) {
                $result['reason'] = 'rename_failed';
                $this->cleanup($tmpPath);
                $result['duration_ms'] = (int) round((microtime(true) - $start) * 1000);
                return $result;
            }

            $result['compressed'] = true;
            $result['size_after'] = $sizeAfter;
        } catch (ProcessTimedOutException $e) {
            $result['reason'] = 'timeout';
            $this->cleanup($tmpPath);
        } catch (\Throwable $e) {
            $result['reason'] = 'exception: ' . $e->getMessage();
            $this->cleanup($tmpPath);
        }

        $result['duration_ms'] = (int) round((microtime(true) - $start) * 1000);
        return $result;
    }

    private function isValidPdf(string $path): bool
    {
        if (!is_file($path) || filesize($path) < 100) {
            return false;
        }

        $fp = @fopen($path, 'rb');
        if (!$fp) {
            return false;
        }

        try {
            $header = fread($fp, 5);
            if ($header !== '%PDF-') {
                return false;
            }
            fseek($fp, -1024, SEEK_END);
            $tail = fread($fp, 1024);
            return strpos($tail, '%%EOF') !== false;
        } finally {
            fclose($fp);
        }
    }

    private function cleanup(string $path): void
    {
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}
