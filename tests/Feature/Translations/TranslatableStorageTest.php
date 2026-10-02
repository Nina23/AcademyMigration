<?php

namespace Tests\Feature\Translations;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use TypiCMS\Modules\News\Models\News;

/**
 * How translations are stored and read today (spatie/laravel-translatable 4.6
 * on MariaDB LONGTEXT + CHECK(json_valid) columns). These are the behaviours
 * most likely to change with Spatie/Laravel upgrades.
 *
 * Locales: sr (main, Cyrillic), sr-latn, en (app.fallback_locale).
 */
class TranslatableStorageTest extends TestCase
{
    private function rawColumn(News $news, string $column): string
    {
        return DB::table('news')->where('id', $news->id)->value($column);
    }

    /**
     * Stored bytes are pure ASCII: every non-ASCII character is a JSON escape.
     */
    private function assertStoredAsEscapedAscii(string $raw): void
    {
        $this->assertDoesNotMatchRegularExpression('/[^[:ascii:]]/', $raw, 'Non-ASCII bytes stored unescaped: '.$raw);
    }

    public function test_all_three_locales_are_stored_as_one_json_object_in_the_column()
    {
        $news = $this->createNews();

        // Exact bytes in the database: json_encode() without flags, so non-ASCII
        // characters are \u-escaped (same format as the production data),
        // and keys keep insertion order.
        $raw = $this->rawColumn($news, 'title');
        $this->assertSame(
            json_encode(['sr' => 'Изложба студената', 'sr-latn' => 'Izložba studenata', 'en' => 'Student exhibition']),
            $raw
        );
        $this->assertStoredAsEscapedAscii($raw);
        $this->assertStringContainsString('"sr-latn":"Izlo'.'\\'.'u017eba studenata"', $raw);
        $this->assertSame(
            ['sr' => 'Изложба студената', 'sr-latn' => 'Izložba studenata', 'en' => 'Student exhibition'],
            json_decode($this->rawColumn($news, 'title'), true)
        );

        // The database itself sees valid JSON with the right value per locale.
        $row = DB::selectOne(
            'SELECT JSON_VALID(title) AS valid, JSON_VALUE(title, \'$."sr"\') AS sr, JSON_VALUE(title, \'$."sr-latn"\') AS sr_latn, JSON_VALUE(title, \'$."en"\') AS en FROM typicms_news WHERE id = ?',
            [$news->id]
        );
        $this->assertSame(1, (int) $row->valid);
        $this->assertSame('Изложба студената', $row->sr);
        $this->assertSame('Izložba studenata', $row->sr_latn);
        $this->assertSame('Student exhibition', $row->en);
    }

    public function test_each_locale_reads_its_own_value_and_switching_app_locale_switches_attributes()
    {
        $news = News::find($this->createNews()->id);

        $this->assertSame('Изложба студената', $news->getTranslation('title', 'sr'));
        $this->assertSame('Izložba studenata', $news->getTranslation('title', 'sr-latn'));
        $this->assertSame('Student exhibition', $news->getTranslation('title', 'en'));

        $expected = ['sr' => 'Изложба студената', 'sr-latn' => 'Izložba studenata', 'en' => 'Student exhibition'];
        foreach ($expected as $locale => $title) {
            App::setLocale($locale);
            $this->assertSame($title, $news->title, "Wrong title for locale {$locale}");
            $this->assertSame($title, $news->present()->title);
        }
    }

    public function test_updating_one_locale_keeps_the_other_locales()
    {
        $news = $this->createNews();

        // Explicit locale.
        $news->setTranslation('title', 'sr-latn', 'Nova izložba')->save();
        // Implicit: plain assignment writes the current app locale.
        App::setLocale('en');
        $news = News::find($news->id);
        $news->title = 'New exhibition';
        $news->save();

        $this->assertSame(
            ['sr' => 'Изложба студената', 'sr-latn' => 'Nova izložba', 'en' => 'New exhibition'],
            json_decode($this->rawColumn($news, 'title'), true)
        );
        // Other translatable columns untouched.
        $this->assertSame('Kratak opis', News::find($news->id)->getTranslation('summary', 'sr-latn'));
    }

