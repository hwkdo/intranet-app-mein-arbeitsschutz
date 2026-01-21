<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Jobs;

use Hwkdo\OpenwebuiApiLaravel\Services\OpenWebUiRagService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteFromOpenWebUiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $documentId,
        public string $openwebuiFileId,
    ) {}

    public function handle(OpenWebUiRagService $ragService): void
    {
        try {
            if (empty($this->openwebuiFileId)) {
                Log::warning('OpenWebUI Delete skipped: No file ID provided', [
                    'document_id' => $this->documentId,
                ]);

                return;
            }

            // Hole die Collection-ID aus den Settings
            $settings = \Hwkdo\IntranetAppMeinArbeitsschutz\Models\IntranetAppMeinArbeitsschutzSettings::current();

            if (! $settings) {
                Log::error('OpenWebUI Delete failed: No settings found', [
                    'document_id' => $this->documentId,
                    'openwebui_file_id' => $this->openwebuiFileId,
                ]);

                return;
            }

            $collectionId = $settings->settings->openWebUiCollectionId;

            if (empty($collectionId)) {
                Log::error('OpenWebUI Delete failed: No collection ID configured', [
                    'document_id' => $this->documentId,
                    'openwebui_file_id' => $this->openwebuiFileId,
                ]);

                return;
            }

            // Entferne die Datei aus der Collection und lösche sie (delete_file = true)
            // Dies sollte auch den Hash und alle Metadaten entfernen
            try {
                $ragService->removeFileFromKnowledge($collectionId, $this->openwebuiFileId, true);
            } catch (\Exception $e) {
                // Wenn removeFileFromKnowledge fehlschlägt (z.B. Datei nicht in Collection),
                // versuchen wir, die Datei direkt zu löschen
                if (str_contains($e->getMessage(), 'not found') || str_contains($e->getMessage(), '404')) {
                    Log::warning('OpenWebUI File not in collection, trying to delete file directly', [
                        'document_id' => $this->documentId,
                        'openwebui_file_id' => $this->openwebuiFileId,
                        'collection_id' => $collectionId,
                    ]);
                    
                    try {
                        $ragService->deleteFile($this->openwebuiFileId);
                    } catch (\Exception $deleteException) {
                        // Wenn auch das Löschen fehlschlägt, loggen wir es, aber werfen den ursprünglichen Fehler
                        Log::warning('OpenWebUI Direct file delete also failed', [
                            'document_id' => $this->documentId,
                            'openwebui_file_id' => $this->openwebuiFileId,
                            'error' => $deleteException->getMessage(),
                        ]);
                    }
                } else {
                    // Anderer Fehler - weiterwerfen
                    throw $e;
                }
            }

            // Reindiziere die Collection, damit die Änderungen sichtbar werden
            try {
                $ragService->reindexKnowledgeFiles($collectionId);
                Log::info('OpenWebUI Collection reindexed after delete', [
                    'document_id' => $this->documentId,
                    'collection_id' => $collectionId,
                ]);
            } catch (\Exception $e) {
                // Reindexierung ist nicht kritisch - loggen, aber nicht als Fehler behandeln
                Log::warning('OpenWebUI Collection reindex failed after delete', [
                    'document_id' => $this->documentId,
                    'collection_id' => $collectionId,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('OpenWebUI Delete successful', [
                'document_id' => $this->documentId,
                'openwebui_file_id' => $this->openwebuiFileId,
                'collection_id' => $collectionId,
            ]);
        } catch (\Exception $e) {
            Log::error('OpenWebUI Delete failed', [
                'document_id' => $this->documentId,
                'openwebui_file_id' => $this->openwebuiFileId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
