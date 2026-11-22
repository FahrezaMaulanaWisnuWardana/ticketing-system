<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TrelloConnectionStatus;
use App\Enums\TrelloMode;
use App\Enums\TrelloPrompt;
use App\Http\Requests\ConnectTrelloRequest;
use App\Http\Requests\LinkTrelloItemsRequest;
use App\Http\Requests\TrelloImportRequest;
use App\Integrations\Contracts\IntegrationAdapter;
use App\Integrations\Trello\ConnectTrello;
use App\Integrations\Trello\DisconnectTrello;
use App\Integrations\Trello\TrelloConnectionService;
use App\Integrations\Trello\TrelloSetupService;
use App\Jobs\ImportTrelloBoardJob;
use App\Jobs\MirrorTicketToTrelloJob;
use App\Jobs\ReconcileTrelloJob;
use App\Models\ExternalLink;
use App\Models\ImportRun;
use App\Models\IntegrationConnection;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class TrelloController extends Controller
{
    public function settings(Project $project): View
    {
        Gate::authorize('connect', [IntegrationConnection::class, $project]);
        $connection = IntegrationConnection::where('project_id', $project->id)->first();

        return view('trello.settings', [
            'project' => $project,
            'connection' => $connection,
            'imports' => $connection?->importRuns()->latest()->get() ?? collect(),
            'conflicts' => $connection?->conflicts()->whereNull('resolved_at')->get() ?? collect(),
        ]);
    }

    public function connectForm(Project $project): View
    {
        Gate::authorize('connect', [IntegrationConnection::class, $project]);

        return view('trello.connect', ['project' => $project]);
    }

    public function connect(ConnectTrelloRequest $request, Project $project, ConnectTrello $connect): RedirectResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            abort(401);
        }
        $connection = $connect($actor, $project, $request->validated());
        $this->registerWebhook($connection);
        app(TrelloSetupService::class)->applyExistingChoice(
            $connection,
            (string) $request->input('existing', 'import'),
            $actor,
        );

        return redirect()->route('trello.settings', $project)->with('status', __('trello.connected'));
    }

    public function update(Request $request, Project $project, IntegrationConnection $connection): RedirectResponse
    {
        Gate::authorize('manage', $connection);
        $validated = $request->validate([
            'mode' => ['required', 'in:import_only,mirror,two_way'],
            'mirror_helpdesk' => ['sometimes', 'boolean'],
        ]);
        $connection->forceFill([
            'mode' => TrelloMode::from($validated['mode']),
            'mirror_helpdesk' => (bool) ($validated['mirror_helpdesk'] ?? false),
            'status' => TrelloConnectionStatus::Active,
        ])->save();
        if ($connection->mode !== TrelloMode::ImportOnly) {
            $this->registerWebhook($connection);
        }

        return back()->with('status', __('trello.updated'));
    }

    public function pause(IntegrationConnection $connection): RedirectResponse
    {
        Gate::authorize('manage', $connection);
        $connection->forceFill(['status' => TrelloConnectionStatus::Paused])->save();

        return back()->with('status', __('trello.paused'));
    }

    public function resume(IntegrationConnection $connection): RedirectResponse
    {
        Gate::authorize('manage', $connection);
        $connection->forceFill(['status' => TrelloConnectionStatus::Active])->save();
        ReconcileTrelloJob::dispatch($connection->id)->onQueue('integrations');

        return back()->with('status', __('trello.resumed'));
    }

    public function disconnect(Request $request, IntegrationConnection $connection, DisconnectTrello $disconnect): RedirectResponse
    {
        Gate::authorize('manage', $connection);
        $disconnect($connection, $request->boolean('forget_links'));

        return redirect()->route('trello.settings', $connection->project_id)
            ->with('status', __('trello.disconnected'));
    }

    public function import(TrelloImportRequest $request, IntegrationConnection $connection): RedirectResponse
    {
        Gate::authorize('manage', $connection);
        $run = ImportRun::create([
            'connection_id' => $connection->id, 'started_by' => (int) auth()->id(),
            'status' => 'pending',
        ]);
        ImportTrelloBoardJob::dispatch(
            $run->id,
            $request->boolean('update_existing'),
            $request->boolean('include_archived'),
        )->onQueue('integrations');

        return back()->with('status', __('trello.import_queued'));
    }

    public function importReport(ImportRun $run): View
    {
        Gate::authorize('view', $run->connection);

        return view('trello.import-report', ['run' => $run]);
    }

    public function link(LinkTrelloItemsRequest $request, IntegrationConnection $connection): RedirectResponse
    {
        Gate::authorize('manage', $connection);
        foreach ((array) $request->input('pairs', []) as $pair) {
            if (! is_array($pair) || ! isset($pair['ticket_id'], $pair['external_id'])) {
                continue;
            }
            $ticket = Ticket::find((int) $pair['ticket_id']);
            if ($ticket === null || $ticket->project_id !== $connection->project_id) {
                continue;
            }
            ExternalLink::updateOrCreate(
                ['provider' => 'trello', 'external_id' => (string) $pair['external_id']],
                ['ticket_id' => $ticket->id, 'external_state' => 'ok'],
            );
        }

        return back()->with('status', __('trello.linked'));
    }

    public function resync(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('update', $ticket);
        MirrorTicketToTrelloJob::dispatch($ticket->id)->onQueue('integrations');

        return back()->with('status', __('trello.resync_queued'));
    }

    public function unlink(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('update', $ticket);
        ExternalLink::where('ticket_id', $ticket->id)->where('provider', 'trello')->delete();

        return back()->with('status', __('trello.unlinked'));
    }

    public function prompt(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);
        $validated = $request->validate(['prompt' => ['required', 'in:no,later']]);
        $project->forceFill([
            'trello_prompt' => $validated['prompt'] === 'no' ? TrelloPrompt::No : TrelloPrompt::Later,
            'trello_prompt_at' => now(),
        ])->save();

        return redirect()->route('projects.show', $project);
    }

    public function dismissBanner(Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);
        $key = 'trello.banner_dismissed.'.$project->id;
        Setting::set($key, (int) Setting::get($key, 0) + 1);
        $project->forceFill(['trello_prompt_at' => now()])->save();

        return back();
    }

    private function registerWebhook(IntegrationConnection $connection): void
    {
        try {
            $service = app(TrelloConnectionService::class);
            $result = app(IntegrationAdapter::class)->registerWebhook(
                (string) config('trello.callback_url').'/'.$connection->id,
                $service->credentials($connection),
            );
            $connection->forceFill(['webhook_id' => $result['id'] ?? null])->save();
        } catch (\Throwable $exception) {
            app(TrelloConnectionService::class)->log($connection, 'warning', 'webhook_register_failed', ['message' => $exception->getMessage()]);
        }
    }
}
