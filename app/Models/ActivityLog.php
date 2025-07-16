<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'event_type',
        'action',
        'entity_type',
        'entity_id',
        'data',
        'ip_address',
        'user_agent',
        'user_id',
        'status',
        'message',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'success' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200',
            'error' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200',
            'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200',
            'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200'
        };
    }

    /**
     * Get the event type icon.
     */
    public function getEventIconAttribute(): string
    {
        return match($this->event_type) {
            'status_change' => '🔄',
            'data_sync' => '🔄',
            'api_call' => '🌐',
            'error' => '❌',
            'system' => '⚙️',
            default => '📝'
        };
    }

    /**
     * Get a formatted description of the activity.
     */
    public function getDescriptionAttribute(): string
    {
        if ($this->message) {
            return $this->message;
        }

        return match($this->event_type) {
            'status_change' => "Statut changé: {$this->action}",
            'data_sync' => "Synchronisation: {$this->action}",
            'api_call' => "Appel API: {$this->action}",
            'error' => "Erreur: {$this->action}",
            default => $this->action
        };
    }

    /**
     * Static method to log an activity.
     */
    public static function logActivity(
        string $eventType,
        string $action,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $data = null,
        ?string $message = null,
        string $status = 'success',
        ?int $userId = null
    ): self {
        return self::create([
            'event_type' => $eventType,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'data' => $data,
            'message' => $message,
            'status' => $status,
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
