<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Representative public pages: DB-driven routing (pages + module pages),
 * locale prefixes (sr has none; sr-latn and en do), controllers and Blade views.
 */
class PublicRoutesTest extends TestCase
{
    public function test_home_page_renders_in_each_locale()
    {
        $this->get('/')->assertOk()->assertSee('<html lang="sr">', false);
        $this->get('/sr-latn')->assertOk()->assertSee('<html lang="sr-latn">', false);
        $this->get('/en')->assertOk()->assertSee('<html lang="en">', false);
    }

    public function test_unknown_uris_return_404()
    {
        $this->get('/ova-stranica-ne-postoji')->assertNotFound();
        $this->get('/sr-latn/ova-stranica-ne-postoji')->assertNotFound();
        $this->get('/en/this-page-does-not-exist')->assertNotFound();
    }

    public function test_news_index_lists_published_news()
    {
        $this->createNews();

        $this->get('/novosti')->assertOk()->assertSee('Изложба студената');
        $this->get('/sr-latn/novosti')->assertOk()->assertSee('Izložba studenata');
        $this->get('/en/news')->assertOk()->assertSee('Student exhibition');
    }

    public function test_news_detail_resolves_by_translated_slug_in_each_locale()
    {
        $this->createNews();

        $this->get('/novosti/izlozba-studenata-cr')->assertOk()->assertSee('<title>Изложба студената', false);
        $this->get('/sr-latn/novosti/izlozba-studenata')->assertOk()->assertSee('<title>Izložba studenata', false);
        $this->get('/en/news/student-exhibition')->assertOk()->assertSee('<title>Student exhibition', false);

        // A slug from another locale does not resolve.
        $this->get('/en/news/izlozba-studenata')->assertNotFound();
        $this->get('/novosti/does-not-exist')->assertNotFound();
    }

    public function test_news_unpublished_in_one_locale_is_hidden_only_in_that_locale()
    {
        $this->createNews(['status' => ['sr' => '1', 'sr-latn' => '1', 'en' => '0']]);

        $this->get('/novosti/izlozba-studenata-cr')->assertOk();
        $this->get('/en/news/student-exhibition')->assertNotFound();
        $this->get('/en/news')->assertOk()->assertDontSee('Student exhibition');
    }

    public function test_news_atom_feed_renders_when_there_are_no_news()
    {
        $response = $this->get('/en/news/feed.xml')->assertOk();

        $this->assertStringContainsString('atom', $response->headers->get('Content-Type'));
    }

    /**
     * KNOWN EXISTING BUG (baseline, deliberately not fixed):
     * News PublicController::feed() calls $feed->add(), which does not exist in
     * laravelium/feed v8.0.1 (the method is addItem()). As soon as one news
     * item exists the feed throws and returns HTTP 500 — i.e. in production.
     * This test pins the current behaviour; if it starts failing, the feed
     * behaviour changed (fixed, or the package changed) and must be reviewed.
     */
    public function test_known_bug_news_feed_with_items_returns_500()
    {
        $this->createNews();

        $response = $this->get('/en/news/feed.xml');

        $response->assertStatus(500);
        $this->assertStringContainsString('Call to undefined method Laravelium\Feed\Feed::add()', $response->exception->getMessage());
    }

    public function test_class_schedule_index_renders_where_published()
    {
        $this->get('/raspored')->assertOk();
        $this->get('/sr-latn/raspored')->assertOk();

        // The page is unpublished in English (as in production).
        $this->get('/en/classschedule')->assertNotFound();
    }
}
