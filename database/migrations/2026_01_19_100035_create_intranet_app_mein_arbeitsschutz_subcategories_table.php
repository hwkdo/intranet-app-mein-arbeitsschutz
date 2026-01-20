<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('intranet_app_mein_arbeitsschutz_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('intranet_app_mein_arbeitsschutz_categories', indexName: 'mas_subcategories_category_fk')
                ->cascadeOnDelete();
            $table->morphs('source', 'mas_subcategories_source_idx');
            $table->timestamps();

            $table->unique(['category_id', 'source_type', 'source_id'], 'mein_arbeitsschutz_subcategories_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intranet_app_mein_arbeitsschutz_subcategories');
    }
};
