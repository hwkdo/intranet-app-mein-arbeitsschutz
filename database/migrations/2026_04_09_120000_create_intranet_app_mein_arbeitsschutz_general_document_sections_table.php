<?php

use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Category;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\GeneralDocumentSection;
use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Subcategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_app_mein_arbeitsschutz_general_document_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique('mas_gen_doc_sec_key_uq');
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $sectionId = GeneralDocumentSection::query()->insertGetId([
            'key' => 'betriebsarzt_informiert',
            'name' => 'Der Betriebsarzt informiert',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $generalCategory = Category::query()->where('key', 'general')->first();

        if ($generalCategory) {
            Subcategory::query()->firstOrCreate(
                [
                    'category_id' => $generalCategory->id,
                    'source_type' => GeneralDocumentSection::class,
                    'source_id' => $sectionId,
                ],
            );
        }
    }

    public function down(): void
    {
        Subcategory::query()
            ->where('source_type', GeneralDocumentSection::class)
            ->delete();

        Schema::dropIfExists('intranet_app_mein_arbeitsschutz_general_document_sections');
    }
};
