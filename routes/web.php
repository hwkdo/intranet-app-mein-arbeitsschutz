<?php

use Hwkdo\IntranetAppMeinArbeitsschutz\Models\Document;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['web','auth','can:see-app-mein-arbeitsschutz'])->group(function () {        
    Volt::route('apps/mein-arbeitsschutz', 'apps.mein-arbeitsschutz.index')->name('apps.mein-arbeitsschutz.index');
    Volt::route('apps/mein-arbeitsschutz/dokumente', 'apps.mein-arbeitsschutz.documents')->name('apps.mein-arbeitsschutz.documents');
    Volt::route('apps/mein-arbeitsschutz/dokumente/{categoryKey}', 'apps.mein-arbeitsschutz.documents.show')->name('apps.mein-arbeitsschutz.documents.show');
    Route::get('apps/mein-arbeitsschutz/dokumente/{document}/download', function (Document $document) {
        $media = $document->getFirstMedia('documents');

        if (! $media) {
            abort(404);
        }

        return response()->download($media->getPath(), $media->file_name);
    })->name('apps.mein-arbeitsschutz.documents.download');
    Route::get('apps/mein-arbeitsschutz/dokumente/{document}/thumb', function (Document $document) {
        $media = $document->getFirstMedia('documents');

        if (! $media || ! $media->hasGeneratedConversion('thumb')) {
            abort(404);
        }

        return response()->file($media->getPath('thumb'));
    })->name('apps.mein-arbeitsschutz.documents.thumb');
    Volt::route('apps/mein-arbeitsschutz/example', 'apps.mein-arbeitsschutz.example')->name('apps.mein-arbeitsschutz.example');
    Volt::route('apps/mein-arbeitsschutz/frage', 'apps.mein-arbeitsschutz.question')->name('apps.mein-arbeitsschutz.question');
    Volt::route('apps/mein-arbeitsschutz/settings/user', 'apps.mein-arbeitsschutz.settings.user')->name('apps.mein-arbeitsschutz.settings.user');
});

Route::middleware(['web','auth','can:manage-app-mein-arbeitsschutz'])->group(function () {
    Volt::route('apps/mein-arbeitsschutz/admin', 'apps.mein-arbeitsschutz.admin.index')->name('apps.mein-arbeitsschutz.admin.index');
});
