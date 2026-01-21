<?php

use App\Models\Gvp;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Category;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Subcategory;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\WorkArea;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Finde alle GVPs mit kuerzel='FB'
        $gvps = Gvp::query()->where('kuerzel', 'FB')->get();

        if ($gvps->isEmpty()) {
            return;
        }

        // Finde die work_areas Category
        $workAreasCategory = Category::query()->where('key', 'work_areas')->first();

        if (! $workAreasCategory) {
            return;
        }

        // Prüfe, ob icon-Spalte existiert
        $hasIconColumn = Schema::hasColumn('intranet_app_mein_arbeitsschutz_work_areas', 'icon');

        // Erstelle WorkArea-Einträge und migriere Subcategories
        foreach ($gvps as $gvp) {
            // Erstelle WorkArea mit bezeichnung als name
            $workAreaData = [
                'name' => $gvp->name,
                'sort_order' => 0,
            ];

            // Füge icon nur hinzu, wenn die Spalte noch existiert
            if ($hasIconColumn) {
                $workAreaData['icon'] = 'wrench-screwdriver'; // Dummy-Wert, wird später durch Media ersetzt
            }

            $workArea = WorkArea::create($workAreaData);

            // Migriere Icon von GVP zu WorkArea (falls vorhanden)
            if ($gvp->hasGewerkeIcon()) {
                $gvpIcon = $gvp->getGewerkeIcon();
                if ($gvpIcon) {
                    // Kopiere Icon von GVP zu WorkArea
                    $workArea->addMediaFromUrl($gvpIcon->getFullUrl())
                        ->usingName('icon')
                        ->toMediaCollection('icon');
                }
            }

            // Finde bestehende Subcategories für diese GVP
            $existingSubcategories = Subcategory::query()
                ->where('source_type', Gvp::class)
                ->where('source_id', $gvp->id)
                ->get();

            // Aktualisiere source_type und source_id auf WorkArea
            foreach ($existingSubcategories as $subcategory) {
                $subcategory->update([
                    'source_type' => WorkArea::class,
                    'source_id' => $workArea->id,
                ]);
            }

            // Erstelle Subcategory für work_areas Category, falls noch nicht vorhanden
            Subcategory::firstOrCreate(
                [
                    'category_id' => $workAreasCategory->id,
                    'source_type' => WorkArea::class,
                    'source_id' => $workArea->id,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Finde alle WorkAreas
        $workAreas = WorkArea::all();

        if ($workAreas->isEmpty()) {
            return;
        }

        // Finde die work_areas Category
        $workAreasCategory = Category::query()->where('key', 'work_areas')->first();

        if (! $workAreasCategory) {
            return;
        }

        // Lösche Subcategories und WorkAreas
        foreach ($workAreas as $workArea) {
            Subcategory::query()
                ->where('source_type', WorkArea::class)
                ->where('source_id', $workArea->id)
                ->delete();

            $workArea->delete();
        }
    }
};
