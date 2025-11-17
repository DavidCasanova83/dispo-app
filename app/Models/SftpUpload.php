<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SftpUpload extends Model
{
    protected $fillable = [
        'sftp_configuration_id',
        'user_id',
        'original_filename',
        'stored_filename',
        'local_path',
        'remote_path',
        'file_size',
        'status',
        'error_message',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * SFTP configuration used for this upload
     */
    public function configuration(): BelongsTo
    {
        return $this->belongsTo(SftpConfiguration::class, 'sftp_configuration_id');
    }

    /**
     * User who uploaded the file
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if upload is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if upload is uploading
     */
    public function isUploading(): bool
    {
        return $this->status === 'uploading';
    }

    /**
     * Check if upload is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if upload has failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Get status badge color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'failed' => 'danger',
            'uploading' => 'warning',
            default => 'secondary',
        };
    }
}
