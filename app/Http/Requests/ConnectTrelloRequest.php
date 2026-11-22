<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\IntegrationConnection;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class ConnectTrelloRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && Gate::allows('connect', [IntegrationConnection::class, $project]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:255'],
            'token' => ['required', 'string', 'max:255'],
            'board_id' => ['required', 'string', 'max:255'],
            'mode' => ['required', Rule::in(['import_only', 'mirror', 'two_way'])],
        ];
    }
}
