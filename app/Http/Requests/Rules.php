<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Priority;
use App\Enums\ProjectTemplate;
use App\Enums\Role;
use App\Enums\TicketKind;
use App\Enums\TicketStatus;
use Illuminate\Validation\Rule;

final class Rules
{
    /** @return array<string, mixed> */
    public static function ticketCreate(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'kind' => ['required', 'string', Rule::enum(TicketKind::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:20000'],
            'priority' => ['nullable', 'string', Rule::enum(Priority::class)],
            'type' => ['nullable', 'string', 'max:32'],
            'severity' => ['nullable', 'string', 'max:16'],
            'environment' => ['nullable', 'string', 'max:64'],
            'requester_id' => ['nullable', 'integer', 'exists:users,id'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'attachments' => ['nullable', 'array', 'max:'.(int) config('ticketing.uploads.max_files')],
            'attachments.*' => ['file', 'max:'.(int) config('ticketing.uploads.max_kb')],
        ];
    }

    /** @return array<string, mixed> */
    public static function ticketUpdate(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'priority' => ['sometimes', 'string', Rule::enum(Priority::class)],
            'severity' => ['sometimes', 'nullable', 'string', 'max:16'],
            'environment' => ['sometimes', 'nullable', 'string', 'max:64'],
            'status' => ['sometimes', 'string', Rule::enum(TicketStatus::class)],
            'assignee_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'blocked' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, mixed> */
    public static function comment(): array
    {
        return [
            'body' => ['required', 'string', 'max:20000'],
            'is_internal' => ['sometimes', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:'.(int) config('ticketing.uploads.max_files')],
            'attachments.*' => ['file', 'max:'.(int) config('ticketing.uploads.max_kb')],
        ];
    }

    /** @return array<string, mixed> */
    public static function project(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'key' => ['required', 'string', 'regex:/^[A-Za-z]{2,10}$/', 'unique:projects,key'],
            'description' => ['nullable', 'string', 'max:5000'],
            'template' => ['nullable', 'string', Rule::enum(ProjectTemplate::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /** @return array<string, mixed> */
    public static function userCreate(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', Rule::enum(Role::class)],
        ];
    }
}
