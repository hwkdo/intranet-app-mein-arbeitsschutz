<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Listeners;

use Hwkdo\IntranetAppMeinArbeitsschutz\Events\DocumentDeleted;
use Hwkdo\IntranetAppMeinArbeitsschutz\Jobs\DeleteFromOpenWebUiJob;

class DeleteDocumentFromOpenWebUi
{
    public function handle(DocumentDeleted $event): void
    {
        if ($event->openwebuiFileId) {
            DeleteFromOpenWebUiJob::dispatch($event->documentId, $event->openwebuiFileId);
        }
    }
}
