<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AgentQueueRequest extends FormRequest
{
    private const QUEUES = ['all_open', 'mine', 'unassigned', 'new', 'recently_resolved'];

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isAgent() || $user->isAdmin());
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'queue' => ['required', Rule::in(self::QUEUES)],
            'q' => ['nullable', 'string', 'max:120'],
            'sort' => ['required', Rule::in(['summary', 'status', 'created_at', 'updated_at', 'priority'])],
            'dir' => ['required', Rule::in(['asc', 'desc'])],
            'explicit_sort' => ['required', 'boolean'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $q = trim(strip_tags((string) $this->input('q', '')));

        $this->merge([
            'queue' => in_array($this->input('queue'), self::QUEUES, true) ? $this->input('queue') : 'all_open',
            'q' => $q === '' ? null : mb_substr($q, 0, 120),
            'sort' => in_array($this->input('sort'), ['summary', 'status', 'created_at', 'updated_at', 'priority'], true) ? $this->input('sort') : 'created_at',
            'dir' => $this->input('dir') === 'asc' ? 'asc' : 'desc',
            'explicit_sort' => $this->has('sort'),
        ]);
    }
}
