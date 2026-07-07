<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportController extends Controller
{
    public function exportTasksCsv(Request $request): StreamedResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $filters = $request->only(['status', 'priority', 'category_id', 'label_id', 'search', 'due']);
        $tasks = $this->getFilteredTasks($user, $filters);

        $filename = 'tasks_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($tasks) {
            $handle = fopen('php://output', 'w');

            // Add BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($handle, [
                'ID',
                'Title',
                'Description',
                'Status',
                'Priority',
                'Category',
                'Due Date',
                'Created At',
                'Updated At',
                'Labels',
                'Recurrence Type',
                'Recurrence Interval',
            ]);

            // Data rows
            foreach ($tasks as $task) {
                fputcsv($handle, [
                    $task->id,
                    $task->title,
                    $task->description ?? '',
                    $task->status->label(),
                    $task->priority->label(),
                    $task->category?->name ?? '',
                    $task->due_at?->format('Y-m-d H:i') ?? '',
                    $task->created_at->format('Y-m-d H:i'),
                    $task->updated_at->format('Y-m-d H:i'),
                    $task->labels->pluck('name')->implode(', '),
                    $task->recurrence_type?->label() ?? 'None',
                    $task->recurrence_interval,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment',
        ]);
    }

    private function getFilteredTasks($user, array $filters)
    {
        $query = Task::query()
            ->forUser($user)
            ->with(['category', 'labels'])
            ->orderBy('sort_order')
            ->latest('created_at');

        if (isset($filters['status']) && $filters['status'] !== '') {
            $status = \App\Enums\TaskStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->byStatus($status);
            }
        }

        if (isset($filters['priority']) && $filters['priority'] !== '') {
            $priority = \App\Enums\TaskPriority::tryFrom($filters['priority']);
            if ($priority !== null) {
                $query->byPriority($priority);
            }
        }

        if (isset($filters['category_id']) && $filters['category_id'] !== '') {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if (isset($filters['label_id']) && $filters['label_id'] !== '') {
            $query->whereHas('labels', function ($q) use ($filters): void {
                $q->where('labels.id', (int) $filters['label_id']);
            });
        }

        if (isset($filters['due']) && $filters['due'] === 'overdue') {
            $query->overdue();
        } elseif (isset($filters['due']) && $filters['due'] === 'soon') {
            $query->dueSoon();
        }

        return $query->get();
    }
}
