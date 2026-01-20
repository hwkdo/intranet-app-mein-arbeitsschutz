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
        Schema::create('intranet_app_mein_arbeitsschutz_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('icon');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('intranet_app_mein_arbeitsschutz_document_types')->insert([
            [
                'key' => 'risk_assessment',
                'label' => 'Gefährdungsbeurteilungen',
                'icon' => 'exclamation-triangle',
                'sort_order' => 1,
            ],
            [
                'key' => 'operating_instructions',
                'label' => 'Betriebsanweisungen',
                'icon' => 'document-text',
                'sort_order' => 2,
            ],
            [
                'key' => 'hazardous_substances',
                'label' => 'Gefahrstoffregister',
                'icon' => 'beaker',
                'sort_order' => 3,
            ],
            [
                'key' => 'safety_data_sheets',
                'label' => 'Sicherheitsdatenblätter',
                'icon' => 'clipboard-document-list',
                'sort_order' => 4,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intranet_app_mein_arbeitsschutz_document_types');
    }
};
