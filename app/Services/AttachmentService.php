<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

final class AttachmentService
{
    /** @return array{disk: string, path: string, original_name: string, mime: string, size: int, sha256: string} */
    public function store(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            throw new InvalidArgumentException('The upload failed.');
        }

        $maxBytes = (int) config('ticketing.uploads.max_kb') * 1024;
        if ($file->getSize() > $maxBytes) {
            throw new InvalidArgumentException(__('validation.upload_too_large', ['max' => (int) config('ticketing.uploads.max_kb')]));
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (in_array($extension, (array) config('ticketing.uploads.blocked_extensions'), true)) {
            throw new InvalidArgumentException(__('attachments.blocked_extension'));
        }

        $contents = @file_get_contents($file->getRealPath());
        if ($contents === false) {
            throw new RuntimeException('Unable to read uploaded file.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->buffer($contents);
        if (! in_array($mime, (array) config('ticketing.uploads.allowed_mimes'), true)) {
            throw new InvalidArgumentException(__('attachments.invalid_type'));
        }

        $disk = (string) config('filesystems.default') === 's3' ? 's3' : 'attachments';
        $path = 'tickets/'.date('Y/m/d').'/'.bin2hex(random_bytes(16)).'.'.$this->safeExtension($extension, $mime);

        if (! Storage::disk($disk)->put($path, $contents, 'private')) {
            throw new RuntimeException('Unable to store uploaded file.');
        }

        return [
            'disk' => $disk,
            'path' => $path,
            'original_name' => $this->sanitizeName($file->getClientOriginalName()),
            'mime' => $mime,
            'size' => strlen($contents),
            'sha256' => hash('sha256', $contents),
        ];
    }

    public function delete(string $disk, string $path): void
    {
        Storage::disk($disk)->delete($path);
    }

    private function safeExtension(string $extension, string $mime): string
    {
        $map = [
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp',
            'application/pdf' => 'pdf', 'text/plain' => 'txt', 'text/csv' => 'csv',
            'application/zip' => 'zip', 'application/x-zip-compressed' => 'zip',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        ];

        return $map[$mime] ?? (preg_match('/^[a-z0-9]{1,8}$/', $extension) === 1 ? $extension : 'bin');
    }

    private function sanitizeName(string $name): string
    {
        $name = str_replace(['/', '\\', "\0"], '-', $name);

        return mb_substr($name, 0, 200);
    }
}
