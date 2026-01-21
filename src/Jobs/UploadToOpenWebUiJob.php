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
use Illuminate\Support\Facades\Storage;
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

            // Hole den Dateipfad - funktioniert für lokale Disks
            $filePath = $this->media->getPath();
            $tempFile = null;

            // Wenn die Datei nicht existiert, versuche sie vom Disk zu lesen
            if (! file_exists($filePath)) {
                // Versuche, die Datei vom Disk zu lesen (für Remote-Disks)
                $disk = $this->media->disk;
                $diskInstance = Storage::disk($disk);
                $relativePath = $this->media->getPathRelativeToRoot();

                if ($diskInstance->exists($relativePath)) {
                    // Datei existiert auf Remote-Disk - lade sie temporär herunter
                    $tempFile = sys_get_temp_dir().'/'.uniqid('openwebui_upload_', true).'_'.$this->media->file_name;
                    $fileContent = $diskInstance->get($relativePath);
                    file_put_contents($tempFile, $fileContent);
                    $filePath = $tempFile;
                } else {
                    Log::error('OpenWebUI Upload failed: File not found', [
                        'document_id' => $this->document->id,
                        'media_id' => $this->media->id,
                        'file_path' => $filePath,
                        'disk' => $disk,
                        'relative_path' => $relativePath,
                    ]);

                    return;
                }
            }

            // Prüfe, ob die Datei wirklich existiert und lesbar ist
            if (! file_exists($filePath)) {
                Log::error('OpenWebUI Upload failed: File does not exist', [
                    'document_id' => $this->document->id,
                    'media_id' => $this->media->id,
                    'file_path' => $filePath,
                ]);

                return;
            }

            if (! is_readable($filePath)) {
                Log::error('OpenWebUI Upload failed: File not readable', [
                    'document_id' => $this->document->id,
                    'media_id' => $this->media->id,
                    'file_path' => $filePath,
                ]);

                return;
            }

            // Prüfe, ob die Datei nicht leer ist
            if (filesize($filePath) === 0) {
                Log::error('OpenWebUI Upload failed: File is empty', [
                    'document_id' => $this->document->id,
                    'media_id' => $this->media->id,
                    'file_path' => $filePath,
                ]);

                return;
            }

            // Wir müssen die File-ID aus dem Upload-Result holen, bevor wir addToKnowledge aufrufen
            // Daher rufen wir die Methoden einzeln auf
            $uploadResult = $ragService->uploadFile($filePath);
            $fileId = $uploadResult['id'] ?? null;

            if (! $fileId) {
                throw new \Exception('Keine File-ID in Upload-Response: '.json_encode($uploadResult));
            }

            // Warte auf Verarbeitung
            $ragService->waitForFileProcessing($fileId);

            // Füge zur Collection hinzu
            // "Duplicate content" bedeutet, dass der Hash bereits in der DB existiert,
            // aber die Datei selbst ist NICHT in der Collection
            // Das passiert, wenn eine Datei gelöscht wurde, aber der Hash noch in der DB ist
            // In diesem Fall müssen wir die Datei trotzdem zur Collection hinzufügen
            try {
                $ragService->addFileToKnowledge($collectionId, $fileId);
                Log::info('OpenWebUI File added to collection', [
                    'document_id' => $this->document->id,
                    'media_id' => $this->media->id,
                    'collection_id' => $collectionId,
                    'openwebui_file_id' => $fileId,
                ]);
            } catch (\Exception $e) {
                // Wenn es ein "Duplicate content" Fehler ist, bedeutet das, dass der Hash bereits existiert
                // aber die Datei ist NICHT in der Collection
                // Wir loggen es als Warnung, aber behandeln es nicht als Fehler
                if (str_contains($e->getMessage(), 'Duplicate content')) {
                    Log::warning('OpenWebUI Duplicate content detected - hash exists but file not in collection', [
                        'document_id' => $this->document->id,
                        'media_id' => $this->media->id,
                        'collection_id' => $collectionId,
                        'openwebui_file_id' => $fileId,
                        'error' => $e->getMessage(),
                    ]);
                } else {
                    // Anderer Fehler - weiterwerfen
                    throw $e;
                }
            }

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
        } finally {
            // Lösche temporäre Datei, falls vorhanden
            if (isset($tempFile) && file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }
}
