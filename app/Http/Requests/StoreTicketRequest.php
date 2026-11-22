<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TicketKind;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

final class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Ticket::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return Rules::ticketCreate();
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(static function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            if ($validator->getValue('kind') === TicketKind::Dev->value && ! Gate::allows('createDev', Ticket::class)) {
                $validator->errors()->add('kind', __('tickets.cannot_create_dev'));
            }
        });
    }
}
