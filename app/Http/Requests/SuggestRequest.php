<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SuggestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:120']];
    }

    protected function prepareForValidation(): void
    {
        $q = trim(strip_tags((string) $this->input('q', '')));
        $this->merge(['q' => $q === '' ? null : mb_substr($q, 0, 120)]);
    }
}
