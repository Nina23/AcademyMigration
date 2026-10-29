<?php

namespace TypiCMS\Modules\Translations\Providers;

use Illuminate\Foundation\Application;
use Illuminate\Translation\TranslationServiceProvider as LaravelTranslationServiceProvider;
use TypiCMS\Modules\Translations\Loaders\MixedLoader;
use TypiCMS\Modules\Translations\Models\Translation;
use TypiCMS\Modules\Translations\Translator;

class TranslationServiceProvider extends LaravelTranslationServiceProvider
{
    /**
     * Same as Laravel's register(), with the TypiCMS Translator (choice locale as in Laravel 10).
     */
    public function register()
    {
        $this->registerLoader();

        $this->app->singleton('translator', function ($app) {
            $trans = new Translator($app['translation.loader'], $app->getLocale());

            $trans->setFallback($app->getFallbackLocale());

            return $trans;
        });
    }

    /**
     * Register the translation line loader.
     */
    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function (Application $app) {
            $repository = new Translation();

            return new MixedLoader($app['files'], $app['path.lang'], $repository);
        });
    }
}
