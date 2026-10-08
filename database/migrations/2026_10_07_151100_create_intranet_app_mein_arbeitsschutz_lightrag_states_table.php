<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_app_mein_arbeitsschutz_lightrag_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id')->unique('iama_lightrag_document_uq');
            $table->unsignedBigInteger('media_id')->nullable();
            $table->string('lightrag_doc_id')->nullable();
            $table->string('track_id')->nullable();
            $table->string('status', 32);
            $table->text('error_message')->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();

            $table->index('status', 'iama_lightrag_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_app_mein_arbeitsschutz_lightrag_states');
    }
};
