<?php

namespace Tests\Feature\Models;

use Tests\TestCase;

/**
 * Base::uri($locale) for a locale the item has no slug in: the language switcher
 * calls it for every enabled locale. Laravel 8–11 built the route URL with the
 * empty {slug} left out (= the module page of that locale); Laravel 12's route()
 * rejects an empty parameter.
 */
class UriEmptySlugTest extends TestCase
{
    private function newsWithoutEnglish()
    {
        // Not translated into English: no English title or slug (SlugObserver
        // needs a slug for every title, known issue #4).
        return $this->createNews([
            'title' => ['sr' => 'Изложба студената', 'sr-latn' => 'Izložba studenata'],
            'slug' => ['sr' => 'izlozba-studenata-cr', 'sr-latn' => 'izlozba-studenata'],
            'status' => ['sr' => '1', 'sr-latn' => '1', 'en' => '0'],
        ]);
    }

    public function test_uri_for_a_locale_without_slug_is_the_module_page()
    {
        $news = $this->newsWithoutEnglish();

        $this->assertSame('', $news->translate('slug', 'en'));
        $this->assertSame(url('/en/news'), $news->uri('en'));
        $this->assertSame(url('/sr-latn/novosti/izlozba-studenata'), $news->uri('sr-latn'));
    }

    public function test_news_page_links_the_untranslated_locale_to_its_module_page()
    {
        $this->newsWithoutEnglish();

        $this->get('/novosti/izlozba-studenata-cr')
            ->assertOk()
            ->assertSee('href="'.url('/en/news').'"', false)
            ->assertSee('href="'.url('/sr-latn/novosti/izlozba-studenata').'"', false);
    }
}