    public function test_status_values_are_stored_per_locale_as_strings()
    {
        $news = $this->createNews(['status' => ['sr' => '1', 'sr-latn' => '0', 'en' => 1]]);

        // Base::setStatusAttribute runs once per locale via Spatie; values are
        // kept as given (production data also stores "1"/"0" strings).
        $this->assertSame('{"sr":"1","sr-latn":"0","en":1}', $this->rawColumn($news, 'status'));
        $this->assertTrue($news->isPublished('sr'));
        $this->assertFalse($news->isPublished('sr-latn'));
        $this->assertTrue($news->isPublished('en'));
    }

    /**
     * Current Spatie 4.6 behaviour: getTranslations() filters out '' and null,
     * and every setTranslation() re-writes the column from that filtered list.
     * So empty translations are dropped whenever the attribute is written —
     * except the last locale written in that call.
     */
    public function test_empty_translations_are_dropped_when_the_attribute_is_written()
    {
        $news = $this->createNews([
            'summary' => ['sr' => 'Опис', 'sr-latn' => '', 'en' => null],
            'body' => ['sr' => '', 'sr-latn' => '', 'en' => ''],
        ]);

        $this->assertSame(json_encode(['sr' => 'Опис', 'en' => null]), $this->rawColumn($news, 'summary'));
        $this->assertSame('{"en":""}', $this->rawColumn($news, 'body'));

        $news->setTranslation('summary', 'sr', 'Нови опис')->save();
        $this->assertSame(json_encode(['sr' => 'Нови опис']), $this->rawColumn($news, 'summary'));
    }

    public function test_missing_locale_falls_back_to_english_on_the_website_but_not_in_the_admin()
    {
        $news = $this->createNews(['title' => ['sr' => 'Само српски', 'en' => 'English only']]);
        $news = News::find($news->id);

        // Website: fallback to app.fallback_locale (en).
        $this->assertSame('English only', $news->getTranslation('title', 'sr-latn'));
        $this->assertSame('', $news->getTranslation('title', 'sr-latn', false));

        // Admin requests: SetTranslatableFallbackLocaleToNull disables fallback.
        $this->actingAs($this->createSuperUser())->get('/admin/news')->assertOk();
        $this->assertNull(config('app.fallback_locale'));
        $this->assertSame('', $news->getTranslation('title', 'sr-latn'));
    }

    public function test_production_shaped_rows_are_read_correctly()
    {
        // Row written exactly like existing production data (inserted raw, not via the model).
        $id = DB::table('news')->insertGetId([
            'date' => '2025-12-01',
            'status' => '{"sr":"1","sr-latn":"1","en":"0"}',
            'highlight' => '{"sr":"0","sr-latn":"0","en":"0"}',
            // json_encode() default flags = production format (\u-escaped, "<\/p>").
            'title' => json_encode(['sr' => 'Концерт', 'sr-latn' => 'Koncert', 'en' => 'Concert']),
            'slug' => '{"sr":"koncert-cr","sr-latn":"koncert","en":"concert"}',
            'summary' => '{"sr":"","sr-latn":"","en":""}',
            'body' => json_encode(['sr' => '<p>Текст</p>', 'sr-latn' => '<p>Tekst</p>', 'en' => '<p>Text</p>']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertStoredAsEscapedAscii(DB::table('news')->where('id', $id)->value('body'));
        $this->assertStringContainsString('<'.'\\'.'/p>', DB::table('news')->where('id', $id)->value('body'));

        $news = News::find($id);
        $this->assertSame('Концерт', $news->title);
        $this->assertSame('<p>Текст</p>', $news->body);
        $this->assertSame('Koncert', $news->getTranslation('title', 'sr-latn'));
        $this->assertFalse($news->isPublished('en'));
    }
}
