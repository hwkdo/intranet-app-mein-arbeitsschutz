<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Enums;

enum ViewModeEnum: string
{
    case Grid = 'grid';
    case List = 'list';

    public static function options(): array
    {
        return [
            self::Grid->value => 'Raster-Ansicht',
            self::List->value => 'Listen-Ansicht',
        ];
    }
}
