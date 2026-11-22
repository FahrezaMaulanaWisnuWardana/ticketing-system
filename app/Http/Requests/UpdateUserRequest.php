<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && Gate::allows('update', $user);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'role' => ['sometimes', 'string', Rule::enum(Role::class)],
            'is_active' => ['sometimes', 'boolean'],
            'timezone' => ['sometimes', 'string', 'max:64'],
            'locale' => ['sometimes', 'string', 'in:id,en'],
        ];
    }
}
