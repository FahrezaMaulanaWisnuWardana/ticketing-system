<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Project::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return Rules::project();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['key.regex' => __('validation.key_format')];
    }
}
