<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\CannedReply;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class UpdateCannedReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reply = $this->route('reply');

        return $reply instanceof CannedReply && Gate::allows('update', $reply);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:20000'],
        ];
    }
}
