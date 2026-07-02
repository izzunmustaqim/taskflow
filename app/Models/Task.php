<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RecurrenceType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $category_id
 * @property string $title
 * @property string|null $description
 * @property TaskStatus $status
 * @property TaskPriority $priority
 * @property \Illuminate\Support\Carbon|null $due_at
 * @property string|null $recurrence_type
 * @property int $recurrence_interval
 * @property \Illuminate\Support\Carbon|null $next_occurrence_at
 * @property int|null $parent_task_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User $user
 * @property-read Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Label> $labels
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Attachment> $attachments
 * @property-read Task|null $parentTask
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Task> $childTasks
 */
final class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'category_id',
        'due_at',
        'sort_order',
        'recurrence_type',
        'recurrence_interval',
        'next_occurrence_at',
        'parent_task_id',
    ];

    /**
     * @var list<string>
     */
    protected $with = ['category'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_at' => 'datetime',
            'recurrence_type' => RecurrenceType::class,
            'next_occurrence_at' => 'datetime',
            'recurrence_interval' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsToMany<Label, $this>
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'task_label')->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Attachment, $this>
     */
    public function attachments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Task, $this>
     */
    public function childTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    // ─── Query Scopes ────────────────────────────────────────

    /**
     * Scope results to a specific user.
     *
     * @param Builder<Task> $query
     * @return Builder<Task>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Filter tasks by status.
     *
     * @param Builder<Task> $query
     * @return Builder<Task>
     */
    public function scopeByStatus(Builder $query, TaskStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    /**
     * Filter tasks by priority.
     *
     * @param Builder<Task> $query
     * @return Builder<Task>
     */
    public function scopeByPriority(Builder $query, TaskPriority $priority): Builder
    {
        return $query->where('priority', $priority->value);
    }

    /**
     * Get tasks due within the next 48 hours that are not completed.
     *
     * @param Builder<Task> $query
     * @return Builder<Task>
     */
    public function scopeDueSoon(Builder $query): Builder
    {
        return $query
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->addHours(48))
            ->where('due_at', '>', now())
            ->where('status', '!=', TaskStatus::Completed->value);
    }

    /**
     * Get tasks that are past their due date and not completed.
     *
     * @param Builder<Task> $query
     * @return Builder<Task>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->where('status', '!=', TaskStatus::Completed->value);
    }

    /**
     * Get tasks that are due for recurrence.
     *
     * @param Builder<Task> $query
     * @return Builder<Task>
     */
    public function scopeDueForRecurrence(Builder $query): Builder
    {
        return $query
            ->whereNotNull('next_occurrence_at')
            ->where('next_occurrence_at', '<=', now())
            ->where('status', '=', TaskStatus::Completed->value);
    }

    // ─── Accessors ───────────────────────────────────────────

    public function isOverdue(): bool
    {
        return $this->due_at !== null
            && $this->due_at->isPast()
            && $this->status !== TaskStatus::Completed;
    }

    public function isDueSoon(): bool
    {
        return $this->due_at !== null
            && $this->due_at->isBetween(now(), now()->addHours(48))
            && $this->status !== TaskStatus::Completed;
    }

    public function isRecurring(): bool
    {
        return $this->recurrence_type !== null
            && $this->recurrence_type !== RecurrenceType::None;
    }
}
