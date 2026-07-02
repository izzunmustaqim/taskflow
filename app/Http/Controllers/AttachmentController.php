<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Task;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AttachmentController extends Controller
{
    public function __construct(
        private readonly AttachmentService $attachmentService,
    ) {}

    /**
     * Store a new attachment for a task.
     */
    public function store(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB max
                function ($attribute, $value, $fail): void {
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'zip'];
                    $extension = strtolower($value->getClientOriginalExtension());

                    if (!in_array($extension, $allowedExtensions, true)) {
                        $fail('The file type is not allowed. Allowed: ' . implode(', ', $allowedExtensions));
                    }
                },
            ],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $file = $request->file('file');

        if ($file === null) {
            return response()->json(['message' => 'No file provided.'], 400);
        }

        $attachment = $this->attachmentService->upload($task, $user, $file);

        return response()->json([
            'message' => 'File uploaded successfully.',
            'attachment' => [
                'id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
                'created_at' => $attachment->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Download an attachment.
     */
    public function download(Attachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment->task);

        return $this->attachmentService->download($attachment);
    }

    /**
     * Delete an attachment.
     */
    public function destroy(Attachment $attachment): JsonResponse
    {
        Gate::authorize('update', $attachment->task);

        $this->attachmentService->delete($attachment);

        return response()->json([
            'message' => 'Attachment deleted successfully.',
        ]);
    }
}
