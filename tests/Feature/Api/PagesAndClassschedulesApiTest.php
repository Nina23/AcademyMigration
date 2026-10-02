<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use TypiCMS\Modules\Classschedules\Models\Classschedule;
use TypiCMS\Modules\Pages\Models\Page;

/**
 * Pages API (nested tree used by ItemListTree.vue, protected home page) and the
 * custom Classschedules API list.
 */
class PagesAndClassschedulesApiTest extends TestCase
{
    public function test_pages_api_returns_the_nested_page_tree()
    {
        $headers = $this->apiHeaders($this->createSuperUser());
        Page::find(3)->update(['parent_id' => 2]);   // class schedule page under the news page

        $url = '/api/pages?'.http_build_query([
            'fields' => ['pages' => 'id,position,parent_id,module,redirect,is_home,private,status,title,slug,uri'],
            'locale' => 'sr-latn',
        ]);
        $response = $this->getJson($url, $headers)->assertOk();

        $response->assertJsonStructure([['id', 'position', 'parent_id', 'module', 'is_home', 'title_translated', 'status_translated', 'uri_translated', 'data', 'isLeaf', 'isExpanded', 'children']]);
        $this->assertSame([1, 2], array_column($response->json(), 'id'));   // roots, by position
        $this->assertSame('Novosti', $response->json('1.title_translated'));
        $this->assertTrue($response->json('1.isLeaf'));                       // module page
        $this->assertFalse($response->json('0.isLeaf'));
        $this->assertSame(3, $response->json('1.children.0.id'));
        $this->assertSame('Raspored', $response->json('1.children.0.title_translated'));
    }

    public function test_home_page_and_pages_with_children_cannot_be_deleted()
    {
        $headers = $this->apiHeaders($this->createSuperUser());
        Page::find(3)->update(['parent_id' => 2]);

        $this->deleteJson('/api/pages/1', [], $headers)
            ->assertForbidden()->assertJson(['message' => 'The home page cannot be deleted.']);
        $this->deleteJson('/api/pages/2', [], $headers)
            ->assertForbidden()->assertJson(['message' => 'This item cannot be deleted because it has children.']);

        $this->deleteJson('/api/pages/3', [], $headers)->assertOk();
        $this->assertNull(Page::find(3));
    }

    public function test_classschedules_api_lists_translated_rows()
    {
        $headers = $this->apiHeaders($this->createSuperUser());
        $schedule = Classschedule::create([
            'title' => ['sr' => 'Распоред', 'sr-latn' => 'Raspored', 'en' => 'Schedule'],
            'slug' => ['sr' => 'raspored-cr', 'sr-latn' => 'raspored', 'en' => 'schedule'],
            'status' => ['sr' => '1', 'sr-latn' => '1', 'en' => '0'],
            'summary' => ['sr' => '', 'sr-latn' => '', 'en' => ''],
            'announcement_department_id' => 1,
            'year' => 2,
        ]);

        // Query string from resources/views/vendor/classschedules/admin/index.blade.php + ItemList.vue.
        $url = '/api/classschedules?'.http_build_query([
            'sort' => 'title_translated',
            'fields' => ['classschedules' => 'id,status,title,announcement_department_id,year'],
            'include' => 'image',
            'locale' => 'en',
            'per_page' => 50,
        ]);

        $this->getJson($url, $headers)->assertOk()->assertJson([
            'total' => 1,
            'data' => [[
                'id' => $schedule->id, 'title_translated' => 'Schedule', 'status_translated' => 0,
                'announcement_department_id' => 1, 'year' => 2, 'image' => null,
            ]],
        ]);

        $this->patchJson("/api/classschedules/{$schedule->id}", ['status' => ['en' => '1']], $headers)->assertOk();
        $this->assertTrue(Classschedule::find($schedule->id)->isPublished('en'));
    }
}
