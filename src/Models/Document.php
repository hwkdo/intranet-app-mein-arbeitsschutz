<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Hwkdo\IntranetAppMeinArbeitsschutz\Enums\ArbeitsschutzLightRagStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Laravel\Scout\Searchable;


class Document extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use Searchable;

    protected $table = 'intranet_app_mein_arbeitsschutz_documents';

    protected $guarded = [];

    public function searchableAs(): string
    {
        return 'mein_arbeitsschutz_documents';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'description' => $this->description ?? '',
            'uploaded_by' => $this->uploadedBy->name,
            'created_at' => $this->created_at?->timestamp,
        ];
    }

    public function typesenseSearchParameters(): array
    {
        return [
            'infix' => 'always',  // oder 'always,always' wenn query_by = title,description (ein Wert pro Feld)
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DocumentAssignment::class, 'document_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function lightRagState(): HasOne
    {
        return $this->hasOne(DocumentLightRagState::class, 'document_id');
    }

    public function lightRagIsCurrent(): bool
    {
        $state = $this->lightRagState;
        $mediaId = $this->getFirstMedia('documents')?->id;
        if ($state === null || $mediaId === null) {
            return false;
        }

        return (int) $state->media_id === (int) $mediaId
            && in_array($state->status, [
                ArbeitsschutzLightRagStatus::Pending,
                ArbeitsschutzLightRagStatus::Processing,
                ArbeitsschutzLightRagStatus::Processed,
            ], true);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->singleFile()
            ->useDisk('intranet-app-mein-arbeitsschutz');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 320, 420)
            ->nonQueued();
    }
}
