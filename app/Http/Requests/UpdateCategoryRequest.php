<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof Category && Gate::allows('update', $category);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'form_schema' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'default_assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
