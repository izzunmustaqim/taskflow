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

    // Reorder
    Route::post('/tasks/reorder', [TaskController::class, 'reorder'])->name('tasks.reorder');

    // Export
    Route::get('/tasks/export/csv', [\App\Http\Controllers\ExportController::class, 'exportTasksCsv'])->name('tasks.export.csv');
    Route::get('/tasks/export/pdf', [\App\Http\Controllers\ExportController::class, 'exportTasksPdf'])->name('tasks.export.pdf');

    // Import
    Route::post('/tasks/import/csv', [\App\Http\Controllers\ImportController::class, 'importTasksCsv'])->name('tasks.import.csv');
    Route::get('/tasks/import/errors', [\App\Http\Controllers\ImportController::class, 'getImportErrors'])->name('tasks.import.errors');

    // Attachments
    Route::post('/tasks/{task}/attachments', [\App\Http\Controllers\AttachmentController::class, 'store'])->name('tasks.attachments.store');
    Route::get('/attachments/{attachment}/download', [\App\Http\Controllers\AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('/attachments/{attachment}', [\App\Http\Controllers\AttachmentController::class, 'destroy'])->name('attachments.destroy');

    Route::resource('tasks', TaskController::class);

    // Categories
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Labels
    Route::resource('labels', \App\Http\Controllers\LabelController::class)->except(['show']);

    // Templates
    Route::resource('templates', \App\Http\Controllers\TemplateController::class)->except(['show']);
    Route::post('/templates/{template}/use', [\App\Http\Controllers\TemplateController::class, 'useTemplate'])->name('templates.use');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::put('/notifications/preferences', [\App\Http\Controllers\NotificationController::class, 'updatePreferences'])->name('notifications.preferences');
    Route::delete('/notifications/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
});

require __DIR__.'/auth.php';
