<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Label;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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

    public function exportTasksPdf(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $filters = $request->only(['status', 'priority', 'category_id', 'label_id', 'search', 'due']);
        $tasks = $this->getFilteredTasks($user, $filters);

        // Build filter description
        $filterParts = [];
        if (!empty($filters['status'])) {
            $filterParts[] = 'Status: ' . ucfirst($filters['status']);
        }
        if (!empty($filters['priority'])) {
            $filterParts[] = 'Priority: ' . ucfirst($filters['priority']);
        }
        if (!empty($filters['search'])) {
            $filterParts[] = 'Search: ' . $filters['search'];
        }
        $filterDescription = !empty($filterParts) ? implode(', ', $filterParts) : '';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.tasks-pdf', [
            'tasks' => $tasks,
            'filters' => $filterDescription,
        ]);

        $filename = 'tasks_' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
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

    /**
     * Export all user data as a JSON backup file.
     */
    public function exportBackup(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Fetch all user data with relationships
        $categories = Category::forUser($user)->get();
        $labels = Label::forUser($user)->get();
        $tasks = Task::forUser($user)
            ->with(['category', 'labels'])
            ->get();

        $backup = [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'categories' => $categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'color' => $cat->color,
                'created_at' => $cat->created_at->toIso8601String(),
                'updated_at' => $cat->updated_at->toIso8601String(),
            ]),
            'labels' => $labels->map(fn ($label) => [
                'id' => $label->id,
                'name' => $label->name,
                'color' => $label->color,
                'created_at' => $label->created_at->toIso8601String(),
                'updated_at' => $label->updated_at->toIso8601String(),
            ]),
            'tasks' => $tasks->map(fn ($task) => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status->value,
                'priority' => $task->priority->value,
                'due_at' => $task->due_at?->toIso8601String(),
                'category_id' => $task->category_id,
                'category_name' => $task->category?->name,
                'sort_order' => $task->sort_order,
                'recurrence_type' => $task->recurrence_type?->value,
                'recurrence_interval' => $task->recurrence_interval,
                'labels' => $task->labels->pluck('name'),
                'created_at' => $task->created_at->toIso8601String(),
                'updated_at' => $task->updated_at->toIso8601String(),
            ]),
        ];

        $filename = 'taskflow_backup_' . now()->format('Y-m-d_His') . '.json';

        return response($backup)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Restore user data from a JSON backup file.
     */
    public function restoreBackup(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $request->validate([
            'backup_file' => 'required|file',
        ]);

        $file = $request->file('backup_file');

        if (!$file) {
            return response()->json([
                'message' => 'No file uploaded.',
            ], 422);
        }

        $content = file_get_contents($file->getRealPath());
        $backup = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'message' => 'Invalid JSON file.',
            ], 422);
        }

        // Validate backup structure
        if (!isset($backup['version']) || !isset($backup['tasks'])) {
            return response()->json([
                'message' => 'Invalid backup file format.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Restore categories (map old IDs to new IDs)
            $categoryMap = [];
            if (!empty($backup['categories'])) {
                foreach ($backup['categories'] as $catData) {
                    $newCategory = Category::create([
                        'user_id' => $user->id,
                        'name' => $catData['name'],
                        'color' => $catData['color'] ?? '#6366f1',
                    ]);
                    $categoryMap[$catData['id']] = $newCategory->id;
                }
            }

            // Restore labels (map old IDs to new IDs)
            $labelMap = [];
            if (!empty($backup['labels'])) {
                foreach ($backup['labels'] as $labelData) {
                    $newLabel = Label::create([
                        'user_id' => $user->id,
                        'name' => $labelData['name'],
                        'color' => $labelData['color'] ?? '#6366f1',
                    ]);
                    $labelMap[$labelData['id']] = $newLabel->id;
                }
            }

            // Restore tasks
            $taskCount = 0;
            foreach ($backup['tasks'] as $taskData) {
                $categoryId = null;
                if (!empty($taskData['category_id']) && isset($categoryMap[$taskData['category_id']])) {
                    $categoryId = $categoryMap[$taskData['category_id']];
                }

                // If category wasn't in backup but category_name exists, try to find or create it
                if (empty($categoryId) && !empty($taskData['category_name'])) {
                    $existingCategory = Category::where('user_id', $user->id)
                        ->where('name', $taskData['category_name'])
                        ->first();
                    if ($existingCategory) {
                        $categoryId = $existingCategory->id;
                    } else {
                        $newCategory = Category::create([
                            'user_id' => $user->id,
                            'name' => $taskData['category_name'],
                            'color' => '#6366f1',
                        ]);
                        $categoryId = $newCategory->id;
                    }
                }

                $task = Task::create([
                    'user_id' => $user->id,
                    'title' => $taskData['title'],
                    'description' => $taskData['description'] ?? null,
                    'status' => $taskData['status'] ?? 'pending',
                    'priority' => $taskData['priority'] ?? 'medium',
                    'due_at' => $taskData['due_at'] ?? null,
                    'category_id' => $categoryId,
                    'sort_order' => $taskData['sort_order'] ?? 0,
                    'recurrence_type' => $taskData['recurrence_type'] ?? null,
                    'recurrence_interval' => $taskData['recurrence_interval'] ?? null,
                ]);

                // Restore task labels
                if (!empty($taskData['labels'])) {
                    foreach ($taskData['labels'] as $labelName) {
                        $label = Label::where('user_id', $user->id)
                            ->where('name', $labelName)
                            ->first();
                        if ($label) {
                            $task->labels()->attach($label->id);
                        }
                    }
                }

                $taskCount++;
            }

            DB::commit();

            return response()->json([
                'message' => "Backup restored successfully! Restored {$taskCount} task(s).",
                'tasks_restored' => $taskCount,
                'categories_restored' => count($categoryMap),
                'labels_restored' => count($labelMap),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to restore backup: ' . $e->getMessage(),
            ], 500);
        }
    }
}
