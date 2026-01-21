<?php

use App\Models\Standort;
use Flux\Flux;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Category;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Document;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\DocumentAssignment;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\DocumentType;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Subcategory;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\WorkArea;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

use function Livewire\Volt\computed;
use function Livewire\Volt\mount;
use function Livewire\Volt\rules;
use function Livewire\Volt\state;
use function Livewire\Volt\title;
use function Livewire\Volt\uses;

uses([WithFileUploads::class]);

title('MeinArbeitsschutz - Admin');

state([
    'activeTab' => 'uploads',
    'uploadTitles' => [],
    'uploadDescriptions' => [],
    'uploadFiles' => [],
    'selectedCategoryIds' => [],
    'selectedSubcategoryIds' => [],
    'selectedDocumentTypeIds' => [],
    'selectedStandortIds' => [],
    'documentSearch' => '',
    'documentSortField' => 'updated_at',
    'documentSortDirection' => 'desc',
    'showEditModal' => false,
    'editingDocumentId' => null,
    'editTitle' => '',
    'editDescription' => '',
    'editCategoryIds' => [],
    'editSubcategoryIds' => [],
    'editDocumentTypeIds' => [],
    'workAreaName' => '',
    'workAreaIconFile' => null,
    'workAreaSortOrder' => 0,
    'showWorkAreaModal' => false,
    'editingWorkAreaId' => null,
]);

rules([
    'uploadTitles' => 'required|array|min:1',
    'uploadTitles.*' => 'required|string|max:255',
    'uploadDescriptions' => 'nullable|array',
    'uploadDescriptions.*' => 'nullable|string|max:2000',
    'uploadFiles' => 'required|array|min:1',
    'uploadFiles.*' => 'required|file|mimes:pdf|max:20480',
    'selectedCategoryIds' => 'array',
    'selectedCategoryIds.*' => 'integer|exists:intranet_app_mein_arbeitsschutz_categories,id',
    'selectedSubcategoryIds' => 'array',
    'selectedSubcategoryIds.*' => 'integer|exists:intranet_app_mein_arbeitsschutz_subcategories,id',
    'selectedDocumentTypeIds' => 'array',
    'selectedDocumentTypeIds.*' => 'integer|exists:intranet_app_mein_arbeitsschutz_document_types,id',
]);

