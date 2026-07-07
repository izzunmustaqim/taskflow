<?php

declare(strict_types=1);

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test("unauthenticated user cannot import tasks", function () {
    $this->post(route("tasks.import.csv"))
        ->assertRedirect(route("login"));
});

test("user can import tasks from csv", function () {
    $csvContent = "title,description,status,priority,category,due date,labels" . PHP_EOL
        . "Task 1,Description 1,pending,high,Work,2026-12-31,Important" . PHP_EOL
        . "Task 2,Description 2,in_progress,medium,,," . PHP_EOL;

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "tasks.csv",
        $csvContent
    );

    actingAs($this->user)
        ->post(route("tasks.import.csv"), ["csv_file" => $file])
        ->assertRedirect(route("tasks.index"))
        ->assertSessionHas("success");

    $this->assertDatabaseHas("tasks", ["title" => "Task 1", "user_id" => $this->user->id]);
    $this->assertDatabaseHas("tasks", ["title" => "Task 2", "user_id" => $this->user->id]);
});

test("csv import requires title column", function () {
    $csvContent = "description,status" . PHP_EOL
        . "Description 1,pending" . PHP_EOL;

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "tasks.csv",
        $csvContent
    );

    actingAs($this->user)
        ->post(route("tasks.import.csv"), ["csv_file" => $file])
        ->assertSessionHasErrors(["csv_file"]);
});

test("csv import handles empty file", function () {
    $csvContent = "title,description" . PHP_EOL;

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "tasks.csv",
        $csvContent
    );

    actingAs($this->user)
        ->post(route("tasks.import.csv"), ["csv_file" => $file])
        ->assertRedirect(route("tasks.index"))
        ->assertSessionHas("success");
});

test("csv import creates categories from csv", function () {
    $csvContent = "title,category" . PHP_EOL
        . "Task 1,New Category" . PHP_EOL;

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "tasks.csv",
        $csvContent
    );

    actingAs($this->user)
        ->post(route("tasks.import.csv"), ["csv_file" => $file])
        ->assertRedirect(route("tasks.index"));

    $this->assertDatabaseHas("categories", [
        "name" => "New Category",
        "user_id" => $this->user->id,
    ]);
});

test("csv import creates labels from csv", function () {
    $csvContent = 'title,labels' . PHP_EOL
        . '"Task 1","Important,Urgent"' . PHP_EOL;

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "tasks.csv",
        $csvContent
    );

    actingAs($this->user)
        ->post(route("tasks.import.csv"), ["csv_file" => $file])
        ->assertRedirect(route("tasks.index"));

    $this->assertDatabaseHas("labels", [
        "name" => "Important",
        "user_id" => $this->user->id,
    ]);
    $this->assertDatabaseHas("labels", [
        "name" => "Urgent",
        "user_id" => $this->user->id,
    ]);
});

test("csv import maps status correctly", function () {
    $csvContent = "title,status" . PHP_EOL
        . "Task 1,pending" . PHP_EOL
        . "Task 2,completed" . PHP_EOL
        . "Task 3,in_progress" . PHP_EOL;

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "tasks.csv",
        $csvContent
    );

    actingAs($this->user)
        ->post(route("tasks.import.csv"), ["csv_file" => $file])
        ->assertRedirect(route("tasks.index"));

    $this->assertDatabaseHas("tasks", ["title" => "Task 1", "status" => "pending"]);
    $this->assertDatabaseHas("tasks", ["title" => "Task 2", "status" => "completed"]);
    $this->assertDatabaseHas("tasks", ["title" => "Task 3", "status" => "in_progress"]);
});

test("csv import maps priority correctly", function () {
    $csvContent = "title,priority" . PHP_EOL
        . "Task 1,high" . PHP_EOL
        . "Task 2,low" . PHP_EOL;

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "tasks.csv",
        $csvContent
    );

    actingAs($this->user)
        ->post(route("tasks.import.csv"), ["csv_file" => $file])
        ->assertRedirect(route("tasks.index"));

    $this->assertDatabaseHas("tasks", ["title" => "Task 1", "priority" => "high"]);
    $this->assertDatabaseHas("tasks", ["title" => "Task 2", "priority" => "low"]);
});

