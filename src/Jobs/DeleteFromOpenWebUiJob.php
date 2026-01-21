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

            $ragService->deleteFile($this->openwebuiFileId);

            Log::info('OpenWebUI Delete successful', [
                'document_id' => $this->documentId,
                'openwebui_file_id' => $this->openwebuiFileId,
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
