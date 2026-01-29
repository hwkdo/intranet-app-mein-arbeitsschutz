<?php

use Hwkdo\IntranetAppMeinArbeitsschutz\Models\DocumentAssignment;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Subcategory;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\WorkArea;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Finde alle Subcategories mit WorkArea als source_type
        $subcategories = Subcategory::query()
            ->where('source_type', WorkArea::class)
            ->get();

        $orphanedSubcategoryIds = [];

        foreach ($subcategories as $subcategory) {
            // Prüfe, ob die referenzierte WorkArea noch existiert
            $workAreaExists = WorkArea::query()
                ->where('id', $subcategory->source_id)
                ->exists();

            if (! $workAreaExists) {
                $orphanedSubcategoryIds[] = $subcategory->id;
            }
        }

        if (empty($orphanedSubcategoryIds)) {
            return;
        }

        // Lösche zuerst alle zugehörigen DocumentAssignments
        DocumentAssignment::query()
            ->whereIn('subcategory_id', $orphanedSubcategoryIds)
            ->delete();

        // Lösche dann die verwaisten Subcategories
        Subcategory::query()
            ->whereIn('id', $orphanedSubcategoryIds)
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Diese Migration kann nicht rückgängig gemacht werden,
        // da die gelöschten Daten nicht wiederhergestellt werden können
    }
};
