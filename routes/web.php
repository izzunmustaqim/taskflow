<?php

declare(strict_types=1);

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Tasks (including soft delete routes)
    Route::get('/tasks/trash', [TaskController::class, 'trash'])->name('tasks.trash');
    Route::post('/tasks/{task}/restore', [TaskController::class, 'restore'])->name('tasks.restore')->withTrashed();
    Route::delete('/tasks/{task}/force', [TaskController::class, 'forceDestroy'])->name('tasks.force-destroy')->withTrashed();

    // Bulk operations
    Route::post('/tasks/bulk-delete', [TaskController::class, 'bulkDestroy'])->name('tasks.bulk-delete');
    Route::post('/tasks/bulk-status', [TaskController::class, 'bulkUpdateStatus'])->name('tasks.bulk-status');
    Route::post('/tasks/bulk-restore', [TaskController::class, 'bulkRestore'])->name('tasks.bulk-restore');
    Route::post('/tasks/bulk-force-delete', [TaskController::class, 'bulkForceDestroy'])->name('tasks.bulk-force-delete');

    Route::resource('tasks', TaskController::class);

    // Categories
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