mount(function () {
    // Initialisiere Arrays explizit als leere Arrays
    $this->selectedCategoryIds = [];
    $this->selectedSubcategoryIds = [];
    $this->selectedDocumentTypeIds = [];

    $firstAidCategory = Category::query()->where('key', 'first_aid')->first();

    if ($firstAidCategory) {
        $this->selectedStandortIds = Subcategory::query()
            ->where('category_id', $firstAidCategory->id)
            ->where('source_type', Standort::class)
            ->pluck('source_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
});

$categories = computed(function () {
    return Category::query()
        ->orderBy('sort_order')
        ->get();
});

$subcategories = computed(function () {
    return Subcategory::query()
        ->with(['source', 'category'])
        ->orderBy('id')
        ->get()
        ->groupBy('category_id');
});

$standorte = computed(function () {
    return Standort::query()
        ->orderBy('name')
        ->get();
});

$workAreas = computed(function () {
    return WorkArea::query()
        ->orderBy('sort_order')
        ->get();
});

$documentTypes = computed(function () {
    return DocumentType::query()
        ->orderBy('sort_order')
        ->get();
});

$validationErrors = computed(function () {
    $errors = [];

    $selectedCategoryIds = is_array($this->selectedCategoryIds) ? $this->selectedCategoryIds : [];
    $selectedSubcategoryIds = is_array($this->selectedSubcategoryIds) ? $this->selectedSubcategoryIds : [];

    if (empty($selectedCategoryIds) && empty($selectedSubcategoryIds)) {
        $errors[] = 'Bitte wählen Sie mindestens eine Kategorie oder Unterkategorie aus.';

        return $errors;
    }

    $workAreasCategory = Category::query()->where('key', 'work_areas')->first();
    $firstAidCategory = Category::query()->where('key', 'first_aid')->first();
    $generalCategory = Category::query()->where('key', 'general')->first();

    // Prüfe: Wenn "Notsituation/Erste-Hilfe" ausgewählt, muss Standort ausgewählt sein
    if ($firstAidCategory && in_array($firstAidCategory->id, $selectedCategoryIds, true)) {
        $selectedSubcategories = collect();
        if (! empty($selectedSubcategoryIds)) {
            $selectedSubcategories = Subcategory::query()
                ->whereIn('id', $selectedSubcategoryIds)
                ->with('category')
                ->get();
        }
        $firstAidSubcategories = $selectedSubcategories->filter(fn ($sub) => $sub->category_id === $firstAidCategory->id);
        if ($firstAidSubcategories->isEmpty()) {
            $errors[] = 'Für die Kategorie "Notsituation/Erste-Hilfe" muss mindestens ein Standort ausgewählt werden.';
        }
    }

    // Prüfe: Wenn "Arbeitsbereiche" ausgewählt, muss Arbeitsbereich ausgewählt sein
    if ($workAreasCategory && in_array($workAreasCategory->id, $selectedCategoryIds, true)) {
        $selectedSubcategories = collect();
        if (! empty($selectedSubcategoryIds)) {
            $selectedSubcategories = Subcategory::query()
                ->whereIn('id', $selectedSubcategoryIds)
                ->with('category')
                ->get();
        }
        $workAreasSubcategories = $selectedSubcategories->filter(fn ($sub) => $sub->category_id === $workAreasCategory->id);
        if ($workAreasSubcategories->isEmpty()) {
            $errors[] = 'Für die Kategorie "Arbeitsbereiche" muss mindestens ein Arbeitsbereich ausgewählt werden.';
        } else {
            // Prüfe: Wenn Arbeitsbereich-Unterkategorien ausgewählt, muss Dokumenttyp ausgewählt sein
            $workAreaSubcategories = $workAreasSubcategories->filter(fn ($sub) => $sub->source_type === WorkArea::class);
            $selectedDocumentTypeIds = is_array($this->selectedDocumentTypeIds) ? $this->selectedDocumentTypeIds : [];
            if ($workAreaSubcategories->isNotEmpty() && empty($selectedDocumentTypeIds)) {
                $errors[] = 'Für Arbeitsbereiche muss ein Dokumenttyp ausgewählt werden.';
            }
        }
    }

    // Prüfe: Wenn Arbeitsbereich-Unterkategorien ausgewählt (auch ohne "Arbeitsbereiche" Kategorie), muss Dokumenttyp ausgewählt sein
    if (! empty($selectedSubcategoryIds)) {
        $selectedSubcategories = Subcategory::query()
            ->whereIn('id', $selectedSubcategoryIds)
            ->with('category')
            ->get();
        $workAreaSubcategories = $selectedSubcategories->filter(fn ($sub) => $sub->source_type === WorkArea::class);
        $selectedDocumentTypeIds = is_array($this->selectedDocumentTypeIds) ? $this->selectedDocumentTypeIds : [];
        if ($workAreaSubcategories->isNotEmpty() && empty($selectedDocumentTypeIds)) {
            $errors[] = 'Für Arbeitsbereiche muss ein Dokumenttyp ausgewählt werden.';
        }
    }

    return $errors;
});

$documents = computed(function () {
    $query = Document::query()
        ->with([
            'assignments.category',
            'assignments.subcategory.source',
            'assignments.documentType',
            'uploadedBy',
        ]);

    // Suche in Titel und Beschreibung
    if (! empty($this->documentSearch)) {
        $search = $this->documentSearch;
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Sortierung
    $query->orderBy($this->documentSortField, $this->documentSortDirection);

    return $query->get();
});

$canUpload = computed(function () {
    return empty($this->validationErrors);
});

$updatedUploadFiles = function (): void {
    $titles = $this->uploadTitles ?? [];
    $descriptions = $this->uploadDescriptions ?? [];

    foreach ($this->uploadFiles as $index => $file) {
        if (! array_key_exists($index, $titles) || $titles[$index] === '') {
            $titles[$index] = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        }

        // Beschreibung ist optional, setze nur wenn nicht vorhanden
        if (! array_key_exists($index, $descriptions)) {
            $descriptions[$index] = null;
        }
    }

    $indices = array_keys($this->uploadFiles);
    $titles = array_intersect_key($titles, array_flip($indices));
    $descriptions = array_intersect_key($descriptions, array_flip($indices));

    $this->uploadTitles = $titles;
    $this->uploadDescriptions = $descriptions;
};

$saveUploads = function () {
    // Zusätzliche manuelle Validierung ZUERST (vor computed property)
    $selectedCategoryIds = is_array($this->selectedCategoryIds) ? array_map('intval', $this->selectedCategoryIds) : [];
    $selectedSubcategoryIds = is_array($this->selectedSubcategoryIds) ? array_map('intval', $this->selectedSubcategoryIds) : [];

    $firstAidCategory = Category::query()->where('key', 'first_aid')->first();
    $workAreasCategory = Category::query()->where('key', 'work_areas')->first();

    // Prüfe: Wenn "Notsituation/Erste-Hilfe" ausgewählt, muss Standort ausgewählt sein
    if ($firstAidCategory && in_array((int) $firstAidCategory->id, $selectedCategoryIds, true)) {
        $selectedSubcategories = collect();
        if (! empty($selectedSubcategoryIds)) {
            $selectedSubcategories = Subcategory::query()
                ->whereIn('id', $selectedSubcategoryIds)
                ->with('category')
                ->get();
        }
        $firstAidSubcategories = $selectedSubcategories->filter(fn ($sub) => $sub->category_id === $firstAidCategory->id);
        if ($firstAidSubcategories->isEmpty()) {
            Flux::toast(text: 'Für die Kategorie "Notsituation/Erste-Hilfe" muss mindestens ein Standort ausgewählt werden.', variant: 'error');
            $this->addError('categorySelection', 'Für die Kategorie "Notsituation/Erste-Hilfe" muss mindestens ein Standort ausgewählt werden.');

            return;
        }
    }

    // Prüfe: Wenn "Arbeitsbereiche" ausgewählt, muss Arbeitsbereich ausgewählt sein
    if ($workAreasCategory && in_array((int) $workAreasCategory->id, $selectedCategoryIds, true)) {
        $selectedSubcategories = collect();
        if (! empty($selectedSubcategoryIds)) {
            $selectedSubcategories = Subcategory::query()
                ->whereIn('id', $selectedSubcategoryIds)
                ->with('category')
                ->get();
        }
        $workAreasSubcategories = $selectedSubcategories->filter(fn ($sub) => $sub->category_id === $workAreasCategory->id);
        if ($workAreasSubcategories->isEmpty()) {
            Flux::toast(text: 'Für die Kategorie "Arbeitsbereiche" muss mindestens ein Arbeitsbereich ausgewählt werden.', variant: 'error');
            $this->addError('categorySelection', 'Für die Kategorie "Arbeitsbereiche" muss mindestens ein Arbeitsbereich ausgewählt werden.');

            return;
        }

        // Prüfe: Wenn Arbeitsbereich-Unterkategorien ausgewählt, muss Dokumenttyp ausgewählt sein
        $workAreaSubcategories = $workAreasSubcategories->filter(fn ($sub) => $sub->source_type === WorkArea::class);
        $selectedDocumentTypeIds = is_array($this->selectedDocumentTypeIds) ? $this->selectedDocumentTypeIds : [];
        if ($workAreaSubcategories->isNotEmpty() && empty($selectedDocumentTypeIds)) {
            Flux::toast(text: 'Für Arbeitsbereiche muss ein Dokumenttyp ausgewählt werden.', variant: 'error');
            $this->addError('documentTypeSelection', 'Für Arbeitsbereiche muss ein Dokumenttyp ausgewählt werden.');

            return;
        }
    }

    // Prüfe die computed property Validierungsfehler
    $validationErrors = $this->validationErrors;
    if (! empty($validationErrors)) {
        foreach ($validationErrors as $error) {
            Flux::toast(text: $error, variant: 'error');
        }
        $this->addError('categorySelection', $validationErrors[0]);

        return;
    }

    // Dann die Standard-Validierung
    try {
        $this->validate();
    } catch (\Illuminate\Validation\ValidationException $e) {
        foreach ($e->errors() as $field => $messages) {
            foreach ($messages as $message) {
                Flux::toast(text: $message, variant: 'error');
            }
        }

        return;
    }

    $selectedSubcategories = collect();
    $selectedSubcategoryIds = is_array($this->selectedSubcategoryIds) ? $this->selectedSubcategoryIds : [];
    if (! empty($selectedSubcategoryIds)) {
        $selectedSubcategories = Subcategory::query()
            ->whereIn('id', $selectedSubcategoryIds)
            ->with('category')
            ->get();
    }

    foreach ($this->uploadFiles as $index => $uploadFile) {
        $title = $this->uploadTitles[$index] ?? pathinfo($uploadFile->getClientOriginalName(), PATHINFO_FILENAME);
        $description = $this->uploadDescriptions[$index] ?? null;

        $document = Document::create([
            'title' => Str::limit($title, 255, ''),
            'description' => $description,
            'uploaded_by' => Auth::id(),
        ]);

        $document->addMedia($uploadFile->getRealPath())
            ->usingName($document->title)
            ->usingFileName($uploadFile->getClientOriginalName())
            ->toMediaCollection('documents');

        // Kategorien ohne Unterkategorien (z.B. "Allgemeine Dokumente")
        $selectedCategoryIds = is_array($this->selectedCategoryIds) ? $this->selectedCategoryIds : [];
        foreach ($selectedCategoryIds as $categoryId) {
            $category = Category::find($categoryId);

            // Nur erstellen, wenn keine Unterkategorien für diese Kategorie ausgewählt sind
            $hasSubcategoriesForCategory = $selectedSubcategories->contains(fn ($sub) => $sub->category_id === $categoryId);

            if (! $hasSubcategoriesForCategory) {
                DocumentAssignment::firstOrCreate([
                    'document_id' => $document->id,
                    'category_id' => $categoryId,
                    'subcategory_id' => null,
                    'document_type_id' => null,
                ]);
            }
        }

        // Unterkategorien (Standorte oder Arbeitsbereiche)
        foreach ($selectedSubcategories as $subcategory) {
            $isWorkArea = $subcategory->source_type === WorkArea::class;

            $selectedDocumentTypeIds = is_array($this->selectedDocumentTypeIds) ? $this->selectedDocumentTypeIds : [];
            if ($isWorkArea && ! empty($selectedDocumentTypeIds)) {
                // Für Arbeitsbereiche: Erstelle Assignment für jeden ausgewählten Dokumenttyp
                foreach ($selectedDocumentTypeIds as $documentTypeId) {
                    DocumentAssignment::firstOrCreate([
                        'document_id' => $document->id,
                        'category_id' => $subcategory->category_id,
                        'subcategory_id' => $subcategory->id,
                        'document_type_id' => $documentTypeId,
                    ]);
                }
            } else {
                // Für Standorte oder ohne Dokumenttypen: Erstelle Assignment ohne Dokumenttyp
                DocumentAssignment::firstOrCreate([
                    'document_id' => $document->id,
                    'category_id' => $subcategory->category_id,
                    'subcategory_id' => $subcategory->id,
                    'document_type_id' => null,
                ]);
            }
        }
    }

    $this->reset([
        'uploadTitles',
        'uploadDescriptions',
        'uploadFiles',
        'selectedCategoryIds',
        'selectedSubcategoryIds',
        'selectedDocumentTypeIds',
    ]);

    // Setze selectedCategoryIds explizit auf leeres Array, um sicherzustellen, dass keine Kategorien ausgewählt sind
    $this->selectedCategoryIds = [];
    $this->selectedSubcategoryIds = [];
    $this->selectedDocumentTypeIds = [];

    Flux::toast(text: 'Dokumente erfolgreich hochgeladen.', variant: 'success');
};

$saveMarkings = function () {
    $firstAidCategory = Category::query()->where('key', 'first_aid')->first();
    $workAreasCategory = Category::query()->where('key', 'work_areas')->first();

    if ($firstAidCategory) {
        $selectedStandortIds = collect($this->selectedStandortIds)->map(fn ($id) => (int) $id)->all();
        $existingIds = Subcategory::query()
            ->where('category_id', $firstAidCategory->id)
            ->where('source_type', Standort::class)
            ->pluck('source_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $toCreate = array_diff($selectedStandortIds, $existingIds);
        $toDelete = array_diff($existingIds, $selectedStandortIds);

        if (! empty($toDelete)) {
            Subcategory::query()
                ->where('category_id', $firstAidCategory->id)
                ->where('source_type', Standort::class)
                ->whereIn('source_id', $toDelete)
                ->delete();
        }

        foreach ($toCreate as $standortId) {
            Subcategory::create([
                'category_id' => $firstAidCategory->id,
                'source_type' => Standort::class,
                'source_id' => $standortId,
            ]);
        }
    }


    Flux::toast(text: 'Markierungen gespeichert.', variant: 'success');
};

$sortDocuments = function (string $field): void {
    if ($this->documentSortField === $field) {
        $this->documentSortDirection = $this->documentSortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        $this->documentSortField = $field;
        $this->documentSortDirection = 'asc';
    }
};

$deleteDocument = function (int $documentId): void {
    $document = Document::find($documentId);

    if (! $document) {
        Flux::toast(text: 'Dokument nicht gefunden.', variant: 'error');

        return;
    }

    $document->delete();

    Flux::toast(text: 'Dokument erfolgreich gelöscht.', variant: 'success');
};

$editDocument = function (int $documentId): void {
    $document = Document::with(['assignments.category', 'assignments.subcategory', 'assignments.documentType'])->find($documentId);

    if (! $document) {
        Flux::toast(text: 'Dokument nicht gefunden.', variant: 'error');

        return;
    }

    $this->editingDocumentId = $documentId;
    $this->editTitle = $document->title;
    $this->editDescription = $document->description ?? '';

    // Sammle alle Kategorien, Unterkategorien und Dokumenttypen
    $this->editCategoryIds = $document->assignments->pluck('category_id')->unique()->filter()->values()->all();
    $this->editSubcategoryIds = $document->assignments->pluck('subcategory_id')->unique()->filter()->values()->all();
    $this->editDocumentTypeIds = $document->assignments->pluck('document_type_id')->unique()->filter()->values()->all();

    $this->showEditModal = true;
};

$cancelEdit = function (): void {
    $this->showEditModal = false;
    $this->reset(['editingDocumentId', 'editTitle', 'editDescription', 'editCategoryIds', 'editSubcategoryIds', 'editDocumentTypeIds']);
};

$saveEdit = function (): void {
    $document = Document::find($this->editingDocumentId);

    if (! $document) {
        Flux::toast(text: 'Dokument nicht gefunden.', variant: 'error');

        return;
    }

    // Validierung
    if (empty($this->editTitle)) {
        Flux::toast(text: 'Bitte geben Sie einen Titel ein.', variant: 'error');

        return;
    }

    if (empty($this->editCategoryIds) && empty($this->editSubcategoryIds)) {
        Flux::toast(text: 'Bitte wählen Sie mindestens eine Kategorie oder Unterkategorie aus.', variant: 'error');

        return;
    }

    // Dokument aktualisieren
    $document->update([
        'title' => $this->editTitle,
        'description' => $this->editDescription,
    ]);

    // Alle bisherigen Assignments löschen
    $document->assignments()->delete();

    // Neue Assignments erstellen
    $selectedSubcategories = collect();
    if (! empty($this->editSubcategoryIds)) {
        $selectedSubcategories = Subcategory::query()
            ->whereIn('id', $this->editSubcategoryIds)
            ->with('category')
            ->get();
    }

    // Kategorien ohne Unterkategorien
    foreach ($this->editCategoryIds as $categoryId) {
        $hasSubcategoriesForCategory = $selectedSubcategories->contains(fn ($sub) => $sub->category_id === $categoryId);

        if (! $hasSubcategoriesForCategory) {
            DocumentAssignment::create([
                'document_id' => $document->id,
                'category_id' => $categoryId,
                'subcategory_id' => null,
                'document_type_id' => null,
            ]);
        }
    }

    // Unterkategorien
    foreach ($selectedSubcategories as $subcategory) {
        $isWorkArea = $subcategory->source_type === WorkArea::class;

        if ($isWorkArea && ! empty($this->editDocumentTypeIds)) {
            foreach ($this->editDocumentTypeIds as $documentTypeId) {
                DocumentAssignment::create([
                    'document_id' => $document->id,
                    'category_id' => $subcategory->category_id,
                    'subcategory_id' => $subcategory->id,
                    'document_type_id' => $documentTypeId,
                ]);
            }
        } else {
            DocumentAssignment::create([
                'document_id' => $document->id,
                'category_id' => $subcategory->category_id,
                'subcategory_id' => $subcategory->id,
                'document_type_id' => null,
            ]);
        }
    }

    $this->showEditModal = false;
    $this->reset(['editingDocumentId', 'editTitle', 'editDescription', 'editCategoryIds', 'editSubcategoryIds', 'editDocumentTypeIds']);

    Flux::toast(text: 'Dokument erfolgreich aktualisiert.', variant: 'success');
};

$createWorkArea = function (): void {
    $this->editingWorkAreaId = null;
    $this->workAreaName = '';
    $this->workAreaIconFile = null;
    $this->workAreaSortOrder = 0;
    $this->showWorkAreaModal = true;
};

$editWorkArea = function (int $id): void {
    $workArea = WorkArea::findOrFail($id);
    $this->editingWorkAreaId = $id;
    $this->workAreaName = $workArea->name;
    $this->workAreaIconFile = null;
    $this->workAreaSortOrder = $workArea->sort_order;
    $this->showWorkAreaModal = true;
};

$saveWorkArea = function (): void {
    $this->validate([
        'workAreaName' => 'required|string|max:255',
        'workAreaIconFile' => 'nullable|mimes:svg|max:1024',
        'workAreaSortOrder' => 'required|integer|min:0',
    ]);

    $workArea = $this->editingWorkAreaId
        ? WorkArea::findOrFail($this->editingWorkAreaId)
        : new WorkArea();

    $workArea->name = $this->workAreaName;
    $workArea->sort_order = $this->workAreaSortOrder;
    $workArea->save();

    // Icon hochladen, falls vorhanden
    if ($this->workAreaIconFile) {
        $workArea->setIcon($this->workAreaIconFile);
    }

    // Erstelle/aktualisiere automatisch Subcategory für work_areas Category
    $workAreasCategory = Category::query()->where('key', 'work_areas')->first();
    if ($workAreasCategory) {
        Subcategory::firstOrCreate(
            [
                'category_id' => $workAreasCategory->id,
                'source_type' => WorkArea::class,
                'source_id' => $workArea->id,
            ]
        );
    }

    $isUpdate = (bool) $this->editingWorkAreaId;
    $this->showWorkAreaModal = false;
    $this->reset(['editingWorkAreaId', 'workAreaName', 'workAreaIconFile', 'workAreaSortOrder']);

    Flux::toast(text: $isUpdate ? 'Arbeitsbereich aktualisiert.' : 'Arbeitsbereich erstellt.', variant: 'success');
};

$deleteWorkAreaIcon = function (): void {
    if ($this->editingWorkAreaId) {
        $workArea = WorkArea::findOrFail($this->editingWorkAreaId);
        $workArea->setIcon(null);
        Flux::toast(text: 'Icon gelöscht.', variant: 'success');
    }
};

$deleteWorkArea = function (int $id): void {
    $workArea = WorkArea::findOrFail($id);
    // Subcategory wird automatisch via CASCADE gelöscht
    $workArea->delete();

    Flux::toast(text: 'Arbeitsbereich gelöscht.', variant: 'success');
};

?>
<div>
<x-intranet-app-mein-arbeitsschutz::mein-arbeitsschutz-layout heading="MeinArbeitsschutz App" subheading="Admin">
    <flux:tab.group>
        <flux:tabs wire:model="activeTab">
            <flux:tab name="uploads" icon="arrow-up-tray">Uploads</flux:tab>
            <flux:tab name="dokumente" icon="document-text">Dokumente</flux:tab>
            <flux:tab name="arbeitsbereiche" icon="wrench-screwdriver">Arbeitsbereiche</flux:tab>
            <flux:tab name="markierungen" icon="map-pin">Markierungen</flux:tab>
            <flux:tab name="einstellungen" icon="cog-6-tooth">Einstellungen</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="uploads">
            <flux:card>
                <flux:heading size="lg" class="mb-4">Dokumente hochladen</flux:heading>
                <flux:text class="mb-6">
                    Laden Sie PDF-Dokumente hoch und ordnen Sie sie Kategorien oder Unterkategorien zu.
                </flux:text>

                <form wire:submit="saveUploads" class="space-y-6">
                    <flux:field>
                        <flux:label>Dateien (PDF)</flux:label>
                        <input
                            type="file"
                            wire:model="uploadFiles"
                            multiple
                            class="block w-full text-sm text-zinc-900 border border-zinc-300 rounded-lg cursor-pointer bg-zinc-50 dark:text-zinc-400 focus:outline-none dark:bg-zinc-700 dark:border-zinc-600 dark:placeholder-zinc-400"
                        />
                        <flux:error name="uploadFiles" />
                        <div wire:loading wire:target="uploadFiles" class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                            Dateien werden hochgeladen...
                        </div>
                    </flux:field>

                    @if(!empty($this->uploadFiles))
                        <flux:card class="space-y-4">
                            <flux:heading size="md">Datei-Details</flux:heading>
                            <flux:accordion transition>
                                @foreach($this->uploadFiles as $index => $file)
                                    @php($defaultTitle = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                                    @php($currentTitle = $this->uploadTitles[$index] ?? $defaultTitle)
                                    <flux:accordion.item wire:key="upload-file-{{ $index }}">
                                        <flux:accordion.heading>
                                            {{ $currentTitle }}
                                            <span class="ml-2 text-sm text-zinc-500">
                                                {{ $file->getClientOriginalName() }}
                                            </span>
                                        </flux:accordion.heading>
                                        <flux:accordion.content>
                                            <div class="space-y-3">
                                                <flux:input
                                                    wire:model="uploadTitles.{{ $index }}"
                                                    label="Titel"
                                                    placeholder="Titel eingeben"
                                                />
                                                @error("uploadTitles.$index")
                                                    <div class="text-sm text-red-600">{{ $message }}</div>
                                                @enderror
                                                <flux:textarea
                                                    wire:model="uploadDescriptions.{{ $index }}"
                                                    label="Beschreibung"
                                                    rows="auto"
                                                />
                                                @error("uploadDescriptions.$index")
                                                    <div class="text-sm text-red-600">{{ $message }}</div>
                                                @enderror
                                                @error("uploadFiles.$index")
                                                    <div class="text-sm text-red-600">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </flux:accordion.content>
                                    </flux:accordion.item>
                                @endforeach
                            </flux:accordion>
                        </flux:card>
                    @endif

                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <flux:heading size="md">Kategorien</flux:heading>
                            <span class="text-sm text-red-600 dark:text-red-400">*</span>
                        </div>
                        <flux:text class="text-sm text-zinc-500">
                            Bitte wählen Sie mindestens eine Kategorie aus.
                        </flux:text>
                        <div class="grid gap-3 md:grid-cols-2">
                            @foreach($this->categories as $category)
                                @php($selectedCategoryIds = is_array($this->selectedCategoryIds) ? $this->selectedCategoryIds : [])
                                @php($isSelected = in_array($category->id, $selectedCategoryIds, true))
                                @php($needsSubcategory = $category->key === 'first_aid' || $category->key === 'work_areas')
                                @php($hasRequiredSubcategory = $needsSubcategory && !empty($this->selectedSubcategoryIds))
                                @php($hasError = !empty($this->validationErrors) && $isSelected && $needsSubcategory && !$hasRequiredSubcategory)
                                <label class="flex items-center gap-3 rounded-lg border px-4 py-3 {{ $hasError ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : '' }}">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedCategoryIds"
                                        value="{{ $category->id }}"
                                        class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $category->label }}
                                        @if($needsSubcategory)
                                            <span class="text-xs text-zinc-500">(benötigt {{ $category->key === 'first_aid' ? 'Standort' : 'Arbeitsbereich' }})</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <flux:error name="selectedCategoryIds.*" />
                        @if(!empty($this->validationErrors) && empty($this->selectedCategoryIds) && empty($this->selectedSubcategoryIds))
                            <flux:callout variant="danger" icon="exclamation-triangle">
                                Bitte wählen Sie mindestens eine Kategorie oder Unterkategorie aus.
                            </flux:callout>
                        @endif
                    </div>

                    <div class="space-y-4">
                        @php($selectedCategoryIds = is_array($this->selectedCategoryIds) ? collect($this->selectedCategoryIds)->map(fn ($id) => (int) $id)->all() : [])
                        @php($firstAidCategory = $this->categories->firstWhere('key', 'first_aid'))
                        @php($workAreasCategory = $this->categories->firstWhere('key', 'work_areas'))
                        @php($hasFirstAidSelected = $firstAidCategory && in_array($firstAidCategory->id, $selectedCategoryIds, true))
                        @php($hasWorkAreasSelected = $workAreasCategory && in_array($workAreasCategory->id, $selectedCategoryIds, true))
                        @php($selectedSubcategoryIds = is_array($this->selectedSubcategoryIds) ? collect($this->selectedSubcategoryIds)->map(fn ($id) => (int) $id)->all() : [])
                        @php($selectedSubcategories = $this->subcategories->flatten()->filter(fn ($sub) => in_array($sub->id, $selectedSubcategoryIds, true)))
                        @php($firstAidSubcategories = $selectedSubcategories->filter(fn ($sub) => $sub->category_id === $firstAidCategory?->id))
                        @php($needsFirstAidSubcategory = $hasFirstAidSelected && $firstAidSubcategories->isEmpty())
                        @php($needsWorkAreasSubcategory = $hasWorkAreasSelected && $selectedSubcategories->filter(fn ($sub) => $sub->category_id === $workAreasCategory?->id)->isEmpty())

                        <div class="flex items-center gap-2">
                            <flux:heading size="md">Unterkategorien</flux:heading>
                            @if($hasFirstAidSelected || $hasWorkAreasSelected)
                                <span class="text-sm text-red-600 dark:text-red-400">*</span>
                            @endif
                        </div>
                        @if(empty($selectedCategoryIds))
                            <flux:text class="text-sm text-zinc-500">
                                Bitte wählen Sie zuerst eine Kategorie, um die Unterkategorien anzuzeigen.
                            </flux:text>
                        @elseif($needsFirstAidSubcategory || $needsWorkAreasSubcategory)
                            <flux:callout variant="warning" icon="exclamation-triangle">
                                @if($needsFirstAidSubcategory)
                                    Für "Notsituation/Erste-Hilfe" muss mindestens ein Standort ausgewählt werden.
                                @endif
                                @if($needsFirstAidSubcategory && $needsWorkAreasSubcategory)
                                    <br>
                                @endif
                                @if($needsWorkAreasSubcategory)
                                    Für "Arbeitsbereiche" muss mindestens ein Arbeitsbereich ausgewählt werden.
                                @endif
                            </flux:callout>
                        @endif
                        @foreach($this->categories as $category)
                            @php($categorySubcategories = $this->subcategories[$category->id] ?? collect())
                            @if($categorySubcategories->isNotEmpty() && in_array($category->id, $selectedCategoryIds, true))
                                <div class="space-y-2">
                                    <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-300">
                                        {{ $category->label }}
                                    </flux:text>
                                    <div class="grid gap-3 md:grid-cols-2">
                                        @foreach($categorySubcategories as $subcategory)
                                            <label class="flex items-center gap-3 rounded-lg border px-4 py-3" wire:key="subcategory-{{ $subcategory->id }}">
                                                <input
                                                    type="checkbox"
                                                    wire:model.live="selectedSubcategoryIds"
                                                    value="{{ $subcategory->id }}"
                                                    class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                                                />
                                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                    {{ $subcategory->source?->name ?? $subcategory->source?->bezeichnung ?? 'Unbekannt' }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                        <flux:error name="selectedSubcategoryIds.*" />
                    </div>

                    @php($hasWorkAreaSubcategories = $selectedSubcategories->contains(fn ($sub) => $sub->source_type === \Hwkdo\IntranetAppMeinArbeitsschutz\Models\WorkArea::class))
                    @php($showDocumentTypes = $hasWorkAreasSelected && $hasWorkAreaSubcategories)
                    @php($needsDocumentType = $showDocumentTypes && empty($this->selectedDocumentTypeIds))

                    @if($showDocumentTypes)
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <flux:heading size="md">Dokumenttypen</flux:heading>
                                <span class="text-sm text-red-600 dark:text-red-400">*</span>
                            </div>
                            @if($needsDocumentType)
                                <flux:callout variant="danger" icon="exclamation-triangle">
                                    Für Arbeitsbereiche muss mindestens ein Dokumenttyp ausgewählt werden.
                                </flux:callout>
                            @else
                                <flux:text class="text-sm text-zinc-500">
                                    Bitte wählen Sie mindestens einen Dokumenttyp für die ausgewählten Arbeitsbereiche.
                                </flux:text>
                            @endif
                            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                                @foreach($this->documentTypes as $documentType)
                                    <label class="flex items-center gap-3 rounded-lg border px-4 py-3 {{ $needsDocumentType ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : '' }}" wire:key="document-type-{{ $documentType->id }}">
                                        <input
                                            type="checkbox"
                                            wire:model.live="selectedDocumentTypeIds"
                                            value="{{ $documentType->id }}"
                                            class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <div class="flex items-center gap-2">
                                            <flux:icon icon="{{ $documentType->icon }}" class="h-5 w-5 text-zinc-600 dark:text-zinc-400" />
                                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $documentType->label }}
                                            </span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            <flux:error name="selectedDocumentTypeIds.*" />
                        </div>
                    @endif

                    @if(!empty($this->validationErrors))
                        <flux:callout variant="danger" icon="exclamation-triangle">
                            <div class="space-y-1">
                                @foreach($this->validationErrors as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        </flux:callout>
                    @endif

                    <div class="flex gap-2">
                        <flux:button 
                            type="submit" 
                            variant="primary" 
                            icon="check"
                            :disabled="!$this->canUpload"
                        >
                            Hochladen
                        </flux:button>
                        @if(!$this->canUpload)
                            <flux:text class="flex items-center text-sm text-zinc-500">
                                Bitte füllen Sie alle Pflichtfelder aus.
                            </flux:text>
                        @endif
                    </div>
                </form>
            </flux:card>
        </flux:tab.panel>

        <flux:tab.panel name="dokumente">
            <flux:card>
                <flux:heading size="lg" class="mb-4">Dokumente verwalten</flux:heading>
                <flux:text class="mb-6">
                    Übersicht aller hochgeladenen Dokumente mit Bearbeitungs- und Löschfunktion.
                </flux:text>

                <div class="mb-6">
                    <flux:input
                        wire:model.live.debounce.300ms="documentSearch"
                        placeholder="Dokumente durchsuchen (Name oder Beschreibung)..."
                        icon="magnifying-glass"
                    />
                </div>

                @if($this->documents->isEmpty())
                    <flux:callout variant="info" icon="information-circle">
                        @if(!empty($this->documentSearch))
                            Keine Dokumente gefunden, die Ihrer Suche entsprechen.
                        @else
                            Es wurden noch keine Dokumente hochgeladen.
                        @endif
                    </flux:callout>
                @else
                    {{-- BACKUP: Funktionierende HTML-Tabelle (falls Flux nicht funktioniert)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-b border-zinc-200 dark:border-zinc-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                        <button wire:click="sortDocuments('title')" class="flex items-center gap-2 hover:text-blue-600">
                                            Name
                                            @if($this->documentSortField === 'title')
                                                <span class="text-xs">{{ $this->documentSortDirection === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-zinc-100">Kategorie</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                        <button wire:click="sortDocuments('updated_at')" class="flex items-center gap-2 hover:text-blue-600">
                                            Aktualisiert
                                            @if($this->documentSortField === 'updated_at')
                                                <span class="text-xs">{{ $this->documentSortDirection === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-zinc-100">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($this->documents as $document)
                                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50" wire:key="doc-{{ $document->id }}">
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $document->title }}</div>
                                            @if($document->description)
                                                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                                    {{ Str::limit($document->description, 60) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-1">
                                                @forelse($document->assignments->pluck('category')->unique('id')->filter() as $category)
                                                    <span class="inline-flex items-center rounded bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                        {{ $category->label }}
                                                    </span>
                                                @empty
                                                    <span class="text-sm text-zinc-400">-</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $document->updated_at->diffForHumans() }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex gap-2">
                                                <button 
                                                    wire:click="editDocument({{ $document->id }})"
                                                    class="inline-flex items-center rounded bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 ring-1 ring-inset ring-zinc-300 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700 dark:hover:bg-zinc-700"
                                                >
                                                    Bearbeiten
                                                </button>
                                                <button 
                                                    wire:click="deleteDocument({{ $document->id }})"
                                                    wire:confirm="Sind Sie sicher, dass Sie dieses Dokument löschen möchten?"
                                                    class="inline-flex items-center rounded bg-red-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-red-700"
                                                >
                                                    Löschen
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    --}}

                    {{-- Flux-Tabelle: Minimalistischer Ansatz ohne verschachtelte Flux-Komponenten --}}
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column 
                                sortable 
                                :sorted="$documentSortField === 'title'" 
                                :direction="$documentSortDirection" 
                                wire:click="sortDocuments('title')"
                            >
                                Name
                            </flux:table.column>
                            <flux:table.column>Kategorie</flux:table.column>
                            <flux:table.column 
                                sortable 
                                :sorted="$documentSortField === 'updated_at'" 
                                :direction="$documentSortDirection" 
                                wire:click="sortDocuments('updated_at')"
                            >
                                Aktualisiert
                            </flux:table.column>
                            <flux:table.column>Aktionen</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach($this->documents as $document)
                                <flux:table.row :key="$document->id">
                                    <flux:table.cell>
                                        <div class="font-medium">{{ $document->title }}</div>
                                        @if($document->description)
                                            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                                {{ Str::limit($document->description, 60) }}
                                            </div>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @php($categories = $document->assignments->pluck('category')->unique('id')->filter())
                                        @if($categories->isNotEmpty())
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($categories as $category)
                                                    <flux:badge size="sm">{{ $category->label }}</flux:badge>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-zinc-400">-</span>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        {{ $document->updated_at->diffForHumans() }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <div class="flex gap-2">
                                            <flux:button 
                                                size="sm" 
                                                variant="ghost" 
                                                icon="pencil"
                                                wire:click="editDocument({{ $document->id }})"
                                            >
                                                Bearbeiten
                                            </flux:button>
                                            <flux:button 
                                                size="sm" 
                                                variant="danger" 
                                                icon="trash"
                                                wire:click="deleteDocument({{ $document->id }})"
                                                wire:confirm="Sind Sie sicher, dass Sie dieses Dokument löschen möchten?"
                                            >
                                                Löschen
                                            </flux:button>
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>

                    <div class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
                        Gesamt: {{ $this->documents->count() }} Dokument(e)
                    </div>
                @endif
            </flux:card>
        </flux:tab.panel>

        <flux:tab.panel name="arbeitsbereiche">
            <flux:card>
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <flux:heading size="lg" class="mb-2">Arbeitsbereiche verwalten</flux:heading>
                        <flux:text>
                            Erstellen und verwalten Sie Arbeitsbereiche. Jeder erstellte Arbeitsbereich ist automatisch verfügbar.
                        </flux:text>
                    </div>
                    <flux:button wire:click="createWorkArea" variant="primary" icon="plus">
                        Neu erstellen
                    </flux:button>
                </div>

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Icon</flux:table.column>
                        <flux:table.column>Sortierung</flux:table.column>
                        <flux:table.column>Aktionen</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($this->workAreas as $workArea)
                            <flux:table.row :key="$workArea->id">
                                <flux:table.cell>
                                    <div class="font-medium">{{ $workArea->name }}</div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($workArea->hasIcon())
                                        <div class="flex items-center justify-center w-8 h-8">
                                            <img
                                                src="{{ $workArea->getIconUrl() }}"
                                                alt="Icon"
                                                class="max-w-full max-h-full object-contain"
                                            />
                                        </div>
                                    @else
                                        <span class="text-sm text-zinc-400">Kein Icon</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $workArea->sort_order }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex gap-2">
                                        <flux:button 
                                            size="sm" 
                                            variant="ghost" 
                                            icon="pencil"
                                            wire:click="editWorkArea({{ $workArea->id }})"
                                        >
                                            Bearbeiten
                                        </flux:button>
                                        <flux:button 
                                            size="sm" 
                                            variant="danger" 
                                            icon="trash"
                                            wire:click="deleteWorkArea({{ $workArea->id }})"
                                            wire:confirm="Sind Sie sicher, dass Sie diesen Arbeitsbereich löschen möchten?"
                                        >
                                            Löschen
                                        </flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                @if($this->workAreas->isEmpty())
                    <flux:callout variant="info" icon="information-circle" class="mt-4">
                        Noch keine Arbeitsbereiche vorhanden. Klicken Sie auf "Neu erstellen", um einen Arbeitsbereich anzulegen.
                    </flux:callout>
                @endif
            </flux:card>
        </flux:tab.panel>

        <flux:tab.panel name="markierungen">
            <flux:card>
                <flux:heading size="lg" class="mb-4">Unterkategorien markieren</flux:heading>
                <flux:text class="mb-6">
                    Wählen Sie die Standorte aus, die als Unterkategorien für "Notsituation/Erste Hilfe" angezeigt werden sollen.
                </flux:text>

                <form wire:submit="saveMarkings" class="space-y-8">
                    <div class="space-y-3">
                        <flux:heading size="md">Notsituation/Erste Hilfe</flux:heading>
                        <div class="grid gap-3 md:grid-cols-2">
                            @foreach($this->standorte as $standort)
                                <label class="flex items-center gap-3 rounded-lg border px-4 py-3" wire:key="standort-{{ $standort->id }}">
                                    <input
                                        type="checkbox"
                                        wire:model="selectedStandortIds"
                                        value="{{ $standort->id }}"
                                        class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $standort->name }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary" icon="check">
                            Markierungen speichern
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </flux:tab.panel>

        <flux:tab.panel name="einstellungen">
            <div style="min-height: 400px;">
                @livewire('intranet-app-base::admin-settings', [
                    'appIdentifier' => 'mein-arbeitsschutz',
                    'settingsModelClass' => '\Hwkdo\IntranetAppMeinArbeitsschutz\Models\IntranetAppMeinArbeitsschutzSettings',
                    'appSettingsClass' => '\Hwkdo\IntranetAppMeinArbeitsschutz\Data\AppSettings'
                ])
            </div>
        </flux:tab.panel>
    </flux:tab.group>
</x-intranet-app-mein-arbeitsschutz::mein-arbeitsschutz-layout>

{{-- Bearbeitungsmodal --}}
<flux:modal wire:model.self="showEditModal" class="md:w-[800px]">
    <form wire:submit="saveEdit" class="space-y-6">
        <flux:heading size="lg">Dokument bearbeiten</flux:heading>

        <flux:input
            wire:model="editTitle"
            label="Titel"
            placeholder="Titel eingeben"
            required
        />

        <flux:textarea
            wire:model="editDescription"
            label="Beschreibung"
            placeholder="Beschreibung eingeben (optional)"
            rows="auto"
        />

        <div class="space-y-4">
            <flux:heading size="md">Kategorien</flux:heading>
            <div class="grid gap-3 md:grid-cols-2">
                @foreach($this->categories as $category)
                    <label class="flex items-center gap-3 rounded-lg border px-4 py-3">
                        <input
                            type="checkbox"
                            wire:model.live="editCategoryIds"
                            value="{{ $category->id }}"
                            class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $category->label }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="space-y-4">
            <flux:heading size="md">Unterkategorien</flux:heading>
            @php($editCategoryIds = is_array($this->editCategoryIds) ? collect($this->editCategoryIds)->map(fn ($id) => (int) $id)->all() : [])
            @if(empty($editCategoryIds))
                <flux:text class="text-sm text-zinc-500">
                    Bitte wählen Sie zuerst eine Kategorie.
                </flux:text>
            @else
                @foreach($this->categories as $category)
                    @php($categorySubcategories = $this->subcategories[$category->id] ?? collect())
                    @if($categorySubcategories->isNotEmpty() && in_array($category->id, $editCategoryIds, true))
                        <div class="space-y-2">
                            <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-300">
                                {{ $category->label }}
                            </flux:text>
                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach($categorySubcategories as $subcategory)
                                    <label class="flex items-center gap-3 rounded-lg border px-4 py-3">
                                        <input
                                            type="checkbox"
                                            wire:model.live="editSubcategoryIds"
                                            value="{{ $subcategory->id }}"
                                            class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $subcategory->source?->name ?? $subcategory->source?->bezeichnung ?? 'Unbekannt' }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>

        @php($editSubcategoryIds = is_array($this->editSubcategoryIds) ? collect($this->editSubcategoryIds)->map(fn ($id) => (int) $id)->all() : [])
        @php($selectedEditSubcategories = $this->subcategories->flatten()->filter(fn ($sub) => in_array($sub->id, $editSubcategoryIds, true)))
        @php($hasWorkAreaEditSubcategories = $selectedEditSubcategories->contains(fn ($sub) => $sub->source_type === \Hwkdo\IntranetAppMeinArbeitsschutz\Models\WorkArea::class))

        @if($hasWorkAreaEditSubcategories)
            <div class="space-y-4">
                <flux:heading size="md">Dokumenttypen</flux:heading>
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                    @foreach($this->documentTypes as $documentType)
                        <label class="flex items-center gap-3 rounded-lg border px-4 py-3">
                            <input
                                type="checkbox"
                                wire:model.live="editDocumentTypeIds"
                                value="{{ $documentType->id }}"
                                class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                            />
                            <div class="flex items-center gap-2">
                                <flux:icon icon="{{ $documentType->icon }}" class="h-5 w-5 text-zinc-600 dark:text-zinc-400" />
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $documentType->label }}
                                </span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary" icon="check">
                Speichern
            </flux:button>
            <flux:button type="button" variant="ghost" wire:click="cancelEdit">
                Abbrechen
            </flux:button>
        </div>
    </form>
