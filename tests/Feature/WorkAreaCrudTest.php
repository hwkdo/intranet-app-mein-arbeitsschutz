<?php

declare(strict_types=1);

use App\Models\User;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Category;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Subcategory;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\WorkArea;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can create a work area', function () {
    $workArea = WorkArea::factory()->create([
        'name' => 'Test Arbeitsbereich',
        'icon' => 'wrench-screwdriver',
        'sort_order' => 1,
    ]);

    expect($workArea)->toBeInstanceOf(WorkArea::class);
    expect($workArea->name)->toBe('Test Arbeitsbereich');
    expect($workArea->icon)->toBe('wrench-screwdriver');
    expect($workArea->sort_order)->toBe(1);
});

it('can update a work area', function () {
    $workArea = WorkArea::factory()->create([
        'name' => 'Test Arbeitsbereich',
        'icon' => 'wrench-screwdriver',
        'sort_order' => 1,
    ]);

    $workArea->update([
        'name' => 'Aktualisierter Arbeitsbereich',
        'icon' => 'briefcase',
        'sort_order' => 2,
    ]);

    expect($workArea->fresh()->name)->toBe('Aktualisierter Arbeitsbereich');
    expect($workArea->fresh()->icon)->toBe('briefcase');
    expect($workArea->fresh()->sort_order)->toBe(2);
});

it('can delete a work area', function () {
    $workArea = WorkArea::factory()->create();

    $workArea->delete();

    expect(WorkArea::find($workArea->id))->toBeNull();
});

it('creates subcategory automatically when work area is created via admin', function () {
    $workAreasCategory = Category::query()->where('key', 'work_areas')->first();

    $workArea = WorkArea::factory()->create([
        'name' => 'Test Arbeitsbereich',
        'icon' => 'wrench-screwdriver',
        'sort_order' => 1,
    ]);

    // Manually create subcategory (as the admin interface does)
    Subcategory::firstOrCreate(
        [
            'category_id' => $workAreasCategory->id,
            'source_type' => WorkArea::class,
            'source_id' => $workArea->id,
        ]
    );

    $subcategory = Subcategory::query()
        ->where('category_id', $workAreasCategory->id)
        ->where('source_type', WorkArea::class)
        ->where('source_id', $workArea->id)
        ->first();

    expect($subcategory)->toBeInstanceOf(Subcategory::class);
    expect($subcategory->source)->toBeInstanceOf(WorkArea::class);
    expect($subcategory->source->id)->toBe($workArea->id);
});

it('can list work areas in admin interface', function () {
    WorkArea::factory()->count(3)->create();

    Volt::test('apps.mein-arbeitsschutz.admin.index')
        ->assertSee('Arbeitsbereiche')
        ->set('activeTab', 'arbeitsbereiche')
        ->assertSee(WorkArea::first()->name);
});
