<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use TypiCMS\Modules\Classschedules\Models\Classschedule;

/**
 * The admin lists (ItemList.vue) request computed columns with `append=`
 * (e.g. append=thumb for the thumbnail column, append=department,formatted_year
 * for class schedules). spatie/laravel-query-builder 3 appends every requested
 * attribute to the result models (no allowedAppends() is configured); the
 * attributes are not in the models' $appends.
 *
 * Only the presence with append= is asserted: a second request without append=
 * in the same app instance may still return the appended attributes, because
 * the model cache (genealabs Cachable) returns the models of the first request
 * for the same query (also the case in production with the file cache).
 */
class AppendsTest extends TestCase
{
    public function test_news_list_appends_the_requested_thumb()
    {
        $headers = $this->apiHeaders($this->createSuperUser());
        $this->createNews();

        // Query string from resources/views/vendor/news/admin/index.blade.php + ItemList.vue.
        $query = [
            'sort' => '-date',
            'fields' => ['news' => 'id,image_id,date,status,title'],
            'include' => 'image',
            'locale' => 'sr',
            'page' => 1,
            'per_page' => 100,
        ];

        $row = $this->getJson('/api/news?'.http_build_query($query + ['append' => 'thumb']), $headers)
            ->assertOk()->json('data.0');
        $this->assertArrayHasKey('thumb', $row);
        $this->assertSame('', $row['thumb']);   // News presenter: no image -> ''
    }

    public function test_classschedules_list_appends_department_and_formatted_year()
    {
        $headers = $this->apiHeaders($this->createSuperUser());
        Classschedule::create([
            'title' => ['sr' => 'Распоред', 'sr-latn' => 'Raspored', 'en' => 'Schedule'],
            'slug' => ['sr' => 'raspored-cr', 'sr-latn' => 'raspored', 'en' => 'schedule'],
            'status' => ['sr' => '1', 'sr-latn' => '1', 'en' => '1'],
            'summary' => ['sr' => '', 'sr-latn' => '', 'en' => ''],
            'announcement_department_id' => 1,
            'year' => 2,
        ]);

        // Query string from resources/views/vendor/classschedules/admin/index.blade.php + ItemList.vue.
        $query = [
            'sort' => 'title_translated',
            'fields' => ['classschedules' => 'id,status,title,announcement_department_id,year'],
            'include' => 'image',
            'locale' => 'sr',
            'page' => 1,
            'per_page' => 100,
        ];

        $row = $this->getJson('/api/classschedules?'.http_build_query($query + ['append' => 'department,formatted_year']), $headers)
            ->assertOk()->json('data.0');
        $this->assertArrayHasKey('department', $row);
        $this->assertArrayHasKey('formatted_year', $row);
        $this->assertNotSame('', $row['department']);
        $this->assertNotSame('', $row['formatted_year']);
        $this->assertSame(1, $row['announcement_department']['id']);   // loaded by the department accessor
    }
}
