<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class UpdateProjectMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && Gate::allows('manageMembers', $project);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['role' => ['required', 'string', Rule::in(['lead', 'member'])]];
    }
}
