<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property array|null $data
 * @property bool $is_read
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 */
final class Notification extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────────────

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Query Scopes ────────────────────────────────────────

    /**
     * Scope results to a specific user.
     *
     * @param Builder<Notification> $query
     * @return Builder<Notification>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope to unread notifications.
     *
     * @param Builder<Notification> $query
     * @return Builder<Notification>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to read notifications.
     *
     * @param Builder<Notification> $query
     * @return Builder<Notification>
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->where('is_read', true);
    }

    // ─── Methods ─────────────────────────────────────────────

    public function markAsRead(): bool
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAsUnread(): bool
    {
        return $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Mark all unread notifications for a user as read.
     */
    public static function markAllAsRead(User $user): int
    {
        return static::forUser($user)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Get the count of unread notifications for a user.
     */
    public static function getUnreadCount(User $user): int
    {
        return static::forUser($user)->unread()->count();
    }
}
