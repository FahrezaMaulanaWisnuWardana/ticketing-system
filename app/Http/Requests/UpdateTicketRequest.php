<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket && Gate::allows('update', $ticket);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return Rules::ticketUpdate();
    }
}
