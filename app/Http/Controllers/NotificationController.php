<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Display the user's notifications.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $notifications = $this->notificationService->getNotificationsForUser($user, 15);
        $unreadCount = $this->notificationService->getUnreadCount($user);
        $preferences = NotificationPreference::getOrCreateForUser($user);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'preferences' => $preferences,
        ]);
    }

    /**
     * Get unread notification count (for header badge).
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user());

        return response()->json(['count' => $count]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        Gate::authorize('update', $notification);

        $this->notificationService->markAsRead($notification);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user());

        return response()->json(['success' => true, 'count' => $count]);
    }

    /**
     * Update notification preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_due_soon' => 'required|boolean',
            'email_overdue' => 'required|boolean',
            'email_reminders' => 'required|boolean',
            'in_app_due_soon' => 'required|boolean',
            'in_app_overdue' => 'required|boolean',
            'in_app_reminders' => 'required|boolean',
            'reminder_hours_before' => 'required|integer|min:1|max:168',
        ]);

        $preferences = NotificationPreference::getOrCreateForUser($request->user());
        $preferences->update($validated);

        return response()->json(['success' => true, 'preferences' => $preferences]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        Gate::authorize('delete', $notification);

        $notification->delete();

        return response()->json(['success' => true]);
    }
}
