<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Jobs;

use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Document;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\IntranetAppMeinArbeitsschutzSettings;
use Hwkdo\OpenwebuiApiLaravel\Services\OpenWebUiRagService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class UploadToOpenWebUiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Document $document,
        public Media $media,
    ) {}

    public function handle(OpenWebUiRagService $ragService): void
    {
        try {
            $settings = IntranetAppMeinArbeitsschutzSettings::current();

            if (! $settings) {
                Log::error('OpenWebUI Upload failed: No settings found', [
                    'document_id' => $this->document->id,
                    'media_id' => $this->media->id,
                ]);

                return;
            }

            $collectionId = $settings->settings->openWebUiCollectionId;

            if (empty($collectionId)) {
                Log::error('OpenWebUI Upload failed: No collection ID configured', [
                    'document_id' => $this->document->id,
                    'media_id' => $this->media->id,
                ]);

                return;
            }

            $filePath = $this->media->getPath();

            if (! file_exists($filePath)) {
                Log::error('OpenWebUI Upload failed: File not found', [
                    'document_id' => $this->document->id,
                    'media_id' => $this->media->id,
                    'file_path' => $filePath,
                ]);

                return;
            }

            $result =             // Wir müssen die File-ID aus dem Upload-Result holen, bevor wir addToKnowledge aufrufen
            // Daher rufen wir die Methoden einzeln auf
            $uploadResult = $ragService->uploadFile($filePath);
            $fileId = $uploadResult['id'] ?? null;

            if (! $fileId) {
                throw new \Exception('Keine File-ID in Upload-Response: '.json_encode($uploadResult));
            }

            // Warte auf Verarbeitung
            $ragService->waitForFileProcessing($fileId);

            // Füge zur Collection hinzu
            $ragService->addFileToKnowledge($collectionId, $fileId);

            // Speichere die File-ID im Dokument
            $this->document->update(['openwebui_file_id' => $fileId]);

            Log::info('OpenWebUI Upload successful', [
                'document_id' => $this->document->id,
                'media_id' => $this->media->id,
                'collection_id' => $collectionId,
                'openwebui_file_id' => $fileId,
            ]);
        } catch (\Exception $e) {
            Log::error('OpenWebUI Upload failed', [
                'document_id' => $this->document->id,
                'media_id' => $this->media->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
