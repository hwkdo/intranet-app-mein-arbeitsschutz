<?php

use function Livewire\Volt\title;

title('Hintergrund');

?>

<div>
    <x-intranet-app-mein-arbeitsschutz::mein-arbeitsschutz-layout heading="Hintergrund" subheading="Geplante Tasks, Jobs, Mails und Benachrichtigungen">
        @livewire('intranet-app-base::background-activities', ['appIdentifier' => 'mein-arbeitsschutz'])
    </x-intranet-app-mein-arbeitsschutz::mein-arbeitsschutz-layout>
</div>
