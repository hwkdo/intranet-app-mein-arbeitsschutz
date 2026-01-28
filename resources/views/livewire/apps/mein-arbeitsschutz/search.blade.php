<?php

use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Document;
use function Livewire\Volt\{computed, state, title};

title('MeinArbeitsschutz - Suche');

state(['searchQuery' => '']);

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

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @forelse($this->results as $document)
                        <flux:card class="flex gap-4">
                            @php($media = $document->getFirstMedia('documents'))
                            @php($thumbnail = $media && $media->hasGeneratedConversion('thumb') ? route('apps.mein-arbeitsschutz.documents.thumb', $document) : null)
                            @php($fileUrl = route('apps.mein-arbeitsschutz.documents.download', $document))
                            <div class="h-24 w-20 flex-shrink-0 overflow-hidden rounded-lg border bg-white dark:bg-zinc-900">
                                @if($thumbnail)
                                    <img src="{{ $thumbnail }}" alt="{{ $document->title }}" class="h-full w-full object-cover" />
                                @else
                                    <div class="flex h-full w-full items-center justify-center">
                                        <flux:icon icon="document-text" class="h-6 w-6 text-zinc-400" />
                                    </div>
                                @endif
                            </div>
                            <div class="flex flex-1 flex-col gap-2">
                                <flux:heading size="sm">{{ $document->title }}</flux:heading>
                                @if($document->description)
                                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ Str::limit($document->description, 100) }}
                                    </flux:text>
                                @endif
                                @if($fileUrl)
                                    <flux:button href="{{ $fileUrl }}" variant="primary" size="sm" icon="arrow-down-tray">
                                        Download
                                    </flux:button>
                                @endif
                            </div>
                        </flux:card>
                    @empty
                        <div class="col-span-full">
                            <flux:callout variant="info">
                                Keine Dokumente gefunden. Versuchen Sie einen anderen Suchbegriff.
                            </flux:callout>
                        </div>
                    @endforelse
                </div>
            </div>
        @elseif (empty($this->searchQuery))
            <flux:callout variant="info">
                Geben Sie mindestens 2 Zeichen ein, um die Suche zu starten.
            </flux:callout>
        @endif
    </div>
</x-intranet-app-mein-arbeitsschutz::mein-arbeitsschutz-layout>
</div>