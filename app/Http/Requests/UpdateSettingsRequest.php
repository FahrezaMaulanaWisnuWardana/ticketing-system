<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'auto_close_days' => ['required', 'integer', 'min:1', 'max:90'],
            'reopen_days' => ['required', 'integer', 'min:1', 'max:90'],
        ];
    }
}
