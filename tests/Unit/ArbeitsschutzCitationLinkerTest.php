<?php

declare(strict_types=1);

use Hwkdo\IntranetAppMeinArbeitsschutz\Services\ArbeitsschutzCitationLinker;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::dropIfExists('intranet_app_mein_arbeitsschutz_documents');
    Schema::create('intranet_app_mein_arbeitsschutz_documents', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->timestamps();
    });
});

it('links arbeitsschutz filenames to the open route', function () {
    DB::table('intranet_app_mein_arbeitsschutz_documents')->insert([
        'id' => 41,
        'title' => 'Brandschutzordnung',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $resolved = app(ArbeitsschutzCitationLinker::class)->resolveMany([
        'arbeitsschutz-41.md',
        'Arbeitsschutz-41.PDF',
        'arbeitsschutz-404.md',
    ]);

    expect($resolved)->toHaveKey('arbeitsschutz-41.md')
        ->and($resolved['arbeitsschutz-41.pdf']['title'])->toBe('Brandschutzordnung')
        ->and($resolved['arbeitsschutz-41.pdf']['url'])->toContain('/apps/mein-arbeitsschutz/dokumente/41/open')
        ->and($resolved)->not->toHaveKey('arbeitsschutz-404.md');
});
