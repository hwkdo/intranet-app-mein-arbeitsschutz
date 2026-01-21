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
        if (Schema::hasColumn('intranet_app_mein_arbeitsschutz_work_areas', 'icon')) {
            Schema::table('intranet_app_mein_arbeitsschutz_work_areas', function (Blueprint $table) {
                $table->dropColumn('icon');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intranet_app_mein_arbeitsschutz_work_areas', function (Blueprint $table) {
            $table->string('icon')->after('name');
        });
    }
};
