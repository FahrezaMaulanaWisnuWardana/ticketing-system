<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', User::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return Rules::userCreate();
    }
}
