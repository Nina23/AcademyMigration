<?php

namespace App\Providers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        /*
         * Local disks cannot create temporary URLs (S3 can). Views call
         * Storage::temporaryUrl() for documents, so on a local disk fall back
         * to the disk's regular url(). Only disks with the "local" driver are
         * affected; the s3 driver is created by Laravel as before.
         */
        Storage::extend('local', function ($app, array $config) {
            $disk = $app['filesystem']->createLocalDriver($config);
            $disk->buildTemporaryUrlsUsing(function ($path) use ($disk) {
                return $disk->url($path);
            });

            return $disk;
        });
    }
}
