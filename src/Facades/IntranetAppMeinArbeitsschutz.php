<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Hwkdo\IntranetAppMeinArbeitsschutz\IntranetAppMeinArbeitsschutz
 */
class IntranetAppMeinArbeitsschutz extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Hwkdo\IntranetAppMeinArbeitsschutz\IntranetAppMeinArbeitsschutz::class;
    }
}
