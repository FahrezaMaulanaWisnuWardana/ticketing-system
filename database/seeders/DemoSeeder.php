<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\TicketNumbering;
use App\Enums\Priority;
use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Enums\ProjectTemplate;
use App\Enums\TicketKind;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'staging'])) {
            return;
        }

        $admin = User::firstOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Admin', 'password' => Hash::make('password123'), 'role' => 'admin', 'is_active' => true,
        ]);
        $lead = User::firstOrCreate(['email' => 'lead@example.com'], [
            'name' => 'Project Lead', 'password' => Hash::make('password123'), 'role' => 'agent', 'is_active' => true,
        ]);
        $agents = collect([$lead]);
        for ($i = 1; $i <= 3; $i++) {
            $agents->push(User::firstOrCreate(['email' => "agent{$i}@example.com"], [
                'name' => "Agent {$i}", 'password' => Hash::make('password123'), 'role' => 'agent', 'is_active' => true,
            ]));
        }
        $requesters = collect();
        for ($i = 1; $i <= 5; $i++) {
            $requesters->push(User::firstOrCreate(['email' => "user{$i}@example.com"], [
                'name' => "User {$i}", 'password' => Hash::make('password123'), 'role' => 'requester', 'is_active' => true,
            ]));
        }

        $helpdesk = Project::firstOrCreate(['key' => 'IT'], [
            'name' => 'IT Helpdesk', 'owner_id' => $admin->id, 'status' => ProjectStatus::Active,
            'template' => ProjectTemplate::Helpdesk, 'description' => 'Internal helpdesk queue',
        ]);
        $dev = Project::firstOrCreate(['key' => 'APP'], [
            'name' => 'Application Development', 'owner_id' => $lead->id, 'status' => ProjectStatus::Active,
            'template' => ProjectTemplate::Dev, 'description' => 'Product development',
        ]);

        foreach ([$helpdesk, $dev] as $project) {
            foreach ($agents as $agent) {
                $project->members()->syncWithoutDetaching([
                    $agent->id => ['role' => ($agent->is($lead) && $project->is($dev) ? ProjectRole::Lead : ProjectRole::Member)->value],
                ]);
            }

            Category::firstOrCreate(['project_id' => $project->id, 'name' => 'General'], [
                'kind' => $project->is($helpdesk) ? TicketKind::Request : TicketKind::Dev,
                'form_schema' => [], 'sort' => 0,
            ]);
        }

        $statuses = [TicketStatus::New, TicketStatus::Backlog, TicketStatus::Todo, TicketStatus::InProgress,
            TicketStatus::InReview, TicketStatus::WaitingRequester, TicketStatus::Done, TicketStatus::Closed];

        for ($i = 0; $i < 60; $i++) {
            $project = $i % 3 === 0 ? $helpdesk : $dev;
            $kind = $project->is($helpdesk) ? TicketKind::Request : TicketKind::Dev;
            $number = TicketNumbering::next($project->id);
            $status = $statuses[$i % count($statuses)];

            Ticket::create([
                'project_id' => $project->id,
                'number' => $number,
                'kind' => $kind,
                'type' => 'task',
                'title' => fake()->sentence(6),
                'description' => fake()->paragraph(),
                'status' => $status,
                'priority' => [Priority::Urgent, Priority::High, Priority::Normal, Priority::Low][$i % 4],
                'reporter_id' => $requesters->random()->id,
                'requester_id' => $requesters->random()->id,
                'assignee_id' => $agents->random()->id,
                'rank' => str_pad((string) $number, 10, '0', STR_PAD_LEFT),
                'resolved_at' => $status->isFinal() ? now()->subDays(2) : null,
            ]);
        }

        DB::table('settings')->updateOrInsert(['key' => 'app.initialized'], ['value' => json_encode(['at' => now()->toIso8601String()])]);
    }
}
