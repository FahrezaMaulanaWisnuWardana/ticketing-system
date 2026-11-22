<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class StoreLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && Gate::allows('manageLabels', $project);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $project = $this->route('project');
        $projectId = $project instanceof Project ? $project->id : 0;

        return [
            'name' => ['required', 'string', 'max:80', 'unique:labels,name,NULL,id,project_id,'.$projectId],
            'color' => ['required', 'string', 'in:slate,blue,indigo,amber,emerald,rose'],
        ];
    }
}
