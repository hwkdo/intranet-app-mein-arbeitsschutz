<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Models;

use Hwkdo\IntranetAppMeinArbeitsschutz\Data\AppSettings;
use Illuminate\Database\Eloquent\Model;

class IntranetAppMeinArbeitsschutzSettings extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'settings' => AppSettings::class.':default',
        ];
    }

    public static function current(): ?IntranetAppMeinArbeitsschutzSettings
    {
        return self::orderBy('version', 'desc')->first();
    }
}
