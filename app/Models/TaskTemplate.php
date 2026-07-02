<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TaskTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $title
 * @property string|null $description
 * @property string $priority
 * @property string $status
 * @property int|null $category_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 * @property-read Category|null $category
 */
final class TaskTemplate extends Model
{
    /** @use HasFactory<TaskTemplateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'title',
        'description',
        'priority',
        'status',
        'category_id',
    ];

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
     * Convert template to task data array.
     *
     * @return array<string, mixed>
     */
    public function toTaskData(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'category_id' => $this->category_id,
        ];
    }
}
