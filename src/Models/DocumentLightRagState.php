<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Models;

use Hwkdo\IntranetAppMeinArbeitsschutz\Enums\ArbeitsschutzLightRagStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentLightRagState extends Model
{
    protected $table = 'intranet_app_mein_arbeitsschutz_lightrag_states';

    protected $guarded = [];

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ArbeitsschutzLightRagStatus::class,
            'indexed_at' => 'datetime',
        ];
    }
}
