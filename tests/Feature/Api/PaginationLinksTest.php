<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

/**
 * The paginated admin API JSON: every entry of "links" has exactly url, label
 * and active (Laravel 12 adds a "page" key).
 */
class PaginationLinksTest extends TestCase
{
    public function test_pagination_links_have_url_label_and_active_only()
    {
        $this->createNews();

        $links = $this->getJson('/api/news?'.http_build_query([
            'sort' => '-date',
            'fields' => ['news' => 'id,date,title,status'],
            'locale' => 'sr',
            'page' => 1,
            'per_page' => 10,
        ]), $this->apiHeaders($this->createSuperUser()))
            ->assertOk()
            ->json('links');

        $this->assertCount(3, $links);
        foreach ($links as $link) {
            $this->assertSame(['url', 'label', 'active'], array_keys($link));
        }
    }
}
