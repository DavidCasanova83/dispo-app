<?php

namespace App\Jobs;

use App\Models\SftpUpload;
use App\Services\SftpService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessSftpUpload implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SftpUpload $upload
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(SftpService $sftpService): void
    {
        try {
            // Update status to uploading
            $this->upload->update(['status' => 'uploading']);

            Log::info('Starting SFTP upload', [
                'upload_id' => $this->upload->id,
                'filename' => $this->upload->stored_filename,
            ]);

            // Validate that local file exists
            if (!file_exists($this->upload->local_path)) {
                throw new \RuntimeException('Local file not found: ' . $this->upload->local_path);
            }

            // Set SFTP configuration
            $sftpService->setConfiguration($this->upload->configuration);

            // Upload file
            $result = $sftpService->uploadFile(
                $this->upload->local_path,
                $this->upload->stored_filename
            );

            if (!$result['success']) {
                throw new \RuntimeException($result['message']);
            }

            // Update upload record as completed
            $this->upload->update([
                'status' => 'completed',
                'remote_path' => $result['remote_path'],
                'uploaded_at' => now(),
                'error_message' => null,
            ]);

            // Clean up local file
            if (file_exists($this->upload->local_path)) {
                unlink($this->upload->local_path);
            }

            Log::info('SFTP upload completed successfully', [
                'upload_id' => $this->upload->id,
                'remote_path' => $result['remote_path'],
            ]);

        } catch (\Exception $e) {
            Log::error('SFTP upload failed', [
                'upload_id' => $this->upload->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Update upload record as failed
            $this->upload->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // Re-throw to trigger retry if attempts remain
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SFTP upload job failed permanently', [
            'upload_id' => $this->upload->id,
            'error' => $exception->getMessage(),
        ]);

        // Ensure status is marked as failed
        $this->upload->update([
            'status' => 'failed',
            'error_message' => 'Job failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
        ]);

        // Clean up local file if it still exists
        if (file_exists($this->upload->local_path)) {
            unlink($this->upload->local_path);
        }
    }
}
