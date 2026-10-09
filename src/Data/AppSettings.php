<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Data;

use Hwkdo\IntranetAppBase\Contracts\HasDocumentParseSettings;
use Hwkdo\IntranetAppBase\Data\Attributes\Description;
use Hwkdo\IntranetAppBase\Data\BaseAppSettings;
use Hwkdo\IntranetAppBase\Enums\DocumentParseEngine;
use Hwkdo\IntranetAppBase\Traits\HasDocumentParseSettingsFields;
use Hwkdo\IntranetAppMeinArbeitsschutz\Enums\ViewModeEnum;

class AppSettings extends BaseAppSettings implements HasDocumentParseSettings
{
    use HasDocumentParseSettingsFields;

    public function __construct(

        #[Description('OpenWebUi-Collection-ID für KI Ablage der hochgeladenen Dokumente')]
        public string $openWebUiCollectionId = 'b513b09b-2e3d-43a8-8213-bc120395913a',

        #[Description('OpenWebUi-Modell für KI Chat')]
        public string $openWebUiModel = 'Intranet-App-Mein-Arbeitsschutz',

        #[Description('Route-Name der Startseite (z.B. apps.mein-arbeitsschutz.documents). Wenn nicht gesetzt, wird die Standard-Übersicht verwendet.')]
        public ?string $startPageRoute = null,

        #[Description('Ansicht für Suchergebnisse')]
        public ViewModeEnum $viewModeSearch = ViewModeEnum::Grid,

        #[Description('Ansicht für Allgemeine Dokumente')]
        public ViewModeEnum $viewModeGeneral = ViewModeEnum::Grid,

        #[Description('Ansicht für Notsituation/Erste Hilfe')]
        public ViewModeEnum $viewModeFirstAid = ViewModeEnum::Grid,

        #[Description('Ansichten für Arbeitsbereiche nach Dokumenttyp')]
        public array $viewModeWorkAreas = [
            'risk_assessment' => 'grid',
            'operating_instructions' => 'grid',
            'hazardous_substances' => 'grid',
            'safety_data_sheets' => 'grid',
        ],

        #[Description('Document-Parsing-Motor überschreiben (leer = Intranet-Base-Default)')]
        public ?DocumentParseEngine $documentParseEngineOverride = null,

        #[Description('LlamaParse-Tier überschreiben (leer = Intranet-Base-Default)')]
        public ?string $documentParseTierOverride = null,
    ) {}
}
