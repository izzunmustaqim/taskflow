<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property bool $email_due_soon
 * @property bool $email_overdue
 * @property bool $email_reminders
 * @property bool $in_app_due_soon
 * @property bool $in_app_overdue
 * @property bool $in_app_reminders
 * @property int $reminder_hours_before
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 */
final class NotificationPreference extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'email_due_soon',
        'email_overdue',
        'email_reminders',
        'in_app_due_soon',
        'in_app_overdue',
        'in_app_reminders',
        'reminder_hours_before',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'email_due_soon' => 'boolean',
        'email_overdue' => 'boolean',
        'email_reminders' => 'boolean',
        'in_app_due_soon' => 'boolean',
        'in_app_overdue' => 'boolean',
        'in_app_reminders' => 'boolean',
        'reminder_hours_before' => 'integer',
    ];

    // ─── Relationships ───────────────────────────────────────

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Static Methods ──────────────────────────────────────

    /**
     * Get or create preferences for a user.
     */
    public static function getOrCreateForUser(User $user): self
    {
        return static::firstOrCreate(
            ['user_id' => $user->id],
            [
                'email_due_soon' => true,
                'email_overdue' => true,
                'email_reminders' => true,
                'in_app_due_soon' => true,
                'in_app_overdue' => true,
                'in_app_reminders' => true,
                'reminder_hours_before' => 24,
            ]
        );
    }
}
