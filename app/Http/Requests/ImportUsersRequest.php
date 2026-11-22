<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class ImportUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('import', User::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048']];
    }
}
