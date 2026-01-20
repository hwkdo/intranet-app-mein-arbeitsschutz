<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Models;

use Hwkdo\IntranetAppMeinArbeitsschutz\Database\Factories\DocumentTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return DocumentTypeFactory::new();
    }

    protected $table = 'intranet_app_mein_arbeitsschutz_document_types';

    protected $guarded = [];

    public function assignments(): HasMany
    {
        return $this->hasMany(DocumentAssignment::class, 'document_type_id');
    }
}
