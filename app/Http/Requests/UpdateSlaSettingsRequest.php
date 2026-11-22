<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSlaSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_start' => ['required', 'date_format:H:i'],
            'business_end' => ['required', 'date_format:H:i', 'after:business_start'],
            'workdays' => ['required', 'array', 'min:1'],
            'workdays.*' => ['integer', 'between:1,7'],
            'urgent_24x7' => ['sometimes', 'boolean'],
            'targets' => ['required', 'array'],
            'targets.*.response' => ['required', 'integer', 'min:1'],
            'targets.*.resolve' => ['required', 'integer', 'min:1'],
        ];
    }
}
