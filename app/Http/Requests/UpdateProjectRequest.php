<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && Gate::allows('update', $project);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $project = $this->route('project');
        $id = $project instanceof Project ? $project->id : 0;

        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'key' => ['required', 'string', 'regex:/^[A-Za-z]{2,10}$/', Rule::unique('projects', 'key')->ignore($id)],
            'status' => ['required', 'string', 'in:active,on_hold,completed,archived'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
