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
        Schema::create('intranet_app_mein_arbeitsschutz_document_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('intranet_app_mein_arbeitsschutz_documents', indexName: 'mas_doc_assign_doc_fk')
                ->cascadeOnDelete();
            $table->foreignId('category_id')
                ->constrained('intranet_app_mein_arbeitsschutz_categories', indexName: 'mas_doc_assign_category_fk')
                ->cascadeOnDelete();
            $table->foreignId('subcategory_id')
                ->nullable()
                ->constrained('intranet_app_mein_arbeitsschutz_subcategories', indexName: 'mas_doc_assign_subcat_fk')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['document_id', 'category_id', 'subcategory_id'], 'mein_arbeitsschutz_document_assignments_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intranet_app_mein_arbeitsschutz_document_assignments');
    }
};
