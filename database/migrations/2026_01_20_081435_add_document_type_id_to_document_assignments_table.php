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
        // Füge die document_type_id Spalte hinzu (nur wenn sie noch nicht existiert)
        if (!Schema::hasColumn('intranet_app_mein_arbeitsschutz_document_assignments', 'document_type_id')) {
            Schema::table('intranet_app_mein_arbeitsschutz_document_assignments', function (Blueprint $table) {
                $table->unsignedBigInteger('document_type_id')->nullable()->after('subcategory_id');
            });
        }

        // Entferne Foreign Keys, die die Unique-Constraint verwenden
        Schema::table('intranet_app_mein_arbeitsschutz_document_assignments', function (Blueprint $table) {
            $table->dropForeign('mas_doc_assign_doc_fk');
            $table->dropForeign('mas_doc_assign_category_fk');
            $table->dropForeign('mas_doc_assign_subcat_fk');
        });

        // Entferne die alte Unique-Constraint
        Schema::table('intranet_app_mein_arbeitsschutz_document_assignments', function (Blueprint $table) {
            $table->dropUnique('mein_arbeitsschutz_document_assignments_unique');
        });

        // Füge Foreign Keys wieder hinzu
        Schema::table('intranet_app_mein_arbeitsschutz_document_assignments', function (Blueprint $table) {
            $table->foreign('document_id', 'mas_doc_assign_doc_fk')
                ->references('id')
                ->on('intranet_app_mein_arbeitsschutz_documents')
                ->cascadeOnDelete();
            $table->foreign('category_id', 'mas_doc_assign_category_fk')
                ->references('id')
                ->on('intranet_app_mein_arbeitsschutz_categories')
                ->cascadeOnDelete();
            $table->foreign('subcategory_id', 'mas_doc_assign_subcat_fk')
                ->references('id')
                ->on('intranet_app_mein_arbeitsschutz_subcategories')
                ->cascadeOnDelete();
        });

        // Füge den Foreign Key für document_type_id hinzu (nur wenn er noch nicht existiert)
        $constraintExists = DB::selectOne(
            "SELECT CONSTRAINT_NAME 
             FROM information_schema.KEY_COLUMN_USAGE 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = 'intranet_app_mein_arbeitsschutz_document_assignments' 
             AND CONSTRAINT_NAME = 'mas_doc_assign_doctype_fk'"
        );
        
        if (! $constraintExists) {
            Schema::table('intranet_app_mein_arbeitsschutz_document_assignments', function (Blueprint $table) {
                $table->foreign('document_type_id', 'mas_doc_assign_doctype_fk')
                    ->references('id')
                    ->on('intranet_app_mein_arbeitsschutz_document_types')
                    ->cascadeOnDelete();
            });
        }

        // Füge die neue Unique-Constraint mit allen vier Spalten hinzu
        Schema::table('intranet_app_mein_arbeitsschutz_document_assignments', function (Blueprint $table) {
            $table->unique(['document_id', 'category_id', 'subcategory_id', 'document_type_id'], 'mein_arbeitsschutz_document_assignments_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intranet_app_mein_arbeitsschutz_document_assignments', function (Blueprint $table) {
            // Entferne die neue Unique-Constraint
            $table->dropUnique('mein_arbeitsschutz_document_assignments_unique');

            // Entferne die document_type_id Spalte
            $table->dropForeign('mas_doc_assign_doctype_fk');
            $table->dropColumn('document_type_id');

            // Füge die alte Unique-Constraint wieder hinzu
            $table->unique(['document_id', 'category_id', 'subcategory_id'], 'mein_arbeitsschutz_document_assignments_unique');
        });
    }
};
