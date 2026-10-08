<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Listeners;

use Hwkdo\IntranetAppMeinArbeitsschutz\Events\DocumentDeleted;
use Hwkdo\IntranetAppMeinArbeitsschutz\Services\ArbeitsschutzLightRagQueue;

class RemoveDocumentFromLightRag
{
    public function handle(DocumentDeleted $event): void
    {
        app(ArbeitsschutzLightRagQueue::class)->remove($event->documentId);
    }
}
