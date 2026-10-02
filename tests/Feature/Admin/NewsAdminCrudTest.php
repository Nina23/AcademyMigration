<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use TypiCMS\Modules\News\Models\News;

/**
 * Representative admin CRUD through the real admin form endpoints
 * (FormRequest validation, translatable form arrays, redirects, history).
 */
class NewsAdminCrudTest extends TestCase
{
    private function formData(array $overrides = []): array
    {
        return array_replace_recursive([
            'date' => '2026-03-01',
            'category_id' => 1,
            'image_id' => null,
            'title' => ['sr' => 'Концерт', 'sr-latn' => 'Koncert', 'en' => 'Concert'],
            'slug' => ['sr' => 'koncert-cr', 'sr-latn' => 'koncert', 'en' => 'concert'],
            'status' => ['sr' => '1', 'sr-latn' => '1', 'en' => '0'],
            'highlight' => ['sr' => '0', 'sr-latn' => '0', 'en' => '0'],
            'summary' => ['sr' => 'Опис', 'sr-latn' => 'Opis', 'en' => 'Summary'],
            'body' => ['sr' => '<p>Текст</p>', 'sr-latn' => '<p>Tekst</p>', 'en' => '<p>Text</p>'],
        ], $overrides);
    }

    public function test_superuser_can_create_news_through_the_admin_form()
    {
        $admin = $this->createSuperUser();

        $response = $this->actingAs($admin)->post('/admin/news', $this->formData());

        $news = News::latest('id')->first();
        $response->assertRedirect(route('admin::edit-news', $news->id));

        $this->assertSame('2026-03-01', $news->date->format('Y-m-d'));
        $this->assertSame(1, (int) $news->category_id);
        $this->assertSame(['sr' => 'Концерт', 'sr-latn' => 'Koncert', 'en' => 'Concert'], $news->getTranslations('title'));
        $this->assertSame(['sr' => '1', 'sr-latn' => '1', 'en' => '0'], $news->getTranslations('status'));

        // Historable trait records who created it.
        $this->assertDatabaseHas('history', [
            'historable_type' => News::class, 'historable_id' => $news->id, 'action' => 'created', 'user_id' => $admin->id,
        ]);

        // "Save and exit" goes back to the index.
        $this->post('/admin/news', $this->formData(['slug' => ['en' => 'concert-2'], 'exit' => 1]))
            ->assertRedirect(route('admin::index-news'));
    }

    public function test_superuser_can_edit_and_update_news()
    {
        $this->actingAs($this->createSuperUser());
        $news = $this->createNews();

        $this->get("/admin/news/{$news->id}/edit")->assertOk()->assertSee('Student exhibition');

        $this->put("/admin/news/{$news->id}", $this->formData(['title' => ['en' => 'Updated title']]))
            ->assertRedirect(route('admin::edit-news', $news->id));

        $news->refresh();
        $this->assertSame('Updated title', $news->getTranslation('title', 'en'));
        $this->assertSame('Koncert', $news->getTranslation('title', 'sr-latn'));
        $this->assertSame(1, DB::table('history')->where('historable_id', $news->id)->where('action', 'updated')->count());
    }

    public function test_invalid_admin_input_is_rejected_and_nothing_is_saved()
    {
        $this->actingAs($this->createSuperUser());
        $before = News::count();

        $this->from('/admin/news/create')
            ->post('/admin/news', $this->formData([
                'date' => '01.03.2026',               // date_format:Y-m-d
                'slug' => ['en' => ''],                // required_with:title.*
                'category_id' => 999,                  // exists:categories,id
            ]))
            ->assertRedirect('/admin/news/create')
            ->assertSessionHasErrors(['date', 'slug.en', 'category_id']);

        $this->assertSame($before, News::count());
    }
}
