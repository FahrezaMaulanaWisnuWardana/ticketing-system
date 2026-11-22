<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\IntegrationConnection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class LinkTrelloItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $connection = $this->route('connection');

        return $connection instanceof IntegrationConnection && Gate::allows('manage', $connection);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'pairs' => ['required', 'array'],
            'pairs.*.ticket_id' => ['required', 'integer'],
            'pairs.*.external_id' => ['required', 'string'],
        ];
    }
}
