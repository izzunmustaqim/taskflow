<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\RecurrenceType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query): void {
                    $query->where('user_id', $this->user()?->id);
                }),
            ],
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
            'label_ids' => ['nullable', 'array'],
            'label_ids.*' => [
                'integer',
                Rule::exists('labels', 'id')->where(function ($query): void {
                    $query->where('user_id', $this->user()?->id);
                }),
            ],
            'recurrence_type' => ['nullable', Rule::enum(RecurrenceType::class)],
            'recurrence_interval' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.exists' => 'The selected category does not belong to your account.',
            'due_at.after_or_equal' => 'The due date cannot be in the past.',
        ];
    }

    /**
     * Retrieve validated data with proper type casting.
     *
     * @return array<string, mixed>
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        if (isset($data['category_id'])) {
            $data['category_id'] = (int) $data['category_id'];
        }

        return $data;
    }
}
