<?php

use Hwkdo\IntranetAppMeinArbeitsschutz\Enums\ViewModeEnum;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Document;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\IntranetAppMeinArbeitsschutzSettings;
use function Livewire\Volt\{computed, state, title};

title('MeinArbeitsschutz - Suche');

state(['searchQuery' => '']);

$viewModeSearch = computed(function () {
    $settingsModel = IntranetAppMeinArbeitsschutzSettings::current();

    return $settingsModel?->settings?->viewModeSearch ?? ViewModeEnum::Grid;
});

$results = computed(function () {
    if (empty($this->searchQuery) || strlen($this->searchQuery) < 2) {
        return collect();
    }

    return Document::search($this->searchQuery)
        ->query(fn ($query) => $query->with('media'))
        ->take(50)
        ->get();
});

?>
<div>
<x-intranet-app-mein-arbeitsschutz::mein-arbeitsschutz-layout heading="MeinArbeitsschutz App" subheading="Suche">
    <div class="space-y-6">
        <div class="space-y-2">
            <flux:heading size="lg">Dokumente durchsuchen</flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400">
                Geben Sie einen Suchbegriff ein, um Dokumente zu finden.
            </flux:text>
        </div>

        <flux:input
            wire:model.live.debounce.300ms="searchQuery"
            placeholder="Suchbegriff eingeben..."
            icon="magnifying-glass"
            class="w-full"
        />

        @if (!empty($this->searchQuery) && strlen($this->searchQuery) >= 2)
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <flux:heading size="md">Suchergebnisse</flux:heading>
                    <flux:badge variant="outline">{{ $this->results->count() }}</flux:badge>
                </div>

                @if($this->results->isEmpty())
                    <flux:callout variant="info">
                        Keine Dokumente gefunden. Versuchen Sie einen anderen Suchbegriff.
                    </flux:callout>
                @else
                    <x-intranet-app-mein-arbeitsschutz::document-list
                        :documents="$this->results"
                        :viewMode="$this->viewModeSearch->value"
                    />
                @endif
            </div>
        @elseif (empty($this->searchQuery))
            <flux:callout variant="info">
                Geben Sie mindestens 2 Zeichen ein, um die Suche zu starten.
            </flux:callout>
        @endif
    </div>
</x-intranet-app-mein-arbeitsschutz::mein-arbeitsschutz-layout>
</div>