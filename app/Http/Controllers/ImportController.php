<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\Label;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ImportController extends Controller
{
    public function importTasksCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $file = $request->file('csv_file');
        $handle = fopen($file->getPathname(), 'r');

        if ($handle === false) {
            return redirect()->back()->withErrors(['csv_file' => 'Unable to read the CSV file.']);
        }

        // Read and skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return redirect()->back()->withErrors(['csv_file' => 'CSV file is empty or invalid.']);
        }

        // Normalize headers
        $headers = array_map('strtolower', array_map('trim', $headers));

        // Validate required headers
        if (!in_array('title', $headers)) {
            fclose($handle);
            return redirect()->back()->withErrors(['csv_file' => 'CSV must contain a "title" column.']);
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($handle, $headers, $user, &$imported, &$skipped, &$errors) {
            $rowNumber = 1; // Header is row 1
            $headerCount = count($headers);

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                // Ensure row has exactly the same number of elements as header
                if (count($row) > $headerCount) {
                    $row = array_slice($row, 0, $headerCount);
                } elseif (count($row) < $headerCount) {
                    $row = array_pad($row, $headerCount, '');
                }

                $data = array_combine($headers, $row);

                if ($data === false || empty($data['title'] ?? '')) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: Missing title, skipped.";
                    continue;
                }

                try {
                    // Map status
                    $status = TaskStatus::tryFrom(strtolower($data['status'] ?? 'pending')) ?? TaskStatus::Pending;

                    // Map priority
                    $priority = TaskPriority::tryFrom(strtolower($data['priority'] ?? 'medium')) ?? TaskPriority::Medium;

                    // Find or create category
                    $categoryId = null;
                    if (!empty($data['category'] ?? '')) {
                        $category = Category::where('user_id', $user->id)
                            ->where('name', $data['category'])
                            ->first();

                        if (!$category) {
                            $category = new Category([
                                'name' => $data['category'],
                                'color' => '#6366f1', // Default indigo color
                            ]);
                            $category->user_id = $user->id;
                            $category->save();
                        }
                        $categoryId = $category->id;
                    }

                    // Parse due date
                    $dueAt = null;
                    if (!empty($data['due date'] ?? '')) {
                        $dueAt = \Carbon\Carbon::parse($data['due date'])->format('Y-m-d H:i:s');
                    }

                    // Create task
                    $task = $user->tasks()->create([
                        'title' => $data['title'],
                        'description' => $data['description'] ?? null,
                        'status' => $status,
                        'priority' => $priority,
                        'category_id' => $categoryId,
                        'due_at' => $dueAt,
                    ]);

                    // Handle labels (comma-separated)
                    if (!empty($data['labels'] ?? '')) {
                        $labelNames = array_map('trim', explode(',', $data['labels']));
                        foreach ($labelNames as $labelName) {
                            if (!empty($labelName)) {
                                $label = Label::where('user_id', $user->id)
                                    ->where('name', $labelName)
                                    ->first();

                                if (!$label) {
                                    $label = new Label([
                                        'name' => $labelName,
                                        'color' => '#6366f1', // Default indigo color
                                    ]);
                                    $label->user_id = $user->id;
                                    $label->save();
                                }
                                $task->labels()->attach($label);
                            }
                        }
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                    Log::error("CSV import error for user {$user->id}: " . $e->getMessage());
                }
            }
        });

        fclose($handle);

        $message = "Import complete: {$imported} task(s) imported.";
        if ($skipped > 0) {
            $message .= " {$skipped} row(s) skipped.";
        }

        if (!empty($errors)) {
            session()->put('import_errors', array_slice($errors, 0, 10)); // Store first 10 errors
        }

        return redirect()->route('tasks.index')
            ->with('success', $message);
    }

    public function getImportErrors(): \Illuminate\Http\JsonResponse
    {
        $errors = session()->get('import_errors', []);
        session()->forget('import_errors');

        return response()->json(['errors' => $errors]);
    }
}
