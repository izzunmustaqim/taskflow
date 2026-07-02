<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LabelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $color
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read User $user
 * @property-read Collection<int, Task> $tasks
 */
final class Label extends Model
{
    /** @use HasFactory<LabelFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'color',
    ];

    // ─── Relationships ───────────────────────────────────────

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Task, $this>
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_label')->withTimestamps();
    }

    // ─── Query Scopes ────────────────────────────────────────

    /**
     * Scope results to a specific user.
     *
     * @param Builder<Label> $query
     * @return Builder<Label>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }
}
