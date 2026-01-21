<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Events;

use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Document;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DocumentUploaded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Document $document,
        public Media $media,
    ) {}
}
