<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Jobs;

use Hwkdo\IntranetAppBase\Contracts\IntranetAiGatewayInterface;
use Hwkdo\IntranetAppMeinArbeitsschutz\Enums\ArbeitsschutzLightRagStatus;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Document;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\DocumentLightRagState;
use Hwkdo\IntranetAppMeinArbeitsschutz\Services\LightRagArbeitsschutzClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class SyncArbeitsschutzDocumentToLightRag implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 600;

    public function __construct(public int $documentId) {}

    public function handle(LightRagArbeitsschutzClient $client, IntranetAiGatewayInterface $gateway): void
    {
        if (app()->runningUnitTests() && ! config('intranet-app-mein-arbeitsschutz.lightrag.execute_in_tests')) {
            return;
        }

        $document = Document::query()->find($this->documentId);
        $media = $document?->getFirstMedia('documents');
        $path = $media instanceof Media ? $this->readablePath($media) : null;
        if ($document === null || $path === null || $media === null) {
            $this->markFailed($this->documentId, $media?->id, 'Die Datei fehlt.');

            return;
        }

        $state = DocumentLightRagState::query()->firstOrNew(['document_id' => $document->id]);
        if ((int) $state->media_id === (int) $media->id
            && $state->status === ArbeitsschutzLightRagStatus::Processed
            && is_string($state->lightrag_doc_id)
            && $state->lightrag_doc_id !== '') {
            return;
        }

        $state->fill([
            'media_id' => $media->id,
            'status' => ArbeitsschutzLightRagStatus::Processing,
            'error_message' => null,
        ])->save();

        try {
            $extension = $media->extension !== '' ? '.'.$media->extension : '';
            $fileName = 'arbeitsschutz-'.$document->id.$extension;
            $parsed = $this->insertParsed($client, $gateway, $path, $fileName);
            if ($parsed['track_id'] === '') {
                return;
            }

            $state->update([
                'track_id' => $parsed['track_id'],
                'status' => ArbeitsschutzLightRagStatus::Processing,
                'error_message' => null,
            ]);
        } catch (Throwable $exception) {
            if ($this->attempts() < $this->tries) {
                $this->release(30);

                return;
            }

            report($exception);
            $this->markFailed($document->id, $media->id, $exception->getMessage());
        }
    }

    /**
     * @return array{track_id: string}
     */
    private function insertParsed(
        LightRagArbeitsschutzClient $client,
        IntranetAiGatewayInterface $gateway,
        string $path,
        string $fileName,
    ): array {
        $markdown = $gateway->parseAppDocument($path, 'mein-arbeitsschutz');

        return $client->insertText(
            '# '.$fileName."\n\n".$markdown,
            $this->markdownName($fileName),
        );
    }

    private function markdownName(string $fileName): string
    {
        $base = pathinfo($fileName, PATHINFO_FILENAME);

        return ($base !== '' ? $base : 'dokument').'.md';
    }

    private function readablePath(Media $media): ?string
    {
        $path = $media->getPath();
        if (is_string($path) && is_file($path)) {
            return $path;
        }

        $relative = $media->getPathRelativeToRoot();
        $disk = Storage::disk($media->disk);
        if ($relative !== '' && $disk->exists($relative)) {
            return $disk->path($relative);
        }

        return null;
    }

    private function markFailed(int $documentId, ?int $mediaId, string $message): void
    {
        DocumentLightRagState::query()->updateOrCreate(
            ['document_id' => $documentId],
            [
                'media_id' => $mediaId,
                'status' => ArbeitsschutzLightRagStatus::Failed,
                'error_message' => str($message)->limit(2000)->toString(),
            ],
        );
    }
}
