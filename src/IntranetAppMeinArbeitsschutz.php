<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz;

use Hwkdo\IntranetAppBase\Data\SearchActionDefinition;
use Hwkdo\IntranetAppBase\Interfaces\IntranetAppInterface;
use Hwkdo\IntranetAppBase\Interfaces\ProvidesSearchActionsInterface;
use Hwkdo\IntranetAppBase\Interfaces\ProvidesSearchInterface;
use Hwkdo\IntranetAppBase\Interfaces\SearchSourceInterface;
use Hwkdo\IntranetAppMeinArbeitsschutz\Data\AppSettings;
use Hwkdo\IntranetAppMeinArbeitsschutz\Search\DocumentsSearchSource;
use Illuminate\Support\Collection;

class IntranetAppMeinArbeitsschutz implements IntranetAppInterface, ProvidesSearchActionsInterface, ProvidesSearchInterface
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

    public static function searchActions(): array
    {
        return [
            new SearchActionDefinition(
                key: 'mein-arbeitsschutz.lightrag',
                title: 'LightRAG',
                keywords: [
                    'lightrag',
                    'rag',
                    'light rag',
                    'arbeitsschutz lightrag',
                    'arbeitsschutz rag',
                    'mein arbeitsschutz lightrag',
                ],
                routeName: 'apps.mein-arbeitsschutz.admin.index',
                appIdentifier: self::identifier(),
                appName: self::app_name(),
                icon: 'circle-stack',
                permission: 'manage-app-mein-arbeitsschutz',
                subtitle: self::app_name(),
                sort: 100,
                queryParameters: ['tab' => 'lightrag'],
            ),
        ];
    }
}
