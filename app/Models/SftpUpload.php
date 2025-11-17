<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SftpUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sftp_configuration_id',
        'original_filename',
        'remote_filename',
        'remote_path',
        'file_size',
        'status',
        'error_message',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * Get the user who uploaded the file
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the SFTP configuration used
     */
    public function sftpConfiguration(): BelongsTo
    {
        return $this->belongsTo(SftpConfiguration::class);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $size = $this->file_size;
        $units = ['o', 'Ko', 'Mo', 'Go'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Check if upload was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if upload failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
