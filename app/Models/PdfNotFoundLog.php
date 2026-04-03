<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfNotFoundLog extends Model
{
    protected $fillable = [
        'url',
        'referer',
        'ip_address',
        'user_agent',
        'user_id',
        'hit_count',
        'last_hit_at',
    ];

    protected $casts = [
        'last_hit_at' => 'datetime',
        'hit_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Enregistre ou incrémente un 404 PDF
     */
    public static function logNotFound(string $url, ?string $referer, ?string $ip, ?string $userAgent, ?int $userId): void
    {
        $existing = self::where('url', $url)
            ->where('referer', $referer)
            ->first();

        if ($existing) {
            $existing->increment('hit_count');
            $existing->update(['last_hit_at' => now()]);
        } else {
            self::create([
                'url' => $url,
                'referer' => $referer,
                'ip_address' => $ip,
                'user_agent' => substr($userAgent ?? '', 0, 500),
                'user_id' => $userId,
                'hit_count' => 1,
                'last_hit_at' => now(),
            ]);
        }
    }
}
