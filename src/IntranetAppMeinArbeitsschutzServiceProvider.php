<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz;

use Livewire\Volt\Volt;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
            ->discoversMigrations();
    }

    public function boot(): void
    {
        parent::boot();
        // Gate::policy(Raum::class, RaumPolicy::class);
        $this->app->booted(function () {
            Volt::mount(__DIR__.'/../resources/views/livewire');
        });
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

    }

    public function register(): void
    {
        parent::register();
        $this->mergeConfigFrom(__DIR__.'/../config/intranet-app-mein-arbeitsschutz-disk.php', 'filesystems.disks.intranet-app-mein-arbeitsschutz');
    }
}
