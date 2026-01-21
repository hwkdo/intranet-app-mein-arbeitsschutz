<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz;

use Hwkdo\IntranetAppMeinArbeitsschutz\Events\DocumentDeleted;
use Hwkdo\IntranetAppMeinArbeitsschutz\Events\DocumentUploaded;
use Hwkdo\IntranetAppMeinArbeitsschutz\Listeners\DeleteDocumentFromOpenWebUi;
use Hwkdo\IntranetAppMeinArbeitsschutz\Listeners\UploadDocumentToOpenWebUi;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Hwkdo\IntranetAppMeinArbeitsschutz\Commands\IntranetAppMeinArbeitsschutzCommand;
use Livewire\Volt\Volt;

class IntranetAppMeinArbeitsschutzServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('intranet-app-mein-arbeitsschutz')
            ->hasConfigFile()
            ->hasViews()
            ->hasAssets()
            ->discoversMigrations();
    }

    public function boot(): void
    {
        parent::boot();
        // Gate::policy(Raum::class, RaumPolicy::class);
        $this->app->booted( function() {
            Volt::mount(__DIR__.'/../resources/views/livewire');                        
        });
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Event::listen(
            DocumentUploaded::class,
            UploadDocumentToOpenWebUi::class
        );

        Event::listen(
            DocumentDeleted::class,
            DeleteDocumentFromOpenWebUi::class
        );
    }

    public function register(): void
    {
        parent::register();
        $this->mergeConfigFrom(__DIR__.'/../config/intranet-app-mein-arbeitsschutz-disk.php', 'filesystems.disks.intranet-app-mein-arbeitsschutz');
    }
}
