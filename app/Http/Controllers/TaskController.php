<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\BulkStatusRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Services\CategoryService;
use App\Services\LabelService;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly CategoryService $categoryService,
        private readonly LabelService $labelService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Task::class);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $filters = $request->only(['status', 'priority', 'category_id', 'label_id', 'search', 'due', 'per_page', 'sort', 'order']);
        $tasks = $this->taskService->list($user, $filters);
        $categories = $this->categoryService->list($user);
        $stats = $this->taskService->getStats($user);

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'filters' => $filters,
            'categories' => $categories,
            'labels' => $this->labelService->list($user),
            'stats' => $stats,
            'statuses' => TaskStatus::options(),
            'priorities' => TaskPriority::options(),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Task::class);

        /** @var \App\Models\User $user */
        $user = $request->user();

        return Inertia::render('Tasks/Create', [
            'categories' => $this->categoryService->list($user),
            'labels' => $this->labelService->list($user),
            'statuses' => TaskStatus::options(),
            'priorities' => TaskPriority::options(),
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        Gate::authorize('create', Task::class);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $this->taskService->create($user, $request->validatedData());

        return redirect()->route('tasks.index')
            ->with('success', 'Task created successfully.');
    }

    public function edit(Request $request, Task $task): Response
    {
        Gate::authorize('view', $task);

        /** @var \App\Models\User $user */
        $user = $request->user();

        return Inertia::render('Tasks/Edit', [
            'task' => $task->load('labels'),
            'categories' => $this->categoryService->list($user),
            'labels' => $this->labelService->list($user),
            'statuses' => TaskStatus::options(),
            'priorities' => TaskPriority::options(),
            'activityLog' => $this->taskService->getActivityLog($task),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        $this->taskService->update($task, $request->validatedData());

        return redirect()->back()
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $this->taskService->delete($task);

        return redirect()->back()
            ->with('success', 'Task moved to trash.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {

        /** @var \App\Models\User $user */
        $user = $request->user();

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:tasks,id',
        ]);

        $ids = $request->input('ids');
        $count = $this->taskService->bulkDelete($user, $ids);

        return redirect()->back()
            ->with('success', "{$count} task(s) moved to trash.");
    }

    public function bulkUpdateStatus(BulkStatusRequest $request): RedirectResponse
    {

        /** @var \App\Models\User $user */
        $user = $request->user();

        $ids = $request->input('ids');
        $status = TaskStatus::from($request->input('status'));
        $count = $this->taskService->bulkUpdateStatus($user, $ids, $status);

        return redirect()->back()
            ->with('success', "{$count} task(s) updated to {$status->label()}.");
    }

    public function trash(Request $request): Response
    {
        Gate::authorize('viewAny', Task::class);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $tasks = $this->taskService->listTrashed($user);

        return Inertia::render('Tasks/Trash', [
            'tasks' => $tasks,
        ]);
    }

    public function restore(Task $task): RedirectResponse
    {
        Gate::authorize('restore', $task);

        $this->taskService->restore($task);

        return redirect()->back()
            ->with('success', 'Task restored successfully.');
    }

    public function forceDestroy(Task $task): RedirectResponse
    {
        Gate::authorize('forceDelete', $task);

        $this->taskService->forceDelete($task);

        return redirect()->back()
            ->with('success', 'Task permanently deleted.');
    }

    public function bulkRestore(Request $request): RedirectResponse
    {

        /** @var \App\Models\User $user */
        $user = $request->user();

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:tasks,id',
        ]);

        $ids = $request->input('ids');
        $count = $this->taskService->bulkRestore($user, $ids);

        return redirect()->back()
            ->with('success', "{$count} task(s) restored successfully.");
    }

    public function bulkForceDestroy(Request $request): RedirectResponse
    {

        /** @var \App\Models\User $user */
        $user = $request->user();

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:tasks,id',
        ]);

        $ids = $request->input('ids');
        $count = $this->taskService->bulkForceDelete($user, $ids);

        return redirect()->back()
            ->with('success', "{$count} task(s) permanently deleted.");
    }

    public function reorder(Request $request): \Illuminate\Http\JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:tasks,id',
        ]);

        $ids = $request->input('ids');
        $count = $this->taskService->reorder($user, $ids);

        return response()->json([
            'success' => true,
            'message' => "{$count} task(s) reordered.",
        ]);
    }
}