test("csv import handles due date", function () {
    $csvContent = "title,due date" . PHP_EOL
        . "Task 1,2026-12-31" . PHP_EOL
        . "Task 2,2026-06-15 14:30" . PHP_EOL;

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "tasks.csv",
        $csvContent
    );

    actingAs($this->user)
        ->post(route("tasks.import.csv"), ["csv_file" => $file])
        ->assertRedirect(route("tasks.index"));

    $task1 = Task::where("title", "Task 1")->first();
    expect($task1->due_at->format("Y-m-d"))->toBe("2026-12-31");

    $task2 = Task::where("title", "Task 2")->first();
    expect($task2->due_at->format("Y-m-d H:i"))->toBe("2026-06-15 14:30");
});

test("csv import skips rows without title", function () {
    $csvContent = "title,description" . PHP_EOL
        . "Task 1,Description 1" . PHP_EOL
        . ",No Title" . PHP_EOL
        . "Task 3,Description 3" . PHP_EOL;

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "tasks.csv",
        $csvContent
    );

    actingAs($this->user)
        ->post(route("tasks.import.csv"), ["csv_file" => $file])
        ->assertRedirect(route("tasks.index"))
        ->assertSessionHas("success");

    $this->assertDatabaseHas("tasks", ["title" => "Task 1"]);
    $this->assertDatabaseMissing("tasks", ["title" => ""]);
    $this->assertDatabaseHas("tasks", ["title" => "Task 3"]);
});

test("csv import only creates tasks for authenticated user", function () {
    $otherUser = User::factory()->create();

    $csvContent = "title" . PHP_EOL
        . "My Task" . PHP_EOL;

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "tasks.csv",
        $csvContent
    );

    actingAs($this->user)
        ->post(route("tasks.import.csv"), ["csv_file" => $file])
        ->assertRedirect(route("tasks.index"));

    $this->assertDatabaseHas("tasks", [
        "title" => "My Task",
        "user_id" => $this->user->id,
    ]);
    $this->assertDatabaseMissing("tasks", [
        "title" => "My Task",
        "user_id" => $otherUser->id,
    ]);
});

test("csv import works with test_tasks.csv file", function () {
    $csvContent = file_get_contents(base_path('test_tasks.csv'));

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "test_tasks.csv",
        $csvContent
    );

    actingAs($this->user)
        ->post(route("tasks.import.csv"), ["csv_file" => $file])
        ->assertRedirect(route("tasks.index"))
        ->assertSessionHas("success");

    // Verify all 3 tasks were created
    $this->assertDatabaseHas("tasks", [
        "title" => "Buy groceries",
        "user_id" => $this->user->id,
        "status" => "pending",
        "priority" => "high",
    ]);

    $this->assertDatabaseHas("tasks", [
        "title" => "Write report",
        "user_id" => $this->user->id,
        "status" => "in_progress",
        "priority" => "medium",
    ]);

    $this->assertDatabaseHas("tasks", [
        "title" => "Call doctor",
        "user_id" => $this->user->id,
        "status" => "pending",
        "priority" => "low",
    ]);

    // Verify categories were created
    $this->assertDatabaseHas("categories", [
        "name" => "Personal",
        "user_id" => $this->user->id,
    ]);

    $this->assertDatabaseHas("categories", [
        "name" => "Work",
        "user_id" => $this->user->id,
    ]);

    $this->assertDatabaseHas("categories", [
        "name" => "Health",
        "user_id" => $this->user->id,
    ]);

    // Verify labels were created
    $this->assertDatabaseHas("labels", [
        "name" => "Shopping",
        "user_id" => $this->user->id,
    ]);

    $this->assertDatabaseHas("labels", [
        "name" => "Important",
        "user_id" => $this->user->id,
    ]);

    $this->assertDatabaseHas("labels", [
        "name" => "Urgent",
        "user_id" => $this->user->id,
    ]);

    // Verify due dates
    $task1 = \App\Models\Task::where("title", "Buy groceries")->first();
    expect($task1->due_at->format("Y-m-d"))->toBe("2026-12-31");

    $task3 = \App\Models\Task::where("title", "Call doctor")->first();
    expect($task3->due_at->format("Y-m-d"))->toBe("2026-08-15");
});

