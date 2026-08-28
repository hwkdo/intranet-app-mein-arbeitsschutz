<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz;

use Hwkdo\IntranetAppBase\Interfaces\IntranetAppInterface;
use Hwkdo\IntranetAppBase\Interfaces\ProvidesSearchInterface;
use Hwkdo\IntranetAppBase\Interfaces\SearchSourceInterface;
use Hwkdo\IntranetAppMeinArbeitsschutz\Data\AppSettings;
use Hwkdo\IntranetAppMeinArbeitsschutz\Search\DocumentsSearchSource;
use Illuminate\Support\Collection;

class IntranetAppMeinArbeitsschutz implements IntranetAppInterface, ProvidesSearchInterface
{
    public static function app_name(): string
    {
        return 'MeinArbeitsschutz';
    }

    public static function app_icon(): string
    {
        return 'magnifying-glass';
    }

    public static function identifier(): string
    {
        return 'mein-arbeitsschutz';
    }

    public static function roles_admin(): Collection
    {
        return collect(config('intranet-app-mein-arbeitsschutz.roles.admin'));
    }

    public static function roles_user(): Collection
    {
        return collect(config('intranet-app-mein-arbeitsschutz.roles.user'));
    }

    public static function userSettingsClass(): ?string
    {
        return null;
    }

    public static function appSettingsClass(): ?string
    {
        return AppSettings::class;
    }

    public static function mcpServers(): array
    {
        return [];
    }

    /**
     * @return list<class-string<SearchSourceInterface>>
     */
    public static function searchSources(): array
    {
        return [
            DocumentsSearchSource::class,
        ];
    }
}
