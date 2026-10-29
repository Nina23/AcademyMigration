<?php

namespace TypiCMS\Modules\Translations;

use Illuminate\Translation\Translator as BaseTranslator;

/**
 * Laravel's translator with the Laravel 8–10 locale for choice() / @choice.
 *
 * Laravel 11 uses the fallback locale's plural rule when the key passed to
 * choice() is not a translation key of the current locale. The search page
 * passes an already translated string (@choice(__($module), $count)), so every
 * Serbian count would get the English rule. Keep the current locale, as before.
 */
class Translator extends BaseTranslator
{
    protected function localeForChoice($key, $locale)
    {
        return $locale ?: $this->locale ?: $this->fallback;
    }
}
