<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isAgent() || $user->isAdmin());
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['assign', 'status'])],
            'tickets' => ['required', 'array', 'min:1', 'max:100'],
            'tickets.*' => ['required', 'integer', 'min:1'],
            'assignee_id' => ['required_if:action,assign', 'nullable', 'integer', 'exists:users,id'],
            'status' => ['required_if:action,status', 'nullable', 'string', Rule::enum(TicketStatus::class)],
        ];
    }
}
