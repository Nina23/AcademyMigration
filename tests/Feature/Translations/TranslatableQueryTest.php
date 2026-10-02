<?php

namespace Tests\Feature\Translations;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use TypiCMS\Modules\Announcements\Models\Announcement;
use TypiCMS\Modules\News\Models\News;
use TypiCMS\Modules\Pages\Models\Page;

/**
 * Queries that filter/select on translated JSON values, as the application
 * relies on them: Eloquent "column->locale" paths (incl. the hyphenated
 * "sr-latn" locale), TypiCMS raw JSON_EXTRACT SQL and whereJsonContains.
 */
class TranslatableQueryTest extends TestCase
{
    public function test_published_and_slug_scopes_use_the_current_locale_including_sr_latn()
    {
        $news = $this->createNews(['status' => ['sr' => '1', 'sr-latn' => '1', 'en' => '0']]);

        foreach (['sr' => 'izlozba-studenata-cr', 'sr-latn' => 'izlozba-studenata'] as $locale => $slug) {
            App::setLocale($locale);
            $this->assertSame([$news->id], News::published()->pluck('id')->all(), "published() in {$locale}");
            $this->assertSame($news->id, News::published()->whereSlugIs($slug)->value('id'), "whereSlugIs() in {$locale}");
        }

        App::setLocale('en');
        $this->assertSame(0, News::published()->count());
        $this->assertSame(0, News::whereSlugIs('izlozba-studenata')->count(), 'slug of another locale must not match');
    }

    /**
     * SlugObserver keeps slugs unique per locale (JSON "slug->{locale}"
     * queries, incl. sr-latn) by appending -1, -2, ...
     *
     * Note (current behaviour, not tested): its "generate slug from title" branch
     * throws "Undefined index" when slugs are empty, because Spatie drops empty
     * translations. The admin never reaches it: validation requires a slug
     * whenever a title is given.
     */
    public function test_slugs_are_kept_unique_per_locale()
    {
        $first = $this->createNews();
        $duplicate = $this->createNews();
        $third = $this->createNews();

        $this->assertSame(
            ['sr' => 'izlozba-studenata-cr-1', 'sr-latn' => 'izlozba-studenata-1', 'en' => 'student-exhibition-1'],
            $duplicate->getTranslations('slug')
        );
        $this->assertSame('izlozba-studenata-2', $third->getTranslation('slug', 'sr-latn'));
        $this->assertSame('izlozba-studenata', $first->fresh()->getTranslation('slug', 'sr-latn'));
    }

    public function test_page_uri_lookup_is_per_locale()
    {
        foreach (['sr' => 'novosti', 'sr-latn' => 'novosti', 'en' => 'news'] as $locale => $uri) {
            App::setLocale($locale);
            $this->assertSame(2, Page::published()->whereUriIs($uri)->value('id'), "whereUriIs() in {$locale}");
        }

        App::setLocale('en');
        $this->assertNull(Page::published()->whereUriIs('raspored')->value('id'), 'page unpublished in en');
    }

    /**
     * Base::scopeSelectFields() builds raw SQL with an UNQUOTED JSON path
     * ('$.sr-latn'). MariaDB 10.11 accepts it; MySQL 8 rejects it
     * (ERROR 3143). Used by every admin API list.
     */
    public function test_select_fields_returns_translated_columns_for_every_locale()
    {
        $news = $this->createNews(['status' => ['sr' => '1', 'sr-latn' => '0', 'en' => '1']]);

        $expected = [
            'sr' => ['Изложба студената', 1],
            'sr-latn' => ['Izložba studenata', 0],
            'en' => ['Student exhibition', 1],
        ];

        foreach ($expected as $locale => [$title, $status]) {
            request()->merge(['locale' => $locale]);
            $row = News::selectFields('id,title,status')->where('id', $news->id)->first();

            $this->assertSame($title, $row->title_translated, "title_translated in {$locale}");
            $this->assertSame($status, (int) $row->status_translated, "status_translated in {$locale}");
        }
    }

    public function test_raw_json_filters_match_case_insensitively_per_locale()
    {
        $news = $this->createNews();

        // Same expression as TypiCMS FilterOr (admin list search) and the public search.
        $sql = 'JSON_UNQUOTE(JSON_EXTRACT(`title`, \'$.%s\')) LIKE ? COLLATE utf8mb4_general_ci';

        $this->assertSame($news->id, News::whereRaw(sprintf($sql, 'sr-latn'), ['%IZLOŽBA%'])->value('id'));
        $this->assertSame($news->id, News::whereRaw(sprintf($sql, 'sr'), ['%изложба%'])->value('id'));
        $this->assertNull(News::whereRaw(sprintf($sql, 'en'), ['%izložba%'])->value('id'));
    }

    public function test_announcement_program_filter_uses_json_contains()
    {
        $base = [
            'status' => ['sr' => '1', 'sr-latn' => '1', 'en' => '1'],
            'highlight' => ['sr' => '0', 'sr-latn' => '0', 'en' => '0'],
            'summary' => ['sr' => '', 'sr-latn' => '', 'en' => ''],
            'body' => ['sr' => '', 'sr-latn' => '', 'en' => ''],
            'announcement_category_id' => 1,
            'date' => '2026-01-01 00:00:00',
            'expiry' => '2027-01-01 00:00:00',
        ];
        // "program" is stored like production: a JSON array of strings.
        $fine = Announcement::create($base + ['title' => ['en' => 'Fine arts'], 'slug' => ['en' => 'fine'], 'program' => '["0"]']);
        $mixed = Announcement::create($base + ['title' => ['en' => 'All'], 'slug' => ['en' => 'all'], 'program' => '["0","1","2"]']);
        $music = Announcement::create($base + ['title' => ['en' => 'Music'], 'slug' => ['en' => 'music'], 'program' => '["1"]']);

        // The public controller passes the request value (a string); "3" means all programs.
        $this->assertEqualsCanonicalizing([$fine->id, $mixed->id], Announcement::filterByProgram('0')->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$mixed->id, $music->id], Announcement::filterByProgram('1')->pluck('id')->all());
        $this->assertSame(3, Announcement::filterByProgram('3')->count());

        $this->assertSame(__('Likovni program').', '.__('Muzicki program').', '.__('Dramski program'), $mixed->fresh()->formatted_program);
        $this->assertSame(1, DB::table('announcements')->where('id', $mixed->id)->whereRaw("JSON_CONTAINS(program, '\"2\"')")->count());
    }
}