test("csv import works with multipart form data", function () {
    // This test simulates how the browser sends file uploads
    // The issue was that Inertia.js needs forceFormData: true
    $csvContent = "title,description,status" . PHP_EOL
        . "Multipart Task,Test multipart upload,pending" . PHP_EOL;

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "upload_test.csv",
        $csvContent
    );

    // Simulate multipart/form-data upload (as browser would send)
    actingAs($this->user)
        ->post(route("tasks.import.csv"), [
            'csv_file' => $file,
        ], [
            'Content-Type' => 'multipart/form-data',
        ])
        ->assertRedirect(route("tasks.index"))
        ->assertSessionHas("success");

    $this->assertDatabaseHas("tasks", [
        "title" => "Multipart Task",
        "user_id" => $this->user->id,
    ]);
});

test("csv import validates file is required", function () {
    actingAs($this->user)
        ->post(route("tasks.import.csv"), [])
        ->assertSessionHasErrors(["csv_file"]);
});

test("csv import validates file type", function () {
    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "test.txt",
        "Not a CSV file"
    );

    actingAs($this->user)
        ->post(route("tasks.import.csv"), ["csv_file" => $file])
        ->assertSessionHasErrors(["csv_file"]);
});

test("csv import validates file size", function () {
    // Create a file larger than 10MB
    $largeContent = str_repeat("title\n", 200000); // ~1.2MB of data
    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "large.csv",
        $largeContent
    )->size(11000); // 11MB

    actingAs($this->user)
        ->post(route("tasks.import.csv"), ["csv_file" => $file])
        ->assertSessionHasErrors(["csv_file"]);
});

test("csv import handles various date formats", function () {
    $csvContent = "title,due date" . PHP_EOL
        . "Task 1,2026-12-31" . PHP_EOL
        . "Task 2,2026/06/15" . PHP_EOL
        . "Task 3,June 15 2026" . PHP_EOL
        . "Task 4,15-06-2026" . PHP_EOL;

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "dates.csv",
        $csvContent
    );

    actingAs($this->user)
        ->post(route("tasks.import.csv"), ["csv_file" => $file])
        ->assertRedirect(route("tasks.index"));

    $this->assertDatabaseHas("tasks", ["title" => "Task 1"]);
    $this->assertDatabaseHas("tasks", ["title" => "Task 2"]);
    $this->assertDatabaseHas("tasks", ["title" => "Task 3"]);
    $this->assertDatabaseHas("tasks", ["title" => "Task 4"]);
});

test("csv import handles Windows line endings", function () {
    // Windows uses \r\n instead of \n
    $csvContent = "title,description\r\n"
        . "Task 1,Description 1\r\n"
        . "Task 2,Description 2\r\n";

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "windows.csv",
        $csvContent
    );

    actingAs($this->user)
        ->post(route("tasks.import.csv"), ["csv_file" => $file])
        ->assertRedirect(route("tasks.index"))
        ->assertSessionHas("success");

    $this->assertDatabaseHas("tasks", ["title" => "Task 1"]);
    $this->assertDatabaseHas("tasks", ["title" => "Task 2"]);
});

test("csv import handles CSV with quoted fields", function () {
    $csvContent = 'title,description' . PHP_EOL
        . '"Task with, comma","Description with ""quotes"""' . PHP_EOL
        . 'Simple Task,Simple description' . PHP_EOL;

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "quoted.csv",
        $csvContent
    );

    actingAs($this->user)
        ->post(route("tasks.import.csv"), ["csv_file" => $file])
        ->assertRedirect(route("tasks.index"))
        ->assertSessionHas("success");

    $task = Task::where("title", "Task with, comma")->first();
    expect($task)->not->toBeNull();
    expect($task->description)->toBe('Description with "quotes"');
});

test("csv import preserves task order from CSV", function () {
    $csvContent = "title" . PHP_EOL
        . "Zebra Task" . PHP_EOL
        . "Alpha Task" . PHP_EOL
        . "Middle Task" . PHP_EOL;

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        "order.csv",
        $csvContent
    );

    actingAs($this->user)
        ->post(route("tasks.import.csv"), ["csv_file" => $file])
        ->assertRedirect(route("tasks.index"));

    // All tasks should be created regardless of order
    $this->assertDatabaseHas("tasks", ["title" => "Zebra Task"]);
    $this->assertDatabaseHas("tasks", ["title" => "Alpha Task"]);
    $this->assertDatabaseHas("tasks", ["title" => "Middle Task"]);

    // Check they were created in order (by ID)
    $tasks = Task::where("user_id", $this->user->id)->get();
    expect($tasks->pluck("title")->toArray())->toBe(["Zebra Task", "Alpha Task", "Middle Task"]);
});
