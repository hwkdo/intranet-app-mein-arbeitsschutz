<?php

use function Livewire\Volt\{title};

title('MeinArbeitsschutz - Meine Einstellungen');

?>

<x-intranet-app-mein-arbeitsschutz::mein-arbeitsschutz-layout heading="Meine Einstellungen" subheading="Persönliche Einstellungen für die MeinArbeitsschutz App">
    @livewire('intranet-app-base::user-settings', ['appIdentifier' => 'mein-arbeitsschutz'])
</x-intranet-app-mein-arbeitsschutz::mein-arbeitsschutz-layout>
