<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Models;

use Hwkdo\IntranetAppMeinArbeitsschutz\Database\Factories\WorkAreaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class WorkArea extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected static function newFactory()
    {
        return WorkAreaFactory::new();
    }

    protected $table = 'intranet_app_mein_arbeitsschutz_work_areas';

    protected $guarded = [];

    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class, 'source_id')
            ->where('source_type', static::class);
    }

    public function hasIcon(): bool
    {
        return $this->hasMedia('icon');
    }

    public function setIcon(?UploadedFile $file): ?Media
    {
        $this->clearMediaCollection('icon');

        return $file ? $this->addMedia($file)
            ->usingName('icon')
            ->toMediaCollection('icon') : null;
    }

    public function getIcon(): ?Media
    {
        return $this->getFirstMedia('icon');
    }

    public function getIconUrl(): ?string
    {
        return $this->hasIcon() ? $this->getIcon()->getFullUrl() : null;
    }
}
