@props([
    'documents',
    'viewMode' => 'grid',
])

@php
    $isList = $viewMode === 'list' || $viewMode === \Hwkdo\IntranetAppMeinArbeitsschutz\Enums\ViewModeEnum::List->value;
@endphp

@if($isList)
    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-24">Vorschau</flux:table.column>
            <flux:table.column>Titel</flux:table.column>
            <flux:table.column>Beschreibung</flux:table.column>
            <flux:table.column class="w-32">Aktionen</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($documents as $document)
                <flux:table.row :key="$document->id">
                    <flux:table.cell>
                        @php($media = $document->getFirstMedia('documents'))
                        @php($thumbnail = $media && $media->hasGeneratedConversion('thumb') ? route('apps.mein-arbeitsschutz.documents.thumb', $document) : null)
                        <div class="h-16 w-16 overflow-hidden rounded border bg-white dark:bg-zinc-900">
                            @if($thumbnail)
                                <img src="{{ $thumbnail }}" alt="{{ $document->title }}" class="h-full w-full object-cover" />
                            @else
                                <div class="flex h-full w-full items-center justify-center">
                                    <flux:icon icon="document-text" class="h-6 w-6 text-zinc-400" />
                                </div>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium">{{ $document->title }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($document->description)
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ Str::limit($document->description, 100) }}
                            </div>
                        @else
                            <span class="text-zinc-400">-</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($fileUrl = route('apps.mein-arbeitsschutz.documents.download', $document))
                        <flux:button href="{{ $fileUrl }}" variant="primary" size="sm" icon="arrow-down-tray">
                            Download
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        <flux:text class="text-center py-4 text-sm text-zinc-500">
                            Keine Dokumente vorhanden.
                        </flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
@else
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse($documents as $document)
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
                            {{ $document->description }}
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
            <flux:text class="text-sm text-zinc-500">
                Keine Dokumente vorhanden.
            </flux:text>
        @endforelse
    </div>
@endif
