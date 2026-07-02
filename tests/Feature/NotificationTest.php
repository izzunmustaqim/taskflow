<?php

declare(strict_types=1);

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\Task;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});
describe('Notification Model', function (): void {
    it('can create a notification', function (): void {
        $notification = Notification::create([
            'user_id' => $this->user->id,
            'type' => 'due_soon',
            'title' => 'Test Notification',
            'message' => 'This is a test notification',
            'data' => ['task_id' => 1],
            'is_read' => false,
        ]);

        expect($notification)->toBeInstanceOf(Notification::class)
            ->and($notification->user_id)->toBe($this->user->id)
            ->and($notification->type)->toBe('due_soon')
            ->and($notification->is_read)->toBeFalse();
    });

    it('can mark notification as read', function (): void {
        $notification = Notification::create([
            'user_id' => $this->user->id,
            'type' => 'reminder',
            'title' => 'Test',
            'message' => 'Test message',
            'is_read' => false,
        ]);

        $notification->markAsRead();

        expect($notification->fresh()->is_read)->toBeTrue()
            ->and($notification->fresh()->read_at)->not->toBeNull();
    });

    it('can mark all notifications as read', function (): void {
        Notification::create(['user_id' => $this->user->id, 'type' => 'due_soon', 'title' => 'T1', 'message' => 'M1', 'is_read' => false]);
        Notification::create(['user_id' => $this->user->id, 'type' => 'overdue', 'title' => 'T2', 'message' => 'M2', 'is_read' => false]);

        $count = Notification::markAllAsRead($this->user);

        expect($count)->toBe(2)->and(Notification::forUser($this->user)->unread()->count())->toBe(0);
    });

    it('scopes notifications by user', function (): void {
        $other = User::factory()->create();
        Notification::create(['user_id' => $this->user->id, 'type' => 'due_soon', 'title' => 'Mine', 'message' => 'Mine']);
        Notification::create(['user_id' => $other->id, 'type' => 'overdue', 'title' => 'Theirs', 'message' => 'Theirs']);

        expect(Notification::forUser($this->user)->get())->toHaveCount(1);
    });
});
describe('Notification Service', function (): void {
    beforeEach(function (): void {
        $this->service = new NotificationService();
    });

    it('can create an in-app notification', function (): void {
        $notification = $this->service->createInAppNotification(
            $this->user, 'due_soon', 'Test Title', 'Test Message', ['task_id' => 1]
        );

        expect($notification)->toBeInstanceOf(Notification::class)
            ->and($notification->user_id)->toBe($this->user->id)
            ->and($notification->type)->toBe('due_soon');
    });

    it('can send due soon notification', function (): void {
        NotificationFacade::fake();
        $task = Task::factory()->forUser($this->user)->create(['title' => 'Test Task', 'due_at' => now()->addHours(2)]);
        NotificationPreference::create(['user_id' => $this->user->id, 'email_due_soon' => true, 'in_app_due_soon' => true]);

        $this->service->sendDueSoonNotification($task);

        $inApp = Notification::where('type', 'due_soon')->where('user_id', $this->user->id)->first();
        expect($inApp)->not->toBeNull()->and($inApp->title)->toBe('Task Due Soon');
    });

    it('respects user notification preferences', function (): void {
        NotificationFacade::fake();
        $task = Task::factory()->forUser($this->user)->create(['due_at' => now()->addHours(2)]);
        NotificationPreference::create(['user_id' => $this->user->id, 'email_due_soon' => false, 'in_app_due_soon' => false]);

        $this->service->sendDueSoonNotification($task);

        expect(Notification::where('user_id', $this->user->id)->get())->toHaveCount(0);
    });

    it('can delete old notifications', function (): void {
        $old = Notification::create(['user_id' => $this->user->id, 'type' => 'old', 'title' => 'Old', 'message' => 'Old']);
        $old->forceFill(['created_at' => now()->subDays(31)])->save();
        Notification::create(['user_id' => $this->user->id, 'type' => 'new', 'title' => 'New', 'message' => 'New']);

        $deleted = $this->service->deleteOldNotifications(30);
        expect($deleted)->toBe(1)->and(Notification::count())->toBe(1);
    });
});
describe('Notification Controller', function (): void {
    it('can display notifications index page', function (): void {
        $response = $this->get(route('notifications.index'));
        $response->assertStatus(200)->assertInertia(fn ($page) => $page->component('Notifications/Index'));
    });

    it('can get unread count', function (): void {
        Notification::create(['user_id' => $this->user->id, 'type' => 'due_soon', 'title' => 'T', 'message' => 'M', 'is_read' => false]);
        $response = $this->getJson(route('notifications.unread-count'));
        $response->assertOk()->assertJson(['count' => 1]);
    });

    it('can mark notification as read via controller', function (): void {
        $n = Notification::create(['user_id' => $this->user->id, 'type' => 'reminder', 'title' => 'T', 'message' => 'M', 'is_read' => false]);
        $response = $this->post(route('notifications.mark-read', $n));
        $response->assertOk()->assertJson(['success' => true]);
        expect($n->fresh()->is_read)->toBeTrue();
    });

    it('can mark all as read', function (): void {
        Notification::create(['user_id' => $this->user->id, 'type' => 'a', 'title' => 'T', 'message' => 'M', 'is_read' => false]);
        Notification::create(['user_id' => $this->user->id, 'type' => 'b', 'title' => 'T', 'message' => 'M', 'is_read' => false]);
        $response = $this->post(route('notifications.mark-all-read'));
        $response->assertOk()->assertJson(['success' => true, 'count' => 2]);
    });

    it('can update preferences', function (): void {
        $response = $this->putJson(route('notifications.preferences'), [
            'email_due_soon' => false, 'email_overdue' => true, 'email_reminders' => false,
            'in_app_due_soon' => true, 'in_app_overdue' => false, 'in_app_reminders' => true,
            'reminder_hours_before' => 48,
        ]);
        $response->assertOk()->assertJson(['success' => true]);
        $p = NotificationPreference::where('user_id', $this->user->id)->first();
        expect($p)->not->toBeNull()->and($p->reminder_hours_before)->toBe(48);
    });

    it('cannot access other users notifications', function (): void {
        $other = User::factory()->create();
        $n = Notification::create(['user_id' => $other->id, 'type' => 'test', 'title' => 'T', 'message' => 'M', 'is_read' => false]);
        $response = $this->post(route('notifications.mark-read', $n));
        $response->assertForbidden();
    });
});

describe('Notification Security', function (): void {
    it('requires authentication', function (): void {
        auth()->logout();
        $response = $this->get(route('notifications.index'));
        $response->assertRedirect('/login');
    });

    it('enforces multi-tenant isolation', function (): void {
        $other = User::factory()->create();
        Notification::create(['user_id' => $this->user->id, 'type' => 'mine', 'title' => 'Mine', 'message' => 'M']);
        Notification::create(['user_id' => $other->id, 'type' => 'theirs', 'title' => 'Theirs', 'message' => 'M']);
        $response = $this->getJson(route('notifications.unread-count'));
        $response->assertOk()->assertJson(['count' => 1]);
    });
});