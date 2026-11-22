<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class StoreEpicRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && Gate::allows('manageSprints', $project);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $project = $this->route('project');
        $projectId = $project instanceof Project ? $project->id : 0;

        return [
            'key' => ['required', 'string', 'max:16', Rule::unique('epics', 'key')->where('project_id', $projectId)],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'color' => ['nullable', 'string', 'in:slate,blue,indigo,amber,emerald,rose'],
        ];
    }
}
