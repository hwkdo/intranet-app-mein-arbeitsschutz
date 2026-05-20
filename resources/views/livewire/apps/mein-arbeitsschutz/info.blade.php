<?php

use function Livewire\Volt\{title};

title('Mein Arbeitsschutz - App-Info');

?>

<x-intranet-app-mein-arbeitsschutz::mein-arbeitsschutz-layout heading="App-Info" subheading="Installierte Version und Release-Historie">
    @livewire('intranet-app-base::app-info', ['appIdentifier' => 'mein-arbeitsschutz'])
</x-intranet-app-mein-arbeitsschutz::mein-arbeitsschutz-layout>
