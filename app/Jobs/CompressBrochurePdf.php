<?php

namespace App\Jobs;

use App\Models\Image;
use App\Services\PdfCompressionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompressBrochurePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 180;
    public int $backoff = 30;

    public function __construct(
        public int $imageId,
        public string $pdfPath
    ) {}

    public function handle(PdfCompressionService $compressor): void
    {
        $image = Image::find($this->imageId);

        if (!$image || $image->pdf_path !== $this->pdfPath) {
            Log::info('CompressBrochurePdf skipped (stale)', [
                'image_id' => $this->imageId,
                'expected_pdf_path' => $this->pdfPath,
                'current_pdf_path' => $image?->pdf_path,
            ]);
            return;
        }

        if (!Storage::disk('public')->exists($this->pdfPath)) {
            Log::info('CompressBrochurePdf skipped (file missing)', [
                'image_id' => $this->imageId,
                'pdf_path' => $this->pdfPath,
            ]);
            return;
        }

        $absolutePath = Storage::disk('public')->path($this->pdfPath);
        $result = $compressor->compress($absolutePath);

        $context = [
            'image_id' => $this->imageId,
            'pdf_path' => $this->pdfPath,
            'compressed' => $result['compressed'],
            'size_before_kb' => round($result['size_before'] / 1024, 2),
            'size_after_kb' => round($result['size_after'] / 1024, 2),
            'savings_pct' => $result['size_before'] > 0
                ? round((1 - $result['size_after'] / $result['size_before']) * 100, 1)
                : 0,
            'duration_ms' => $result['duration_ms'],
            'reason' => $result['reason'],
        ];

        if ($result['compressed']) {
            Log::info('Brochure PDF compressed', $context);
        } else {
            Log::warning('Brochure PDF not compressed', $context);
        }

        // Always sync DB size with the actual on-disk file: covers post-compression,
        // post-rejection (no significant gain) and post-replace-without-compression.
        $realSize = @filesize($absolutePath);
        if ($realSize !== false && (int) $image->size !== $realSize) {
            $image->update(['size' => $realSize]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('CompressBrochurePdf failed', [
            'image_id' => $this->imageId,
            'pdf_path' => $this->pdfPath,
            'exception' => $e->getMessage(),
        ]);
    }
}
