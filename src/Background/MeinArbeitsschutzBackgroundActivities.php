<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Background;

use Hwkdo\IntranetAppBase\Data\BackgroundActivityDefinition;
use Hwkdo\IntranetAppBase\Data\BackgroundActivityFrequency;
use Hwkdo\IntranetAppBase\Enums\BackgroundActivityCadence;
use Hwkdo\IntranetAppMeinArbeitsschutz\IntranetAppMeinArbeitsschutz;
use Hwkdo\IntranetAppMeinArbeitsschutz\Jobs\DeleteFromOpenWebUiJob;
use Hwkdo\IntranetAppMeinArbeitsschutz\Jobs\RemoveArbeitsschutzDocumentFromLightRag;
use Hwkdo\IntranetAppMeinArbeitsschutz\Jobs\SyncArbeitsschutzDocumentToLightRag;
use Hwkdo\IntranetAppMeinArbeitsschutz\Jobs\SyncArbeitsschutzLightRagTrack;
use Hwkdo\IntranetAppMeinArbeitsschutz\Jobs\UploadToOpenWebUiJob;

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
            BackgroundActivityDefinition::queued(key: 'mein-arbeitsschutz.lightrag-sync', label: 'Dokument an LightRAG', appIdentifier: IntranetAppMeinArbeitsschutz::identifier(), appName: IntranetAppMeinArbeitsschutz::app_name(), job: SyncArbeitsschutzDocumentToLightRag::class, description: 'Überträgt ein Arbeitsschutz-Dokument an LightRAG.', sort: 20),
            BackgroundActivityDefinition::queued(key: 'mein-arbeitsschutz.lightrag-track', label: 'LightRAG-Track', appIdentifier: IntranetAppMeinArbeitsschutz::identifier(), appName: IntranetAppMeinArbeitsschutz::app_name(), job: SyncArbeitsschutzLightRagTrack::class, description: 'Aktualisiert den LightRAG-Track eines Arbeitsschutz-Dokuments.', sort: 30),
            BackgroundActivityDefinition::queued(key: 'mein-arbeitsschutz.lightrag-remove', label: 'Dokument aus LightRAG entfernen', appIdentifier: IntranetAppMeinArbeitsschutz::identifier(), appName: IntranetAppMeinArbeitsschutz::app_name(), job: RemoveArbeitsschutzDocumentFromLightRag::class, description: 'Entfernt ein Arbeitsschutz-Dokument aus LightRAG.', sort: 40),
            BackgroundActivityDefinition::queued(key: 'mein-arbeitsschutz.openwebui-upload', label: 'Upload nach Open WebUI', appIdentifier: IntranetAppMeinArbeitsschutz::identifier(), appName: IntranetAppMeinArbeitsschutz::app_name(), job: UploadToOpenWebUiJob::class, description: 'Lädt ein Dokument nach Open WebUI.', sort: 50),
            BackgroundActivityDefinition::queued(key: 'mein-arbeitsschutz.openwebui-delete', label: 'Löschen aus Open WebUI', appIdentifier: IntranetAppMeinArbeitsschutz::identifier(), appName: IntranetAppMeinArbeitsschutz::app_name(), job: DeleteFromOpenWebUiJob::class, description: 'Entfernt ein Dokument aus Open WebUI.', sort: 60),
        ];
    }
}
