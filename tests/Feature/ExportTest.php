<?php

declare(strict_types=1);

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\Label;
use App\Models\Task;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test("unauthenticated user cannot export tasks", function () {
    $this->get(route("tasks.export.csv"))
        ->assertRedirect(route("login"));
});

test("user can export tasks to csv", function () {
    Task::factory()->count(3)->forUser($this->user)->create();

    actingAs($this->user)
        ->get(route("tasks.export.csv"))
        ->assertOk()
        ->assertHeader("Content-Type", "text/csv; charset=UTF-8")
        ->assertHeaderContains("Content-Disposition", "attachment");
});

test("csv export contains correct headers", function () {
    Task::factory()->count(1)->forUser($this->user)->create();

    $response = actingAs($this->user)
        ->get(route("tasks.export.csv"));

    $content = $response->streamedContent();

    // Verify BOM is present at the start
    expect(substr($content, 0, 3))->toBe("\xEF\xBB\xBF");

    // Strip BOM before parsing CSV
    $contentWithoutBom = substr($content, 3);
    $lines = explode("\n", $contentWithoutBom);

    $headers = str_getcsv($lines[0]);
    expect($headers)->toBe([
        "ID",
        "Title",
        "Description",
        "Status",
        "Priority",
        "Category",
        "Due Date",
        "Created At",
        "Updated At",
        "Labels",
        "Recurrence Type",
        "Recurrence Interval",
    ]);
});

test("csv export contains task data", function () {
    $task = Task::factory()->forUser($this->user)->create([
        "title" => "Test Task",
        "description" => "Test Description",
        "status" => TaskStatus::Pending,
        "priority" => TaskPriority::High,
    ]);

    $response = actingAs($this->user)
        ->get(route("tasks.export.csv"));

    $content = $response->streamedContent();
    // Strip BOM before parsing
    $contentWithoutBom = substr($content, 3);
    $lines = explode("\n", $contentWithoutBom);

    $found = false;
    for ($i = 1; $i < count($lines); $i++) {
        $row = str_getcsv($lines[$i]);
        if (isset($row[0]) && $row[0] == $task->id) {
            $found = true;
            expect($row[1])->toBe("Test Task");
            expect($row[2])->toBe("Test Description");
            expect($row[3])->toBe("Pending");
            expect($row[4])->toBe("High");
            break;
        }
    }

    expect($found)->toBeTrue();
});

test("csv export respects status filter", function () {
    Task::factory()->forUser($this->user)->create(["status" => TaskStatus::Pending]);
    Task::factory()->forUser($this->user)->create(["status" => TaskStatus::Completed]);

    $response = actingAs($this->user)
        ->get(route("tasks.export.csv", ["status" => "pending"]));

    $content = $response->streamedContent();
    // Strip BOM and parse
    $contentWithoutBom = substr($content, 3);
    $lines = explode("\n", $contentWithoutBom);

    // Header + 1 task + trailing newline
    expect(count($lines))->toBe(3);
});

test("csv export respects priority filter", function () {
    Task::factory()->forUser($this->user)->create(["priority" => TaskPriority::High]);
    Task::factory()->forUser($this->user)->create(["priority" => TaskPriority::Low]);

    $response = actingAs($this->user)
        ->get(route("tasks.export.csv", ["priority" => "high"]));

    $content = $response->streamedContent();
    $contentWithoutBom = substr($content, 3);
    $lines = explode("\n", $contentWithoutBom);

    expect(count($lines))->toBe(3);
});

test("csv export respects category filter", function () {
    $category = Category::factory()->forUser($this->user)->create();
    Task::factory()->forUser($this->user)->create(["category_id" => $category->id]);
    Task::factory()->forUser($this->user)->create(["category_id" => null]);

    $response = actingAs($this->user)
        ->get(route("tasks.export.csv", ["category_id" => $category->id]));

    $content = $response->streamedContent();
    $contentWithoutBom = substr($content, 3);
    $lines = explode("\n", $contentWithoutBom);

    expect(count($lines))->toBe(3);
});

