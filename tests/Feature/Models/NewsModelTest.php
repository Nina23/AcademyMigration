<?php

namespace Tests\Feature\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use TypiCMS\Modules\Categories\Models\Category;
use TypiCMS\Modules\News\Models\News;

/**
 * News: the standard TypiCMS module pattern shared by most local modules
 * (Base model, JSON translations, category belongsTo, HasFiles morphToMany,
 * Historable, model caching, published/order scopes).
 */
class NewsModelTest extends TestCase
{
    public function test_news_can_be_created_and_read_back_from_the_database()
    {
        $id = $this->createNews()->id;

        $news = News::find($id);

        $this->assertInstanceOf(Carbon::class, $news->date);
        $this->assertSame('2026-01-15', $news->date->format('Y-m-d'));
        $this->assertSame('Изложба студената', $news->title);   // app locale "sr"
        $this->assertSame('<p>Tijelo</p>', $news->getTranslation('body', 'sr-latn'));
        $this->assertTrue($news->isPublished('en'));
    }

    public function test_news_can_be_updated_and_deleted()
    {
        $news = $this->createNews();

        $news->update(['date' => '2026-02-01', 'category_id' => null]);
        $news->setTranslation('summary', 'en', 'New summary')->save();

        $fresh = News::find($news->id);
        $this->assertSame('2026-02-01', $fresh->date->format('Y-m-d'));
        $this->assertNull($fresh->category_id);
        $this->assertSame('New summary', $fresh->getTranslation('summary', 'en'));

        $fresh->delete();

        $this->assertNull(News::find($news->id));
        $this->assertDatabaseMissing('news', ['id' => $news->id]);
        $this->assertDatabaseHas('history', ['historable_id' => $news->id, 'historable_type' => News::class, 'action' => 'deleted']);
    }

    public function test_news_belongs_to_a_category_and_categories_count_their_news()
    {
        $news = $this->createNews();
        $this->createNews(['slug' => ['sr' => 'druga-cr', 'sr-latn' => 'druga', 'en' => 'second']]);

        $this->assertSame(1, (int) $news->category->id);
        $this->assertSame('General', $news->category->getTranslation('title', 'en'));

        // Query used by the public news index.
        $category = Category::withCount('news')->connection()->get()->firstWhere('id', 1);
        $this->assertSame(2, (int) $category->news_count);
    }

    public function test_attached_files_are_split_into_images_and_documents_in_position_order()
    {
        $news = $this->createNews();
        $image1 = $this->insertFile('i', 'files/photo-1.jpg');
        $document = $this->insertFile('d', 'files/program.pdf');
        $image2 = $this->insertFile('i', 'files/photo-2.jpg');

        // Same format the admin form posts (HasFiles::syncIds).
        $news->syncIds("{$image2},{$document},{$image1}");

        $news = News::with(['images', 'documents'])->find($news->id);
        $this->assertSame([$image2, $image1], $news->images->pluck('id')->map('intval')->all());
        $this->assertSame([$document], $news->documents->pluck('id')->map('intval')->all());
        $this->assertSame(3, DB::table('model_has_files')->where('model_id', $news->id)->where('model_type', News::class)->count());
    }

    public function test_highlight_scope_filters_on_the_current_locale()
    {
        $this->createNews(['highlight' => ['sr' => '1', 'sr-latn' => '0', 'en' => '0']]);

        $this->assertSame(1, News::published()->highlight()->count());

        App::setLocale('en');
        $this->assertSame(0, News::published()->highlight()->count());
    }

    private function insertFile(string $type, string $path): int
    {
        return DB::table('files')->insertGetId([
            'type' => $type,
            'name' => basename($path),
            'path' => $path,
            'extension' => pathinfo($path, PATHINFO_EXTENSION),
            'description' => '{}',
            'alt_attribute' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
