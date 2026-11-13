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

    public static function current(): ?self
    {
        return self::orderBy('version', 'desc')->first();
    }

    public static function uploadsDocumentsToOpenWebUi(): bool
    {
        $current = self::current();

        if ($current === null || $current->settings === null) {
            return true;
        }

        return $current->settings->openWebUiUploadEnabled;
    }
}
