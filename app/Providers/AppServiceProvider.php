<?php

namespace App\Providers;

use App\Filesystem\AwsS3V3Adapter;
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
         * affected; s3 disks get no callback (they sign real temporary URLs).
         */
        Storage::extend('local', function ($app, array $config) {
            $disk = $app['filesystem']->createLocalDriver($config);
            $disk->buildTemporaryUrlsUsing(function ($path) use ($disk) {
                return $disk->url($path);
            });

            return $disk;
        });

        /*
         * The s3 driver as Laravel creates it, wrapped in App\Filesystem\AwsS3V3Adapter
         * so that url(null) (folders) returns the disk's base URL as on Laravel 8
         * instead of throwing a TypeError.
         */
        Storage::extend('s3', function ($app, array $config) {
            $disk = $app['filesystem']->createS3Driver($config);

            return new AwsS3V3Adapter($disk->getDriver(), $disk->getAdapter(), $disk->getConfig(), $disk->getClient());
        });
    }
}
