<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TicketKind;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && Gate::allows('manageCategories', $project);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'kind' => ['required', 'string', Rule::enum(TicketKind::class)],
            'form_schema' => ['nullable', 'array'],
            'form_schema.*.name' => ['required_with:form_schema', 'string', 'max:64'],
            'form_schema.*.label' => ['required_with:form_schema', 'string', 'max:120'],
            'form_schema.*.type' => ['required_with:form_schema', 'string', 'in:text,textarea,select,checkbox,file'],
            'form_schema.*.required' => ['sometimes', 'boolean'],
            'default_assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
