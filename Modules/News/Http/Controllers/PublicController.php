<?php

namespace TypiCMS\Modules\News\Http\Controllers;

use Illuminate\View\View;
use TypiCMS\Modules\Core\Http\Controllers\BasePublicController;
use TypiCMS\Modules\News\Models\News;
use TypiCMS\Modules\Categories\Models\Category;
use Illuminate\Http\Request;


class PublicController extends BasePublicController
{
    public function index(Request $request): View
    {
        $models = News::published()
            ->filterByCategory($request['category_id'])
            ->order()
            ->with('image')
            ->paginate(config('typicms.news.per_page'));
        
        $highlightNews=News::published()
        ->highlight()
        ->order()
        ->take(3)
        ->get();

         $categories = Category::withCount('news')->connection()->get();

         $countNews=News::published()->count();

            if ($request->ajax()) {
                return view('news::public._list', compact('models'));
            }

        return view('news::public.index')
            ->with(compact('models', 'highlightNews', 'categories', 'countNews'));
    }

    public function show($slug): View
    {
        $model = News::published()
            ->with([
                'image',
                'images',
                'documents',
            ])
            ->whereSlugIs($slug)
            ->firstOrFail();

        return view('news::public.show')
            ->with(compact('model'));
    }
}
