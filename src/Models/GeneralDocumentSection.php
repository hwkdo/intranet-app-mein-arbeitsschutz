<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class GeneralDocumentSection extends Model
{
    protected $table = 'intranet_app_mein_arbeitsschutz_general_document_sections';

    protected $guarded = [];

    public function subcategories(): MorphMany
    {
        return $this->morphMany(Subcategory::class, 'source');
    }
}
