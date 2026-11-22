<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\RequestTypeCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListMyRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'creator' => ['required', Rule::in(['mine', 'all'])],
            'status' => ['required', Rule::in(['open', 'done', 'all'])],
            'type' => ['nullable', 'string', Rule::in(array_keys(RequestTypeCatalog::all()))],
            'q' => ['nullable', 'string', 'max:120'],
            'sort' => ['required', Rule::in(['summary', 'status', 'created_at'])],
            'dir' => ['required', Rule::in(['asc', 'desc'])],
            'explicit_sort' => ['required', 'boolean'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Query strings are user input: fold anything unexpected back to a safe
     * default before validation instead of letting it reach the query builder.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $canSeeAll = $user !== null && $user->hasOrganizationAccess();

        $type = trim((string) $this->input('type', ''));
        $q = trim(strip_tags((string) $this->input('q', '')));

        $this->merge([
            'creator' => $canSeeAll && $this->input('creator') === 'all' ? 'all' : 'mine',
            'status' => in_array($this->input('status'), ['open', 'done', 'all'], true) ? $this->input('status') : 'open',
            'type' => RequestTypeCatalog::isValid($type) ? $type : null,
            'q' => $q === '' ? null : mb_substr($q, 0, 120),
            'sort' => in_array($this->input('sort'), ['summary', 'status', 'created_at'], true) ? $this->input('sort') : 'created_at',
            'dir' => $this->input('dir') === 'asc' ? 'asc' : 'desc',
            'explicit_sort' => $this->has('sort'),
        ]);
    }
}
