<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Jobs;

use Hwkdo\IntranetAppMeinArbeitsschutz\Enums\ArbeitsschutzLightRagStatus;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\DocumentLightRagState;
use Hwkdo\IntranetAppMeinArbeitsschutz\Services\LightRagArbeitsschutzClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncArbeitsschutzLightRagTrack implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $documentId) {}

    public function handle(LightRagArbeitsschutzClient $client): void
    {
        if (app()->runningUnitTests() && ! config('intranet-app-mein-arbeitsschutz.lightrag.execute_in_tests')) {
            return;
        }

        $state = DocumentLightRagState::query()->where('document_id', $this->documentId)->first();
        if ($state === null || $state->status !== ArbeitsschutzLightRagStatus::Processing) {
            return;
        }

        $trackId = $state->track_id;
        if (! is_string($trackId) || $trackId === '') {
            return;
        }

        try {
            $track = $client->trackStatus($trackId);
        } catch (Throwable) {
            return;
        }

        if ($track['status'] === 'processed') {
            $state->update([
                'status' => ArbeitsschutzLightRagStatus::Processed,
                'lightrag_doc_id' => $track['doc_id'],
                'error_message' => null,
                'indexed_at' => now(),
            ]);

            return;
        }

        if ($track['status'] === 'failed') {
            $state->update([
                'status' => ArbeitsschutzLightRagStatus::Failed,
                'error_message' => str($track['error_message'] ?? 'LightRAG-Verarbeitung fehlgeschlagen.')->limit(2000)->toString(),
            ]);
        }
    }
}
