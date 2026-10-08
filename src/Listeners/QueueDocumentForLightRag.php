<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Listeners;

use Hwkdo\IntranetAppMeinArbeitsschutz\Events\DocumentUploaded;
use Hwkdo\IntranetAppMeinArbeitsschutz\Services\ArbeitsschutzLightRagQueue;

class QueueDocumentForLightRag
{
    public function handle(DocumentUploaded $event): void
    {
        app(ArbeitsschutzLightRagQueue::class)->enqueue($event->document);
    }
}
