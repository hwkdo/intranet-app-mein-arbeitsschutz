<?php

use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Category;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Document;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\DocumentType;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Subcategory;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\WorkArea;
use function Livewire\Volt\{computed, mount, state, title};

state([
    'categoryKey' => null,
    'category' => null,
    'selectedSubcategoryId' => null,
    'selectedDocumentTypeId' => null,
]);

title(fn () => 'MeinArbeitsschutz - Dokumente: ' . ($this->category?->label ?? ''));

mount(function (string $categoryKey) {
    $category = Category::query()->where('key', $categoryKey)->first();

    if (! $category) {
        abort(404);
    }

    $this->categoryKey = $categoryKey;
    $this->category = $category;
});

$subcategories = computed(function () {
    $subcategories = Subcategory::query()
        ->where('category_id', $this->category->id)
        ->with('source')
        ->orderBy('id')
        ->get();

    return $subcategories;
});

$documentTypes = computed(function () {
    if ($this->category?->key !== 'work_areas') {
        return collect();
    }

    return DocumentType::query()
        ->orderBy('sort_order')
        ->get();
});

$documentsIndex = computed(function () {
    $documents = Document::query()
        ->whereHas('assignments', fn ($query) => $query->where('category_id', $this->category->id))
        ->with([
            'media',
            'assignments' => fn ($query) => $query->where('category_id', $this->category->id)->with('documentType'),
        ])
        ->latest()
        ->get();

    $byCategory = [];
    $bySubcategory = [];
    $byDocumentType = [];

    foreach ($documents as $document) {
        foreach ($document->assignments as $assignment) {
            if ($assignment->subcategory_id) {
                if ($assignment->document_type_id) {
                    $byDocumentType[$assignment->subcategory_id][$assignment->document_type_id][] = $document;
                } else {
                    $bySubcategory[$assignment->subcategory_id][] = $document;
                }
            } else {
                $byCategory[$assignment->category_id][] = $document;
            }
        }
    }

    return [
        'byCategory' => $byCategory,
        'bySubcategory' => $bySubcategory,
        'byDocumentType' => $byDocumentType,
    ];
});

$selectSubcategory = function (int $subcategoryId): void {
    $this->selectedSubcategoryId = $subcategoryId;
    $this->selectedDocumentTypeId = null;
};

$selectDocumentType = function (int $documentTypeId): void {
    $this->selectedDocumentTypeId = $documentTypeId;
};

$clearSelection = function (): void {
    $this->selectedSubcategoryId = null;
    $this->selectedDocumentTypeId = null;
};

$clearDocumentTypeSelection = function (): void {
    $this->selectedDocumentTypeId = null;
};

