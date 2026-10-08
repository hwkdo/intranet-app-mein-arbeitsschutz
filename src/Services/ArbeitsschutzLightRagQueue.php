<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Services;

use Hwkdo\IntranetAppMeinArbeitsschutz\Enums\ArbeitsschutzLightRagStatus;
use Hwkdo\IntranetAppMeinArbeitsschutz\Jobs\RemoveArbeitsschutzDocumentFromLightRag;
use Hwkdo\IntranetAppMeinArbeitsschutz\Jobs\SyncArbeitsschutzDocumentToLightRag;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Document;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\DocumentLightRagState;

class ArbeitsschutzLightRagQueue
{
    public function enqueue(Document $document): bool
    {
        $media = $document->getFirstMedia('documents');
        if ($media === null) {
            return false;
        }

        $state = DocumentLightRagState::query()->firstOrNew(['document_id' => $document->id]);
        if ($this->isCurrent($state, (int) $media->id)) {
            return false;
        }

        $state->fill([
            'media_id' => $media->id,
            'status' => ArbeitsschutzLightRagStatus::Pending,
            'error_message' => null,
        ])->save();

        SyncArbeitsschutzDocumentToLightRag::dispatch($document->id)->afterCommit();

        return true;
    }

    public function remove(int $documentId): void
    {
        RemoveArbeitsschutzDocumentFromLightRag::dispatch($documentId)->afterCommit();
    }

    public function enqueueMissing(): int
    {
        $queued = 0;

        Document::query()
            ->with(['lightRagState', 'media'])
            ->orderBy('id')
            ->chunkById(200, function ($documents) use (&$queued): void {
                foreach ($documents as $document) {
                    if ($this->enqueue($document)) {
                        $queued++;
                    }
                }
            });

        return $queued;
    }

    private function isCurrent(DocumentLightRagState $state, int $mediaId): bool
    {
        if (! $state->exists || (int) $state->media_id !== $mediaId) {
            return false;
        }

        return in_array($state->status, [
            ArbeitsschutzLightRagStatus::Pending,
            ArbeitsschutzLightRagStatus::Processing,
            ArbeitsschutzLightRagStatus::Processed,
        ], true);
    }
}
