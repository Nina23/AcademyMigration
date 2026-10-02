<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use TypiCMS\Modules\News\Models\News;

/**
 * News API = the generated-module API pattern used by most local modules
 * (Advertismentboards, Announcements, Banners, Biographies, Categories,
 * Departments, Galleries, Newsletters, ...): token guard, can:* permissions,
 * spatie/laravel-query-builder list with translated columns, PATCH status, DELETE.
 */
class NewsApiTest extends TestCase
{
    /**
     * Query string built by resources/js/components/ItemList.vue.
     */
    private function listUrl(string $locale, array $extra = []): string
    {
        return '/api/news?'.http_build_query(array_merge([
            'sort' => '-date',
            'fields' => ['news' => 'id,image_id,date,title,status'],
            'include' => 'image',
            'locale' => $locale,
            'per_page' => 50,
        ], $extra));
    }

    public function test_api_requires_a_valid_token()
    {
        $this->getJson($this->listUrl('sr'))->assertUnauthorized();
        $this->getJson($this->listUrl('sr'), ['Authorization' => 'Bearer not-a-real-token'])->assertUnauthorized();
    }

    public function test_api_enforces_permissions()
    {
        $reader = $this->createUserWithPermissions(['read news']);
        $news = $this->createNews();

        $this->getJson($this->listUrl('sr'), $this->apiHeaders($this->createUser()))->assertForbidden();
        $this->getJson($this->listUrl('sr'), $this->apiHeaders($reader))->assertOk();
        $this->patchJson("/api/news/{$news->id}", ['status' => ['en' => '0']], $this->apiHeaders($reader))->assertForbidden();
        $this->deleteJson("/api/news/{$news->id}", [], $this->apiHeaders($reader))->assertForbidden();
    }

    public function test_list_returns_paginated_translated_rows_in_the_requested_locale()
    {
        $headers = $this->apiHeaders($this->createSuperUser());
        $older = $this->createNews(['date' => '2025-06-01', 'slug' => ['sr' => 'stara-cr', 'sr-latn' => 'stara', 'en' => 'old']]);
        $newer = $this->createNews(['date' => '2026-06-01']);

        $response = $this->getJson($this->listUrl('sr-latn'), $headers)->assertOk();

        $response->assertJsonStructure([
            'current_page', 'data' => [['id', 'image_id', 'date', 'title_translated', 'status_translated', 'image']],
            'first_page_url', 'from', 'last_page', 'last_page_url', 'next_page_url', 'path', 'per_page', 'prev_page_url', 'to', 'total',
        ]);
        $response->assertJson(['total' => 2, 'per_page' => 50, 'current_page' => 1]);
        $this->assertSame([$newer->id, $older->id], array_column($response->json('data'), 'id'));   // sort=-date
        $this->assertSame('Izložba studenata', $response->json('data.0.title_translated'));
        $this->assertSame(1, $response->json('data.0.status_translated'));
        $this->assertNull($response->json('data.0.image'));

        $this->getJson($this->listUrl('sr'), $headers)->assertJsonPath('data.0.title_translated', 'Изложба студената');
        $this->getJson($this->listUrl('en', ['per_page' => 1]), $headers)
            ->assertJsonPath('data.0.title_translated', 'Student exhibition')
            ->assertJson(['total' => 2, 'last_page' => 2]);
    }

    public function test_list_search_filters_on_the_translated_title_of_the_requested_locale()
    {
        $headers = $this->apiHeaders($this->createSuperUser());
        $match = $this->createNews();
        $this->createNews([
            'title' => ['sr' => 'Концерт', 'sr-latn' => 'Koncert', 'en' => 'Concert'],
            'slug' => ['sr' => 'koncert-cr', 'sr-latn' => 'koncert', 'en' => 'concert'],
        ]);

        $ids = array_column($this->getJson($this->listUrl('sr-latn', ['filter' => ['title' => 'IZLOŽBA']]), $headers)->assertOk()->json('data'), 'id');
        $this->assertSame([$match->id], $ids);

        $this->getJson($this->listUrl('en', ['filter' => ['title' => 'izložba']]), $headers)->assertJson(['total' => 0]);
    }

    public function test_patch_updates_only_the_given_status_locales_and_delete_removes_the_item()
    {
        $headers = $this->apiHeaders($this->createSuperUser());
        $news = $this->createNews();

        $this->patchJson("/api/news/{$news->id}", ['status' => ['en' => '0']], $headers)->assertOk();
        $this->assertSame(['sr' => '1', 'sr-latn' => '1', 'en' => '0'], News::find($news->id)->getTranslations('status'));

        // Only "status" is patchable; other fields are ignored.
        $this->patchJson("/api/news/{$news->id}", ['date' => '2000-01-01', 'title' => ['en' => 'Hacked']], $headers)->assertOk();
        $this->assertSame('2026-01-15', News::find($news->id)->date->format('Y-m-d'));
        $this->assertSame('Student exhibition', News::find($news->id)->getTranslation('title', 'en'));

        $this->deleteJson("/api/news/{$news->id}", [], $headers)->assertOk();
        $this->assertNull(News::find($news->id));
    }
}
