<?php

namespace Tests\Fixtures;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Minimal fixture data for the test environment ONLY (akademija_testing).
 *
 * Mirrors the shape of the real data rather than the stock TypiCMS seeders
 * in database/seeders (which use en/fr/nl):
 *  - locales sr (main, no URL prefix), sr-latn, en
 *  - settings are read into config('typicms.*') while the app boots
 *  - translatable columns are JSON; status stored as strings ("1") like prod
 *
 * Rows are inserted with DB::table() so no model events/observers run and the
 * stored JSON is exactly what we write here.
 */
class BaselineSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        $settings = [
            ['config', 'admin_locale', 'en'],
            ['config', 'auth_public', '0'],
            ['config', 'lang_chooser', '0'],
            ['config', 'register', '0'],
            ['config', 'webmaster_email', 'webmaster@example.test'],
            ['config', 'welcome_message', 'Test admin panel'],
            ['sr', 'status', '1'],
            ['sr', 'website_title', 'Академија уметности (тест)'],
            ['sr', 'website_baseline', 'Тест'],
            ['sr-latn', 'status', '1'],
            ['sr-latn', 'website_title', 'Akademija umjetnosti (test)'],
            ['sr-latn', 'website_baseline', 'Test'],
            ['en', 'status', '1'],
            ['en', 'website_title', 'Academy of Arts (test)'],
            ['en', 'website_baseline', 'Test'],
        ];

        DB::table('settings')->insert(array_map(function (array $row) use ($now) {
            return [
                'group_name' => $row[0],
                'key_name' => $row[1],
                'value' => $row[2],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $settings));

        // Pages. Module pages must exist before the app boots: their module's
        // front-office routes are only registered when such a page exists.
        $this->page(1, $now, ['is_home' => 1], ['sr' => 'Почетна', 'sr-latn' => 'Početna', 'en' => 'Home'],
            ['sr' => 'pocetna-cr', 'sr-latn' => 'pocetna', 'en' => 'home']);
        $this->page(2, $now, ['module' => 'news'], ['sr' => 'Новости', 'sr-latn' => 'Novosti', 'en' => 'News'],
            ['sr' => 'novosti', 'sr-latn' => 'novosti', 'en' => 'news']);
        // Like production: the class schedule page is not published in English.
        $this->page(3, $now, ['module' => 'classschedules'], ['sr' => 'Распоред', 'sr-latn' => 'Raspored', 'en' => 'Classschedule'],
            ['sr' => 'raspored', 'sr-latn' => 'raspored', 'en' => 'classschedule'], ['sr' => '1', 'sr-latn' => '1', 'en' => '0']);

        DB::table('categories')->insert([
            'id' => 1,
            'status' => json_encode(['sr' => '1', 'sr-latn' => '1', 'en' => '1']),
            'title' => json_encode(['sr' => 'Вијести', 'sr-latn' => 'Vijesti', 'en' => 'General']),
            'slug' => json_encode(['sr' => 'vijesti', 'sr-latn' => 'vijesti', 'en' => 'general']),
            'summary' => json_encode(['sr' => '', 'sr-latn' => '', 'en' => '']),
            'body' => json_encode(['sr' => '', 'sr-latn' => '', 'en' => '']),
            'connection' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('announcement_categories')->insert([
            'id' => 1,
            'title' => json_encode(['sr' => 'Распоред', 'sr-latn' => 'Raspored', 'en' => 'Schedule']),
            'slug' => json_encode(['sr' => 'raspored', 'sr-latn' => 'raspored', 'en' => 'schedule']),
            'status' => json_encode(['sr' => '1', 'sr-latn' => '1', 'en' => '1']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('announcement_departments')->insert([
            'id' => 1,
            'title' => json_encode(['sr' => 'Графички дизајн', 'sr-latn' => 'Grafički dizajn', 'en' => 'Graphic design']),
            'slug' => json_encode(['sr' => 'graficki-dizajn', 'sr-latn' => 'graficki-dizajn', 'en' => 'graphic-design']),
            'status' => json_encode(['sr' => '1', 'sr-latn' => '1', 'en' => '1']),
            'program_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function page(int $id, Carbon $now, array $attributes, array $title, array $slug, array $status = null): void
    {
        $empty = ['sr' => '', 'sr-latn' => '', 'en' => ''];
        $null = ['sr' => null, 'sr-latn' => null, 'en' => null];

        DB::table('pages')->insert(array_merge([
            'id' => $id,
            'position' => $id,
            'parent_id' => null,
            'private' => 0,
            'is_home' => 0,
            'redirect' => 0,
            'title' => json_encode($title),
            'slug' => json_encode($slug),
            'uri' => json_encode($slug),
            'body' => json_encode($empty),
            'status' => json_encode($status ?? ['sr' => '1', 'sr-latn' => '1', 'en' => '1']),
            'meta_keywords' => json_encode($null),
            'meta_description' => json_encode($null),
            'module' => null,
            'template' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $attributes));
    }
}
