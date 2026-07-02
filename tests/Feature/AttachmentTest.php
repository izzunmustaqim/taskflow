<?php

declare(strict_types=1);

use App\Models\Attachment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->task = Task::factory()->forUser($this->user)->create();
});

test('authenticated user can upload an attachment to their task', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    actingAs($this->user)
        ->postJson(route('tasks.attachments.store', $this->task->id), [
            'file' => $file,
        ])
        ->assertOk()
        ->assertJsonStructure([
            'message',
            'attachment' => ['id', 'original_name', 'mime_type', 'size', 'created_at'],
        ]);

    $this->assertDatabaseHas('attachments', [
        'task_id' => $this->task->id,
        'user_id' => $this->user->id,
        'original_name' => 'document.pdf',
        'mime_type' => 'application/pdf',
    ]);
});

test('authenticated user can upload an image attachment', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->image('photo.jpg', 200, 200);

    actingAs($this->user)
        ->postJson(route('tasks.attachments.store', $this->task->id), [
            'file' => $file,
        ])
        ->assertOk();

    $this->assertDatabaseHas('attachments', [
        'task_id' => $this->task->id,
        'original_name' => 'photo.jpg',
        'mime_type' => 'image/jpeg',
    ]);
});

test('authenticated user cannot upload disallowed file type', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

    actingAs($this->user)
        ->postJson(route('tasks.attachments.store', $this->task->id), [
            'file' => $file,
        ]);

    // The file should not have been stored as an attachment
    $this->assertDatabaseMissing('attachments', [
        'task_id' => $this->task->id,
        'original_name' => 'malware.exe',
    ]);
});

test('authenticated user cannot upload to another users task', function () {
    Storage::fake('local');

    $otherUser = User::factory()->create();
    $otherTask = Task::factory()->forUser($otherUser)->create();

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    actingAs($this->user)
        ->postJson(route('tasks.attachments.store', $otherTask->id), [
            'file' => $file,
        ])
        ->assertForbidden();
});

test('authenticated user can download their attachment', function () {
    Storage::fake('local');

    $attachment = Attachment::factory()
        ->forTask($this->task)
        ->forUser($this->user)
        ->create(['original_name' => 'test.txt']);

    // Create a fake file in storage
    Storage::disk('local')->put($attachment->path, 'test content');

    actingAs($this->user)
        ->get(route('attachments.download', $attachment->id))
        ->assertOk();
});

test('authenticated user cannot download another users attachment', function () {
    $otherUser = User::factory()->create();
    $otherTask = Task::factory()->forUser($otherUser)->create();

    $attachment = Attachment::factory()
        ->forTask($otherTask)
        ->forUser($otherUser)
        ->create();

    actingAs($this->user)
        ->get(route('attachments.download', $attachment->id))
        ->assertForbidden();
});

test('authenticated user can delete their attachment', function () {
    Storage::fake('local');

    $attachment = Attachment::factory()
        ->forTask($this->task)
        ->forUser($this->user)
        ->create();

    actingAs($this->user)
        ->deleteJson(route('attachments.destroy', $attachment->id))
        ->assertOk();

    $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
});

test('authenticated user cannot delete another users attachment', function () {
    $otherUser = User::factory()->create();
    $otherTask = Task::factory()->forUser($otherUser)->create();

    $attachment = Attachment::factory()
        ->forTask($otherTask)
        ->forUser($otherUser)
        ->create();

    actingAs($this->user)
        ->deleteJson(route('attachments.destroy', $attachment->id))
        ->assertForbidden();

    $this->assertDatabaseHas('attachments', ['id' => $attachment->id]);
});

test('task edit page includes attachments', function () {
    $attachment = Attachment::factory()
        ->forTask($this->task)
        ->forUser($this->user)
        ->create(['original_name' => 'report.pdf']);

    actingAs($this->user)
        ->get(route('tasks.edit', $this->task->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Tasks/Edit')
            ->has('task.attachments', 1)
        );
});
