<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $table = 'intranet_app_mein_arbeitsschutz_categories';

    protected $guarded = [];

    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class, 'category_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DocumentAssignment::class, 'category_id');
    }
}
