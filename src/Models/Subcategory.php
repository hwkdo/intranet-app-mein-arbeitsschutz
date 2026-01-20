<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Subcategory extends Model
{
    use HasFactory;

    protected $table = 'intranet_app_mein_arbeitsschutz_subcategories';

    protected $guarded = [];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DocumentAssignment::class, 'subcategory_id');
    }
}
