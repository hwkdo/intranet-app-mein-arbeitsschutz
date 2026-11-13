<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Listeners;

use Hwkdo\IntranetAppMeinArbeitsschutz\Events\DocumentUploaded;
use Hwkdo\IntranetAppMeinArbeitsschutz\Jobs\UploadToOpenWebUiJob;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\IntranetAppMeinArbeitsschutzSettings;

class UploadDocumentToOpenWebUi
{
    public function handle(DocumentUploaded $event): void
    {
        if (! IntranetAppMeinArbeitsschutzSettings::uploadsDocumentsToOpenWebUi()) {
            return;
        }

        UploadToOpenWebUiJob::dispatch($event->document, $event->media);
    }
}
