<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz;

use Hwkdo\IntranetAppBase\Interfaces\IntranetAppInterface;
use Illuminate\Support\Collection;

class IntranetAppMeinArbeitsschutz implements IntranetAppInterface
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
        return \Hwkdo\IntranetAppMeinArbeitsschutz\Data\UserSettings::class;
    }

    public static function appSettingsClass(): ?string
    {
        return \Hwkdo\IntranetAppMeinArbeitsschutz\Data\AppSettings::class;
    }

    public static function mcpServers(): array
    {
        return [];
    }
}
