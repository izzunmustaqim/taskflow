<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tasks Export</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f3f4f6; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .badge { padding: 2px 8px; border-radius: 4px; font-size: 10px; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-in_progress { background-color: #dbeafe; color: #1e40af; }
        .status-completed { background-color: #d1fae5; color: #065f46; }
        .status-archived { background-color: #e5e7eb; color: #374151; }
        .priority-low { background-color: #d1fae5; color: #065f46; }
        .priority-medium { background-color: #fef3c7; color: #92400e; }
        .priority-high { background-color: #fee2e2; color: #991b1b; }
        .priority-urgent { background-color: #fecaca; color: #7f1d1d; }
        .footer { text-align: center; font-size: 10px; color: #666; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Task Export - {{ now()->format("F j, Y") }}</h1>

    @if($filters)
    <p><strong>Filters Applied:</strong> {{ $filters }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Category</th>
                <th>Due Date</th>
                <th>Labels</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tasks as $task)
            <tr>
                <td>{{ $task->id }}</td>
                <td>{{ $task->title }}</td>
                <td><span class="badge status-{{ $task->status->value }}">{{ $task->status->label() }}</span></td>
                <td><span class="badge priority-{{ $task->priority->value }}">{{ $task->priority->label() }}</span></td>
                <td>{{ $task->category?->name ?? "-" }}</td>
                <td>{{ $task->due_at?->format("M j, Y") ?? "-" }}</td>
                <td>{{ $task->labels->pluck("name")->implode(", ") ?: "-" }}</td>
                <td>{{ $task->created_at->format("M j, Y") }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center;">No tasks found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format("F j, Y \a\t g:i A") }} | {{ $tasks->count() }} task(s)
    </div>
</body>
</html>
