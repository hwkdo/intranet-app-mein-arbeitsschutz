<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('intranet_app_mein_arbeitsschutz_categories', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('icon');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('intranet_app_mein_arbeitsschutz_categories')->insert([
            [
                'key' => 'first_aid',
                'label' => 'Notsituation/Erste Hilfe',
                'icon' => 'heart',
                'sort_order' => 1,
            ],
            [
                'key' => 'work_areas',
                'label' => 'Arbeitsbereiche',
                'icon' => 'wrench-screwdriver',
                'sort_order' => 2,
            ],
            [
                'key' => 'general',
                'label' => 'Allgemeine Dokumente',
                'icon' => 'document-text',
                'sort_order' => 3,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intranet_app_mein_arbeitsschutz_categories');
    }
};
