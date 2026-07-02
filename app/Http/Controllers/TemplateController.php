<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskTemplate;
use App\Services\CategoryService;
use App\Services\LabelService;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class TemplateController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly LabelService $labelService,
        private readonly TaskService $taskService,
    ) {}

    /**
     * Display a listing of the user's templates.
     */
    public function index(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $templates = TaskTemplate::query()
            ->where('user_id', $user->id)
            ->with('category')
            ->latest()
            ->paginate(15);

        return Inertia::render('Templates/Index', [
            'templates' => $templates,
        ]);
    }

    /**
     * Show the form for creating a new template.
     */
    public function create(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return Inertia::render('Templates/Create', [
            'categories' => $this->categoryService->list($user),
            'labels' => $this->labelService->list($user),
            'priorities' => \App\Enums\TaskPriority::options(),
        ]);
    }

    /**
     * Store a newly created template.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'task_id' => 'nullable|integer|exists:tasks,id',
            'title' => 'required_without:task_id|string|max:255',
            'description' => 'nullable|string|max:10000',
            'priority' => 'nullable|string|in:low,medium,high',
            'status' => 'nullable|string|in:pending,in_progress,completed',
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        if ($request->filled('task_id')) {
            $task = Task::findOrFail($request->input('task_id'));
            Gate::authorize('view', $task);

            $template = TaskTemplate::create([
                'user_id' => $user->id,
                'name' => $request->input('name'),
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority->value,
                'status' => $task->status->value,
                'category_id' => $task->category_id,
            ]);
        } else {
            $template = TaskTemplate::create([
                'user_id' => $user->id,
                'name' => $request->input('name'),
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'priority' => $request->input('priority', 'medium'),
                'status' => $request->input('status', 'pending'),
                'category_id' => $request->input('category_id'),
            ]);
        }

        return redirect()->route('templates.index')
            ->with('success', 'Template created successfully.');
    }

    /**
     * Show the form for editing the specified template.
     */
    public function edit(Request $request, TaskTemplate $template): Response
    {
        Gate::authorize('view', $template);

        /** @var \App\Models\User $user */
        $user = $request->user();

        return Inertia::render('Templates/Edit', [
            'template' => $template,
            'categories' => $this->categoryService->list($user),
            'priorities' => \App\Enums\TaskPriority::options(),
        ]);
    }

    /**
     * Update the specified template.
     */
    public function update(Request $request, TaskTemplate $template): RedirectResponse
    {
        Gate::authorize('update', $template);

        $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'priority' => 'nullable|string|in:low,medium,high',
            'status' => 'nullable|string|in:pending,in_progress,completed',
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        $template->update($request->only([
            'name', 'title', 'description', 'priority', 'status', 'category_id',
        ]));

        return redirect()->route('templates.index')
            ->with('success', 'Template updated successfully.');
    }

    /**
     * Remove the specified template.
     */
    public function destroy(TaskTemplate $template): RedirectResponse
    {
        Gate::authorize('delete', $template);

        $template->delete();

        return redirect()->route('templates.index')
            ->with('success', 'Template deleted successfully.');
    }

    /**
     * Create a new task from a template.
     */
    public function useTemplate(TaskTemplate $template): RedirectResponse
    {
        Gate::authorize('view', $template);

        /** @var \App\Models\User $user */
        $user = request()->user();

        $task = $this->taskService->create($user, $template->toTaskData());

        return redirect()->route('tasks.edit', $task)
            ->with('success', 'Task created from template "' . $template->name . '"');
    }
}
