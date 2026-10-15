<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Background;

use Hwkdo\IntranetAppBase\Data\BackgroundActivityDefinition;
use Hwkdo\IntranetAppBase\Data\BackgroundActivityFrequency;
use Hwkdo\IntranetAppBase\Enums\BackgroundActivityCadence;
use Hwkdo\IntranetAppMeinArbeitsschutz\IntranetAppMeinArbeitsschutz;

final class MeinArbeitsschutzBackgroundActivities
{
    /**
     * @return list<BackgroundActivityDefinition>
     */
    public static function definitions(): array
    {
        return [
            BackgroundActivityDefinition::scheduled(
                key: 'mein-arbeitsschutz.lightrag-status',
                label: 'LightRAG-Status',
                appIdentifier: IntranetAppMeinArbeitsschutz::identifier(),
                appName: IntranetAppMeinArbeitsschutz::app_name(),
                command: 'arbeitsschutz:sync-lightrag-status',
                frequency: BackgroundActivityFrequency::everyMinutes(1),
                description: 'Synchronisiert den LightRAG-Status von Arbeitsschutz-Dokumenten.',
                allowedCadences: BackgroundActivityCadence::cases(),
                minEvery: 1,
                maxEvery: 60,
                withoutOverlappingMinutes: 1440,
            ),
        ];
    }
}
