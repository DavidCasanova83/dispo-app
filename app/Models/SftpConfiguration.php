<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class SftpConfiguration extends Model
{
    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'password',
        'private_key',
        'remote_path',
        'active',
        'last_test_at',
        'created_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_test_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'private_key',
    ];

    /**
     * Encrypt password when setting
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Encrypt private key when setting
     */
    protected function privateKey(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * User who created this configuration
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Uploads using this configuration
     */
    public function uploads(): HasMany
    {
        return $this->hasMany(SftpUpload::class);
    }

    /**
     * Get the masked password for display
     */
    public function getMaskedPasswordAttribute(): string
    {
        return $this->password ? '••••••••' : '';
    }

    /**
     * Check if configuration has credentials
     */
    public function hasCredentials(): bool
    {
        return !empty($this->password) || !empty($this->private_key);
    }
}
