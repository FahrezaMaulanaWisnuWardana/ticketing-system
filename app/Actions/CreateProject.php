<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Enums\ProjectTemplate;
use App\Enums\TicketKind;
use App\Models\Category;
use App\Models\Label;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateProject
{
    /** @param array<string, mixed> $data */
    public function __invoke(User $actor, array $data): Project
    {
        $template = ProjectTemplate::from($data['template'] ?? ProjectTemplate::Blank->value);

        return DB::transaction(static function () use ($actor, $data, $template): Project {
            $project = Project::create([
                'key' => strtoupper($data['key']),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'owner_id' => $actor->id,
                'status' => ProjectStatus::Active,
                'template' => $template,
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
            ]);

            $project->members()->attach($actor->id, ['role' => ProjectRole::Lead->value]);

            $categories = match ($template) {
                ProjectTemplate::Helpdesk => [
                    ['name' => 'IT Support', 'kind' => TicketKind::Request],
                    ['name' => 'Access Request', 'kind' => TicketKind::Request],
                    ['name' => 'Hardware', 'kind' => TicketKind::Request],
                ],
                ProjectTemplate::Dev => [
                    ['name' => 'Bug', 'kind' => TicketKind::Dev],
                    ['name' => 'Feature', 'kind' => TicketKind::Dev],
                    ['name' => 'Task', 'kind' => TicketKind::Dev],
                ],
                ProjectTemplate::Blank => [],
            };

            foreach ($categories as $sort => $category) {
                Category::create([
                    'project_id' => $project->id,
                    'name' => $category['name'],
                    'kind' => $category['kind'],
                    'form_schema' => [],
                    'sort' => $sort,
                ]);
            }

            $labels = match ($template) {
                ProjectTemplate::Helpdesk => ['support', 'access', 'hardware'],
                ProjectTemplate::Dev => ['bug', 'feature', 'task'],
                ProjectTemplate::Blank => [],
            };
            foreach ($labels as $label) {
                Label::create(['project_id' => $project->id, 'name' => $label, 'color' => 'slate']);
            }

            return $project->load('owner', 'members');
        });
    }
}
