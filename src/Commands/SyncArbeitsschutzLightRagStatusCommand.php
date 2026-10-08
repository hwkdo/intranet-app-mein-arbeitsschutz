<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Commands;

use Hwkdo\IntranetAppMeinArbeitsschutz\Enums\ArbeitsschutzLightRagStatus;
use Hwkdo\IntranetAppMeinArbeitsschutz\Jobs\SyncArbeitsschutzLightRagTrack;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\DocumentLightRagState;
use Hwkdo\IntranetAppMeinArbeitsschutz\Services\LightRagArbeitsschutzClient;
use Illuminate\Console\Command;
use Throwable;

class SyncArbeitsschutzLightRagStatusCommand extends Command
{
    protected $signature = 'arbeitsschutz:sync-lightrag-status';

    protected $description = 'Schreibt den aktuellen LightRAG-Status der offenen Arbeitsschutz-Dokumente in die Datenbank';

    public function handle(LightRagArbeitsschutzClient $client): int
    {
        $written = 0;
        $open = 0;

        DocumentLightRagState::query()
            ->where('status', ArbeitsschutzLightRagStatus::Processing)
            ->whereNotNull('track_id')
            ->orderBy('id')
            ->each(function (DocumentLightRagState $state) use ($client, &$written, &$open): void {
                try {
                    (new SyncArbeitsschutzLightRagTrack($state->document_id))->handle($client);
                } catch (Throwable $exception) {
                    $this->error('Dokument '.$state->document_id.': '.$exception->getMessage());
                    $open++;

                    return;
                }

                $state->refresh();
                if ($state->status === ArbeitsschutzLightRagStatus::Processing) {
                    $open++;

                    return;
                }

                $written++;
            });

        $this->info($written.' Dokumente aktualisiert, '.$open.' noch in Arbeit.');

        return self::SUCCESS;
    }
}
