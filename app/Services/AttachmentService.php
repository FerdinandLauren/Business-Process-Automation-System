<?php

namespace App\Services;

use App\Models\RequestAttachment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttachmentService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function store(
        UploadedFile $file,
        ServiceRequest $request,
        User $actor,
        ?string $fieldKey = null,
    ): RequestAttachment {
        $maxKb = (int) config('bpa.attachments.max_kb');
        $extensions = config('bpa.attachments.extensions', []);
        $mimeTypes = config('bpa.attachments.mime_types', []);

        $extension = strtolower($file->getClientOriginalExtension());
        $detectedMime = $file->getMimeType() ?: 'application/octet-stream';

        if (! in_array($extension, $extensions, true)) {
            throw ValidationException::withMessages([
                'files' => 'Files of type .'.$extension.' are not accepted. Allowed: '.implode(', ', $extensions).'.',
            ]);
        }

        if (! in_array($detectedMime, $mimeTypes, true)) {
            throw ValidationException::withMessages([
                'files' => 'The file content type ('.$detectedMime.') is not accepted.',
            ]);
        }

        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages([
                'files' => 'Each file must be smaller than '.round($maxKb / 1024, 1).' MB.',
            ]);
        }

        $storedName = Str::uuid()->toString().'.'.$extension;
        $month = $request->created_at?->format('Y/m') ?? now()->format('Y/m');
        $directory = trim((string) config('bpa.attachments.directory'), '/').'/'.$month;
        $path = $file->storeAs($directory, $storedName, config('bpa.attachments.disk'));

        $attachment = RequestAttachment::create([
            'request_id' => $request->getKey(),
            'uploaded_by' => $actor->getKey(),
            'field_key' => $fieldKey,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $storedName,
            'mime_type' => $detectedMime,
            'file_size' => $file->getSize(),
            'storage_path' => $path,
        ]);

        $this->audit->log('attachment.uploaded', $request, [
            'attachment_id' => $attachment->getKey(),
            'filename' => $attachment->original_filename,
            'size' => $attachment->file_size,
        ], $actor);

        return $attachment;
    }

    public function delete(RequestAttachment $attachment, User $actor): void
    {
        $request = $attachment->request;

        if ((int) $request->requester_id !== (int) $actor->getKey() || ! $request->isEditable()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('This attachment can no longer be removed.');
        }

        Storage::disk(config('bpa.attachments.disk'))->delete($attachment->storage_path);

        $this->audit->log('attachment.removed', $request, [
            'filename' => $attachment->original_filename,
        ], $actor);

        $attachment->delete();
    }

    public function absolutePath(RequestAttachment $attachment): ?string
    {
        $disk = Storage::disk(config('bpa.attachments.disk'));

        if (! $disk->exists($attachment->storage_path)) {
            return null;
        }

        return $disk->path($attachment->storage_path);
    }
}