</flux:modal>

{{-- WorkArea Bearbeitungsmodal --}}
<flux:modal wire:model.self="showWorkAreaModal" class="md:w-[600px]">
    <form wire:submit="saveWorkArea" class="space-y-6">
        <flux:heading size="lg">{{ $editingWorkAreaId ? 'Arbeitsbereich bearbeiten' : 'Neuer Arbeitsbereich' }}</flux:heading>

        <flux:input
            wire:model="workAreaName"
            label="Name"
            placeholder="Name des Arbeitsbereichs eingeben"
            required
        />

        @php($currentWorkArea = $this->editingWorkAreaId ? WorkArea::find($this->editingWorkAreaId) : null)

        @if($currentWorkArea && $currentWorkArea->hasIcon())
            <div class="space-y-2">
                <flux:heading size="sm">Aktuelles Icon</flux:heading>
                <div class="flex items-center gap-4">
                    <div class="flex items-center justify-center w-16 h-16 border border-zinc-300 dark:border-zinc-700 rounded">
                        <img
                            src="{{ $currentWorkArea->getIconUrl() }}"
                            alt="Icon"
                            class="max-w-full max-h-full object-contain"
                        />
                    </div>
                    <flux:button
                        wire:click="deleteWorkAreaIcon"
                        variant="danger"
                        size="sm"
                        icon="trash"
                    >
                        Icon löschen
                    </flux:button>
                </div>
            </div>
        @endif

        <flux:input
            type="file"
            wire:model="workAreaIconFile"
            label="Icon"
            description="SVG-Datei für das Icon (max. 1MB)"
            accept=".svg"
        />

        <flux:input
            wire:model="workAreaSortOrder"
            type="number"
            label="Sortierung"
            placeholder="0"
            min="0"
            required
        />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary" icon="check">
                Speichern
            </flux:button>
            <flux:button 
                type="button" 
                variant="ghost" 
                wire:click="$set('showWorkAreaModal', false)"
            >
                Abbrechen
            </flux:button>
        </div>
    </form>
</flux:modal>
</div>