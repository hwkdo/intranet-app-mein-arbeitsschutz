<?php

declare(strict_types=1);

use App\Models\Standort;
use App\Models\User;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Category;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Document;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\DocumentAssignment;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\DocumentType;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Subcategory;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\WorkArea;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can create document with work areas category, work area and document type', function () {
    $workAreasCategory = Category::query()->where('key', 'work_areas')->first();
    $workArea = WorkArea::factory()->create();
    $documentType = DocumentType::query()->where('key', 'risk_assessment')->first();

    $subcategory = Subcategory::create([
        'category_id' => $workAreasCategory->id,
        'source_type' => WorkArea::class,
        'source_id' => $workArea->id,
    ]);

    $document = Document::factory()->create();

    $assignment = DocumentAssignment::create([
        'document_id' => $document->id,
        'category_id' => $workAreasCategory->id,
        'subcategory_id' => $subcategory->id,
        'document_type_id' => $documentType->id,
    ]);

    expect($assignment->document_type_id)->toBe($documentType->id);
    expect($assignment->documentType)->toBeInstanceOf(DocumentType::class);
    expect($assignment->documentType->key)->toBe('risk_assessment');
});

it('can create document with first aid category and standort without document type', function () {
    $firstAidCategory = Category::query()->where('key', 'first_aid')->first();
    $standort = Standort::factory()->create();

    $subcategory = Subcategory::create([
        'category_id' => $firstAidCategory->id,
        'source_type' => Standort::class,
        'source_id' => $standort->id,
    ]);

    $document = Document::factory()->create();

    $assignment = DocumentAssignment::create([
        'document_id' => $document->id,
        'category_id' => $firstAidCategory->id,
        'subcategory_id' => $subcategory->id,
        'document_type_id' => null,
    ]);

    expect($assignment->document_type_id)->toBeNull();
});

it('can create document with general category without subcategory or document type', function () {
    $generalCategory = Category::query()->where('key', 'general')->first();

    $document = Document::factory()->create();

    $assignment = DocumentAssignment::create([
        'document_id' => $document->id,
        'category_id' => $generalCategory->id,
        'subcategory_id' => null,
        'document_type_id' => null,
    ]);

    expect($assignment->subcategory_id)->toBeNull();
    expect($assignment->document_type_id)->toBeNull();
});

it('can create document with multiple categories', function () {
    $workAreasCategory = Category::query()->where('key', 'work_areas')->first();
    $generalCategory = Category::query()->where('key', 'general')->first();
    $workArea = WorkArea::factory()->create();
    $documentType = DocumentType::query()->where('key', 'operating_instructions')->first();

    $subcategory = Subcategory::create([
        'category_id' => $workAreasCategory->id,
        'source_type' => WorkArea::class,
        'source_id' => $workArea->id,
    ]);

    $document = Document::factory()->create();

    $assignment1 = DocumentAssignment::create([
        'document_id' => $document->id,
        'category_id' => $workAreasCategory->id,
        'subcategory_id' => $subcategory->id,
        'document_type_id' => $documentType->id,
    ]);

    $assignment2 = DocumentAssignment::create([
        'document_id' => $document->id,
        'category_id' => $generalCategory->id,
        'subcategory_id' => null,
        'document_type_id' => null,
    ]);

    expect($document->assignments)->toHaveCount(2);
    expect($assignment1->document_type_id)->toBe($documentType->id);
    expect($assignment2->document_type_id)->toBeNull();
});

it('enforces unique constraint on document assignments with document type', function () {
    $workAreasCategory = Category::query()->where('key', 'work_areas')->first();
    $workArea = WorkArea::factory()->create();
    $documentType = DocumentType::query()->where('key', 'risk_assessment')->first();

    $subcategory = Subcategory::create([
        'category_id' => $workAreasCategory->id,
        'source_type' => WorkArea::class,
        'source_id' => $workArea->id,
    ]);

    $document = Document::factory()->create();

    DocumentAssignment::create([
        'document_id' => $document->id,
        'category_id' => $workAreasCategory->id,
        'subcategory_id' => $subcategory->id,
        'document_type_id' => $documentType->id,
    ]);

    expect(fn () => DocumentAssignment::create([
        'document_id' => $document->id,
        'category_id' => $workAreasCategory->id,
        'subcategory_id' => $subcategory->id,
        'document_type_id' => $documentType->id,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('shows document types in work areas category view', function () {
    $workAreasCategory = Category::query()->where('key', 'work_areas')->first();
    $workArea = WorkArea::factory()->create();
    $documentType = DocumentType::query()->where('key', 'risk_assessment')->first();

    $subcategory = Subcategory::create([
        'category_id' => $workAreasCategory->id,
        'source_type' => WorkArea::class,
        'source_id' => $workArea->id,
    ]);

    Volt::test('apps.mein-arbeitsschutz.documents.show', $workAreasCategory->key)
        ->assertSee('Gefährdungsbeurteilungen')
        ->assertSee('Betriebsanweisungen')
        ->assertSee('Gefahrstoffregister')
        ->assertSee('Sicherheitsdatenblätter');
});

it('does not show document types in first aid category view', function () {
    $firstAidCategory = Category::query()->where('key', 'first_aid')->first();

    Volt::test('apps.mein-arbeitsschutz.documents.show', $firstAidCategory->key)
        ->assertDontSee('Gefährdungsbeurteilungen')
        ->assertDontSee('Betriebsanweisungen');
});
