<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class SftpConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'password',
        'private_key',
        'remote_path',
        'timeout',
        'is_active',
    ];

    protected $casts = [
        'port' => 'integer',
        'timeout' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'password',
        'private_key',
    ];

    /**
     * Encrypt password before saving
     */
    public function setPasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt password when accessing
     */
    public function getPasswordAttribute($value): ?string
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Encrypt private key before saving
     */
    public function setPrivateKeyAttribute($value): void
    {
        if ($value) {
            $this->attributes['private_key'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt private key when accessing
     */
    public function getPrivateKeyAttribute($value): ?string
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Get all uploads for this configuration
     */
    public function uploads(): HasMany
    {
        return $this->hasMany(SftpUpload::class);
    }

    /**
     * Get the active configuration
     */
    public static function getActive(): ?self
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Test the SFTP connection
     */
    public function testConnection(): bool
    {
        try {
            $sftp = app(\App\Services\SftpService::class);
            return $sftp->testConnection($this);
        } catch (\Exception $e) {
            return false;
        }
    }
}
