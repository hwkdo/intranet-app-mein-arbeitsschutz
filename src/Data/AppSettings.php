<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Data;

use Hwkdo\IntranetAppBase\Data\Attributes\Description;
use Hwkdo\IntranetAppBase\Data\BaseAppSettings;

class AppSettings extends BaseAppSettings
{
    public function __construct(
        #[Description('Aktiviert die Beispiel-Funktionalität')]
        public bool $enableExampleFeature = true,

        #[Description('Maximale Anzahl von Elementen pro Seite')]
        public int $maxItemsPerPage = 25,

        #[Description('Standard-Theme für die App')]
        public string $defaultTheme = 'light',

        #[Description('OpenWebUi-Collection-ID für KI Ablage der hochgeladenen Dokumente')]
        public string $openWebUiCollectionId = 'b513b09b-2e3d-43a8-8213-bc120395913a',

        #[Description('OpenWebUi-Modell für KI Chat')]
        public string $openWebUiModel = 'Intranet-App-Mein-Arbeitsschutz',
    ) {}
}
