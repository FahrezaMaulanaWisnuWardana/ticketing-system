<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\TicketNumbering;
use App\Enums\Priority;
use App\Enums\TicketKind;
use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ticket> */
final class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'kind' => TicketKind::Request,
            'type' => 'task',
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'status' => TicketStatus::New,
            'priority' => Priority::Normal,
            'reporter_id' => User::factory(),
            'requester_id' => null,
            'assignee_id' => null,
            'rank' => '5555555555',
            'blocked' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(static function (Ticket $ticket): void {
            $ticket->number = TicketNumbering::next((int) $ticket->project_id);
            $ticket->rank = str_pad((string) $ticket->number, 10, '0', STR_PAD_LEFT);
            if ($ticket->requester_id === null) {
                $ticket->requester_id = $ticket->reporter_id;
            }
        });
    }

    public function dev(): self
    {
        return $this->state(fn (): array => [
            'kind' => TicketKind::Dev,
            'status' => TicketStatus::Backlog,
        ]);
    }

    public function assigned(?User $user = null): self
    {
        return $this->state(fn (): array => ['assignee_id' => $user === null ? User::factory() : $user->id]);
    }
}
