<?php

namespace Tests\Feature\Translations;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Plural forms of @choice / trans_choice() as the public search page renders
 * them: "{{ $count }} @choice(__($module), $count)" (search/public/index).
 *
 * The view passes the already translated string ("страница|странице"), not a
 * key. Laravel 8–10 select the form with the current locale's plural rule;
 * Laravel 11 would use the fallback locale (en) because the string is not a
 * key (Translator::localeForChoice). Pinned as it is today, including the
 * two-form strings and the sr-latn locale that has no Laravel plural rule.
 */
class ChoiceTest extends TestCase
{
    private const EXPECTED = [
        'sr' => [
            1 => 'страница', 2 => 'странице', 4 => 'странице', 5 => 'страница', 11 => 'страница',
            21 => 'страница', 22 => 'странице', 66 => 'страница', 84 => 'странице', 101 => 'страница',
        ],
        'sr-latn' => [
            1 => 'stranica', 2 => 'stranica', 4 => 'stranica', 5 => 'stranica', 11 => 'stranica',
            21 => 'stranica', 22 => 'stranica', 66 => 'stranica', 84 => 'stranica', 101 => 'stranica',
        ],
    ];

    public function test_trans_choice_of_a_translated_string_uses_the_current_locale()
    {
        foreach (self::EXPECTED as $locale => $forms) {
            App::setLocale($locale);
            foreach ($forms as $count => $form) {
                $this->assertSame($form, trans_choice(__('pages'), $count), "{$locale}, {$count}");
            }
        }
    }

    public function test_search_heading_blade_choice_renders_the_same_forms()
    {
        foreach (self::EXPECTED as $locale => $forms) {
            App::setLocale($locale);
            foreach ($forms as $count => $form) {
                $this->assertSame(
                    "{$count} {$form}",
                    Blade::render("{{ \$count }} @choice(__('pages'), \$count)", ['count' => $count]),
                    "{$locale}, {$count}"
                );
            }
        }
    }
}
