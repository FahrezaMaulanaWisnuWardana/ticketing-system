<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

final class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket && Gate::allows('attach', $ticket);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => [
                'required', 'file', 'max:'.(int) config('ticketing.uploads.max_kb'),
                static function (string $attribute, mixed $value, callable $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }
                    $extension = strtolower((string) $value->getClientOriginalExtension());
                    if (in_array($extension, (array) config('ticketing.uploads.blocked_extensions'), true)) {
                        $fail(__('attachments.blocked_extension'));
                    }
                },
            ],
        ];
    }
}
