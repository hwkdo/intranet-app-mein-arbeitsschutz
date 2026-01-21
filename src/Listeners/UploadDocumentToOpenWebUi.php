<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Listeners;

use Hwkdo\IntranetAppMeinArbeitsschutz\Events\DocumentUploaded;
use Hwkdo\IntranetAppMeinArbeitsschutz\Jobs\UploadToOpenWebUiJob;

class UploadDocumentToOpenWebUi
{
    public function handle(DocumentUploaded $event): void
    {
        UploadToOpenWebUiJob::dispatch($event->document, $event->media);
    }
}
