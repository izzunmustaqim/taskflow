<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attachment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AttachmentService
{
    /**
     * Allowed MIME types for uploads.
     *
     * @var list<string>
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
        'application/zip',
    ];

    /**
     * Allowed file extensions.
     *
     * @var list<string>
     */
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx',
        'txt', 'csv', 'zip',
    ];

    /**
     * Maximum file size in bytes (10MB).
     */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Upload a file attachment to a task.
     */
    public function upload(Task $task, User $user, UploadedFile $file): Attachment
    {
        // Validate file
        $this->validateFile($file);

        // Generate unique stored name
        $extension = $file->getClientOriginalExtension();
        $storedName = uniqid('att_', true) . '.' . $extension;
        $path = $file->storeAs('attachments', $storedName, 'local');

        return Attachment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
        ]);
    }

    /**
     * Download an attachment file.
     */
    public function download(Attachment $attachment): StreamedResponse
    {
        return Storage::disk('local')->download(
            $attachment->path,
            $attachment->original_name
        );
    }

    /**
     * Delete an attachment and its file.
     */
    public function delete(Attachment $attachment): bool
    {
        // Delete the file from storage
        if (Storage::disk('local')->exists($attachment->path)) {
            Storage::disk('local')->delete($attachment->path);
        }

        return (bool) $attachment->delete();
    }

    /**
     * Validate the uploaded file.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateFile(UploadedFile $file): void
    {
        validator([
            'file' => [
                'required',
                'file',
                'max:' . (self::MAX_FILE_SIZE / 1024), // Convert to KB for Laravel
                function ($attribute, $value, $fail): void {
                    $extension = strtolower($value->getClientOriginalExtension());
                    $mimeType = $value->getMimeType();

                    // Check by extension first (more reliable for fake files)
                    if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                        $fail('The file type is not allowed.');
                        return;
                    }

                    // Also check MIME type if it's not a generic type
                    if ($mimeType !== 'application/octet-stream'
                        && !in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
                        $fail('The file type is not allowed.');
                    }
                },
            ],
        ])->validate(['file' => $file]);
    }
}
