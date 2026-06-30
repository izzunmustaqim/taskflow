<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_log';

    protected $fillable = [
        'user_id',
        'task_id',
        'type',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the description of the activity based on its type.
     */
    public function getDescriptionAttribute(): string
    {
        return match ($this->type) {
            'created' => 'Task was created',
            'updated' => 'Task was updated',
            'status_changed' => 'Status was changed',
            'deleted' => 'Task was moved to trash',
            'restored' => 'Task was restored from trash',
            'force_deleted' => 'Task was permanently deleted',
            default => 'Activity recorded',
        };
    }

    /**
     * Get formatted properties for display.
     */
    public function getFormattedPropertiesAttribute(): array
    {
        $formatted = [];
        $properties = $this->properties ?? [];

        if ($this->type === 'status_changed' && isset($properties['old_status'], $properties['new_status'])) {
            $formatted[] = [
                'label' => 'Status',
                'old' => ucfirst(str_replace('_', ' ', $properties['old_status'])),
                'new' => ucfirst(str_replace('_', ' ', $properties['new_status'])),
            ];
        }

        if ($this->type === 'updated' && isset($properties['changes'])) {
            foreach ($properties['changes'] as $field => $change) {
                $label = ucfirst(str_replace('_', ' ', $field));
                $formatted[] = [
                    'label' => $label,
                    'old' => $change['old'] ?? null,
                    'new' => $change['new'] ?? null,
                ];
            }
        }

        return $formatted;
    }
}
