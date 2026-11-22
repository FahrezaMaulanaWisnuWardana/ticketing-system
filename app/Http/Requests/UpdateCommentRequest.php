<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $comment = $this->route('comment');

        return $comment instanceof Comment && Gate::allows('update', $comment);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:20000']];
    }
}
