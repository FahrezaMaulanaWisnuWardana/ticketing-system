<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\SlaCalculator;
use App\Domain\SlaConfiguration;
use App\Enums\Priority;
use App\Models\Holiday;
use App\Models\Setting;
use App\Models\Ticket;
use Carbon\CarbonImmutable;

final class SlaService
{
    public function configuration(): SlaConfiguration
    {
        /** @var array{start?: string, end?: string, days?: array<int, int>, timezone?: string} $hours */
        $hours = Setting::get('sla.business_hours', [
            'start' => config('ticketing.sla.business_start'),
            'end' => config('ticketing.sla.business_end'),
            'days' => config('ticketing.sla.workdays'),
            'timezone' => config('ticketing.sla.timezone'),
        ]);
        /** @var array<string, array{response: int, resolve: int}> $targets */
        $targets = Setting::get('sla.targets', config('ticketing.sla.targets'));

        $holidays = Holiday::query()->pluck('date')->map(
            static fn ($date): string => (string) $date,
        )->all();

        return new SlaConfiguration(
            timezone: (string) ($hours['timezone'] ?? config('ticketing.sla.timezone')),
            startHour: (int) explode(':', (string) ($hours['start'] ?? '08:00'))[0],
            endHour: (int) explode(':', (string) ($hours['end'] ?? '17:00'))[0],
            workdays: array_map('intval', (array) ($hours['days'] ?? config('ticketing.sla.workdays'))),
            holidays: $holidays,
            urgent24x7: (bool) Setting::get('sla.urgent_24x7', config('ticketing.sla.urgent_24x7')),
            targets: $targets,
        );
    }

    public function calculator(): SlaCalculator
    {
        return new SlaCalculator($this->configuration());
    }

    /** @return array{response: CarbonImmutable, resolve: CarbonImmutable} */
    public function dueDates(Ticket $ticket, ?Priority $priority = null): array
    {
        $calculator = $this->calculator();
        $priority ??= $ticket->priority;
        $from = CarbonImmutable::instance($ticket->created_at ?? now());

        return [
            'response' => $calculator->responseDue($priority, $from),
            'resolve' => $calculator->resolveDue($priority, $from),
        ];
    }

    public function apply(Ticket $ticket, ?Priority $priority = null): void
    {
        $due = $this->dueDates($ticket, $priority);
        $ticket->forceFill([
            'sla_response_due_at' => $due['response']->setTimezone('UTC'),
            'sla_resolve_due_at' => $due['resolve']->setTimezone('UTC'),
        ])->save();
    }

    public function pause(Ticket $ticket): void
    {
        if ($ticket->sla_paused_at === null) {
            $ticket->forceFill(['sla_paused_at' => now()])->save();
        }
    }

    public function resume(Ticket $ticket): void
    {
        if ($ticket->sla_paused_at === null) {
            return;
        }

        $configuration = $this->configuration();
        $calculator = $this->calculator();
        $pausedSeconds = $calculator->pausedSeconds(
            CarbonImmutable::instance($ticket->sla_paused_at),
            CarbonImmutable::now(),
            $configuration->is24x7($ticket->priority),
        );
        $pausedMinutes = (int) ceil($pausedSeconds / 60);
        $response = $ticket->sla_response_due_at;
        $resolve = $ticket->sla_resolve_due_at;

        $ticket->forceFill([
            'sla_paused_at' => null,
            'sla_paused_seconds' => ($ticket->sla_paused_seconds ?? 0) + $pausedSeconds,
            'sla_response_due_at' => $response?->copy()->addMinutes($pausedMinutes),
            'sla_resolve_due_at' => $resolve?->copy()->addMinutes($pausedMinutes),
        ])->save();
    }

    public function status(Ticket $ticket): string
    {
        if ($ticket->status->isFinal()) {
            return 'met';
        }
        $due = $ticket->first_response_at === null ? $ticket->sla_response_due_at : $ticket->sla_resolve_due_at;
        if ($due === null) {
            return 'on_track';
        }

        $totalMinutes = (int) $due->diffInMinutes(CarbonImmutable::instance($ticket->created_at));
        $remaining = (int) now()->diffInMinutes($due, false);
        if ($remaining < 0) {
            return 'breached';
        }
        if ($totalMinutes > 0 && (($totalMinutes - $remaining) / $totalMinutes) >= 0.8) {
            return 'at_risk';
        }

        return 'on_track';
    }
}
