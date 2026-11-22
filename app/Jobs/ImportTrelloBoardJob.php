<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Integrations\Trello\TrelloImporter;
use App\Models\ImportRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ImportTrelloBoardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $importRunId,
        public readonly bool $updateExisting = false,
        public readonly bool $includeArchived = false,
    ) {}

    public function handle(TrelloImporter $importer): void
    {
        $run = ImportRun::find($this->importRunId);
        if ($run === null || $run->status === 'completed') {
            return;
        }
        $importer->import($run, $this->updateExisting, $this->includeArchived);
    }
}