?>
<div>
<x-intranet-app-mein-arbeitsschutz::mein-arbeitsschutz-layout heading="MeinArbeitsschutz App" subheading="{{ $this->category->label }}">
    <div class="space-y-8">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                    <flux:icon icon="{{ $this->category->icon }}" class="h-5 w-5 text-blue-600 dark:text-blue-300" />
                </div>
                <flux:heading size="lg">{{ $this->category->label }}</flux:heading>
            </div>
            <flux:button :href="route('apps.mein-arbeitsschutz.documents')" variant="ghost" icon="arrow-left">
                Zurück
            </flux:button>
        </div>

        @php($documentsByCategory = $this->documentsIndex['byCategory'])
        @php($documentsBySubcategory = $this->documentsIndex['bySubcategory'])
        @php($documentsByDocumentType = $this->documentsIndex['byDocumentType'])
        @php($categoryDocuments = $documentsByCategory[$this->category->id] ?? [])
        @php($isWorkAreas = $this->category->key === 'work_areas')

        @if($this->subcategories->isNotEmpty())
            <div class="space-y-6">
                @if($this->selectedSubcategoryId === null)
                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        @if(!empty($categoryDocuments))
                            <flux:button
                                wire:key="subcategory-button-category"
                                wire:click="selectSubcategory(0)"
                                class="w-full justify-start py-4 px-6"
                                :variant="$this->selectedSubcategoryId === 0 ? 'primary' : 'outline'"
                            >
                                Ohne Unterkategorie
                            </flux:button>
                        @endif
                        @foreach($this->subcategories as $subcategory)
                            @php($subcategoryLabel = $subcategory->source?->name ?? $subcategory->source?->bezeichnung)
                            @php($isWorkArea = $subcategory->source instanceof \Hwkdo\IntranetAppMeinArbeitsschutz\Models\WorkArea)
                            @php($hasIcon = $isWorkArea && $subcategory->source->hasIcon())
                            @php($iconUrl = $hasIcon ? $subcategory->source->getIconUrl() : null)
                            <flux:button
                                wire:key="subcategory-button-{{ $subcategory->id }}"
                                wire:click="selectSubcategory({{ $subcategory->id }})"
                                class="w-full justify-start py-4 px-6"
                                :variant="$this->selectedSubcategoryId === $subcategory->id ? 'primary' : 'outline'"
                            >
                                <div class="flex items-center gap-4">
                                    @if($hasIcon && $iconUrl)
                                        <img
                                            src="{{ $iconUrl }}"
                                            alt="{{ $subcategoryLabel }}"
                                            class="h-10 w-10 flex-shrink-0 object-contain"
                                        />
                                    @elseif($isWorkArea)
                                        <flux:icon icon="wrench-screwdriver" class="h-10 w-10 flex-shrink-0" />
                                    @endif
                                    <span class="text-base font-medium">{{ $subcategoryLabel }}</span>
                                </div>
                            </flux:button>
                        @endforeach
                    </div>
                    <flux:text class="text-sm text-zinc-500">
                        Bitte zuerst einen {{ $isWorkAreas ? 'Fachbereich' : 'Standort' }} auswählen, um die Dokumente anzuzeigen.
                    </flux:text>
                @else
                    @php($selectedSubcategoryId = $this->selectedSubcategoryId)
                    @php($selectedSubcategory = $selectedSubcategoryId > 0 ? $this->subcategories->firstWhere('id', $selectedSubcategoryId) : null)
                    @php($selectedLabel = $selectedSubcategoryId === 0 ? 'Ohne Unterkategorie' : ($selectedSubcategory?->source?->name ?? $selectedSubcategory?->source?->bezeichnung))

                    @if($isWorkAreas && $selectedSubcategoryId > 0)
                        @if($this->selectedDocumentTypeId === null)
                            <div class="space-y-4">
                                <div class="flex items-center justify-between gap-3">
                                    <flux:heading size="md">{{ $selectedLabel }}</flux:heading>
                                    <flux:button variant="ghost" wire:click="clearSelection">
                                        Zurück zu Fachbereichen
                                    </flux:button>
                                </div>
                                <flux:text class="text-sm text-zinc-500">
                                    Bitte wählen Sie einen Dokumenttyp aus.
                                </flux:text>
                                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                    @foreach($this->documentTypes as $documentType)
                                        <flux:button
                                            wire:key="document-type-button-{{ $documentType->id }}"
                                            wire:click="selectDocumentType({{ $documentType->id }})"
                                            class="w-full justify-start py-4 px-6"
                                            variant="outline"
                                        >
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                                    <flux:icon icon="{{ $documentType->icon }}" class="h-5 w-5 text-blue-600 dark:text-blue-300" />
                                                </div>
                                                <span class="text-base font-medium">{{ $documentType->label }}</span>
                                            </div>
                                        </flux:button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            @php($selectedDocumentType = $this->documentTypes->firstWhere('id', $this->selectedDocumentTypeId))
                            @php($selectedDocuments = $documentsByDocumentType[$selectedSubcategoryId][$this->selectedDocumentTypeId] ?? [])

                            <div class="space-y-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <flux:heading size="md">{{ $selectedLabel }}</flux:heading>
                                        <flux:text class="text-zinc-500">→</flux:text>
                                        <flux:heading size="md">{{ $selectedDocumentType->label }}</flux:heading>
                                    </div>
                                    <flux:button variant="ghost" wire:click="clearDocumentTypeSelection">
                                        Zurück zu Dokumenttypen
                                    </flux:button>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    @forelse($selectedDocuments as $document)
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
                            </div>
                        @endif
                    @else
                        @php($selectedDocuments = $selectedSubcategoryId === 0 ? $categoryDocuments : ($documentsBySubcategory[$selectedSubcategoryId] ?? []))

                        <div class="flex items-center justify-between gap-3">
                            <flux:heading size="md">{{ $selectedLabel }}</flux:heading>
                            <flux:button variant="ghost" wire:click="clearSelection">
                                {{ $isWorkAreas ? 'Zurück zu Fachbereichen' : 'Zurück zu Standorten' }}
                            </flux:button>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            @forelse($selectedDocuments as $document)
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
                @endif
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @forelse($categoryDocuments as $document)
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
    </div>
</x-intranet-app-mein-arbeitsschutz::mein-arbeitsschutz-layout>
</div>