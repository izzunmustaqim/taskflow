<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TaskService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(Request $request, TaskService $taskService): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $stats = $taskService->getStats($user);
        $recentTasks = $taskService->getRecent($user, 5);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentTasks' => $recentTasks,
        ]);
    }
}
