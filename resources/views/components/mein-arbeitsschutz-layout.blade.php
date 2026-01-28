@props([
    'heading' => '',
    'subheading' => '',
    'navItems' => []
])

@php
    $defaultNavItems = [
        ['label' => 'Übersicht', 'href' => route('apps.mein-arbeitsschutz.index'), 'icon' => 'home', 'description' => 'Zurück zur Übersicht', 'buttonText' => 'Übersicht anzeigen'],
        ['label' => 'Dokumente', 'href' => route('apps.mein-arbeitsschutz.documents'), 'icon' => 'document-text', 'description' => 'Dokumente durchsuchen', 'buttonText' => 'Dokumente öffnen'],
        ['label' => 'Suche', 'href' => route('apps.mein-arbeitsschutz.search'), 'icon' => 'magnifying-glass', 'description' => 'Dokumente durchsuchen', 'buttonText' => 'Suche öffnen'],
        ['label' => 'Chat', 'href' => route('apps.mein-arbeitsschutz.chat'), 'icon' => 'chat-bubble-left-right', 'description' => 'KI-Chat nutzen', 'buttonText' => 'Chat öffnen'],
        ['label' => 'Meine Einstellungen', 'href' => route('apps.mein-arbeitsschutz.settings.user'), 'icon' => 'cog-6-tooth', 'description' => 'Persönliche Einstellungen anpassen', 'buttonText' => 'Einstellungen öffnen'],
        ['label' => 'Admin', 'href' => route('apps.mein-arbeitsschutz.admin.index'), 'icon' => 'shield-check', 'description' => 'Administrationsbereich verwalten', 'buttonText' => 'Admin öffnen', 'permission' => 'manage-app-mein-arbeitsschutz']
    ];

    $navItems = !empty($navItems) ? $navItems : $defaultNavItems;
@endphp

@if(request()->routeIs('apps.mein-arbeitsschutz.index'))
    <x-intranet-app-base::app-layout 
        app-identifier="mein-arbeitsschutz"
        :heading="$heading"
        :subheading="$subheading"
        :nav-items="$navItems"
        :wrap-in-card="false"
    >
        <x-intranet-app-base::app-index-auto 
            app-identifier="mein-arbeitsschutz"
            app-name="MeinArbeitsschutz App"
            app-description="Generated app: Mein Arbeitsschutz"
            :nav-items="$navItems"
            welcome-title="Willkommen zur MeinArbeitsschutz App"
            welcome-description="Dies ist eine Beispiel-App, die als MeinArbeitsschutz für neue Intranet-Apps dient."
        />
    </x-intranet-app-base::app-layout>
@else
    <x-intranet-app-base::app-layout 
        app-identifier="mein-arbeitsschutz"
        :heading="$heading"
        :subheading="$subheading"
        :nav-items="$navItems"
        :wrap-in-card="true"
    >
        {{ $slot }}
    </x-intranet-app-base::app-layout>
@endif