test("csv export respects search filter", function () {
    Task::factory()->forUser($this->user)->create(["title" => "Unique Task Title"]);
    Task::factory()->forUser($this->user)->create(["title" => "Another Task"]);

    $response = actingAs($this->user)
        ->get(route("tasks.export.csv", ["search" => "Unique"]));

    $response->assertOk();
    $content = $response->streamedContent();
    $contentWithoutBom = substr($content, 3);
    $lines = explode("\n", $contentWithoutBom);

    expect(count($lines))->toBe(3);
});

test("csv export respects label filter", function () {
    $label = Label::factory()->forUser($this->user)->create();
    $taskWithLabel = Task::factory()->forUser($this->user)->create();
    $taskWithLabel->labels()->attach($label);

    Task::factory()->forUser($this->user)->create();

    $response = actingAs($this->user)
        ->get(route("tasks.export.csv", ["label_id" => $label->id]));

    $content = $response->streamedContent();
    $contentWithoutBom = substr($content, 3);
    $lines = explode("\n", $contentWithoutBom);

    expect(count($lines))->toBe(3);
});

test("csv export respects overdue filter", function () {
    Task::factory()->forUser($this->user)->create([
        "due_at" => now()->subDay(),
        "status" => TaskStatus::Pending,
    ]);
    Task::factory()->forUser($this->user)->create([
        "due_at" => now()->addDay(),
        "status" => TaskStatus::Pending,
    ]);

    $response = actingAs($this->user)
        ->get(route("tasks.export.csv", ["due" => "overdue"]));

    $content = $response->streamedContent();
    $contentWithoutBom = substr($content, 3);
    $lines = explode("\n", $contentWithoutBom);

    expect(count($lines))->toBe(3);
});

test("csv export respects due soon filter", function () {
    Task::factory()->forUser($this->user)->create([
        "due_at" => now()->addHours(12),
        "status" => TaskStatus::Pending,
    ]);
    Task::factory()->forUser($this->user)->create([
        "due_at" => now()->addDays(5),
        "status" => TaskStatus::Pending,
    ]);

    $response = actingAs($this->user)
        ->get(route("tasks.export.csv", ["due" => "soon"]));

    $content = $response->streamedContent();
    $contentWithoutBom = substr($content, 3);
    $lines = explode("\n", $contentWithoutBom);

    expect(count($lines))->toBe(3);
});

test("csv export only includes user tasks", function () {
    $otherUser = User::factory()->create();
    Task::factory()->forUser($this->user)->create();
    Task::factory()->forUser($otherUser)->create();

    $response = actingAs($this->user)
        ->get(route("tasks.export.csv"));

    $content = $response->streamedContent();
    $contentWithoutBom = substr($content, 3);
    $lines = explode("\n", $contentWithoutBom);

    expect(count($lines))->toBe(3);
});

test("csv export includes category name", function () {
    $category = Category::factory()->forUser($this->user)->create(["name" => "Work"]);
    Task::factory()->forUser($this->user)->create(["category_id" => $category->id]);

    $response = actingAs($this->user)
        ->get(route("tasks.export.csv"));

    $content = $response->streamedContent();
    $contentWithoutBom = substr($content, 3);
    $lines = explode("\n", $contentWithoutBom);

    $row = str_getcsv($lines[1]);
    expect($row[5])->toBe("Work");
});

test("csv export includes labels", function () {
    $label = Label::factory()->forUser($this->user)->create(["name" => "Important"]);
    $task = Task::factory()->forUser($this->user)->create();
    $task->labels()->attach($label);

    $response = actingAs($this->user)
        ->get(route("tasks.export.csv"));

    $content = $response->streamedContent();
    $contentWithoutBom = substr($content, 3);
    $lines = explode("\n", $contentWithoutBom);

    $row = str_getcsv($lines[1]);
    expect($row[9])->toBe("Important");
});

test("csv export handles empty task list", function () {
    $response = actingAs($this->user)
        ->get(route("tasks.export.csv"));

    $content = $response->streamedContent();
    $contentWithoutBom = substr($content, 3);
    $lines = explode("\n", $contentWithoutBom);

    expect(count($lines))->toBe(2);
});

test("csv export filename contains timestamp", function () {
    $response = actingAs($this->user)
        ->get(route("tasks.export.csv"));

    $contentDisposition = $response->headers->get("Content-Disposition");
    expect($contentDisposition)->toContain("tasks_" . now()->format("Y-m-d"));
});
