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
        Schema::table('intranet_app_mein_arbeitsschutz_documents', function (Blueprint $table) {
            $table->string('openwebui_file_id')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intranet_app_mein_arbeitsschutz_documents', function (Blueprint $table) {
            $table->dropColumn('openwebui_file_id');
        });
    }
};
