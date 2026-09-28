<?php

namespace TypiCMS\Modules\Advertismentboards\Http\Controllers;

use Illuminate\View\View;
use TypiCMS\Modules\Core\Http\Controllers\BasePublicController;
use TypiCMS\Modules\Advertismentboards\Models\Advertismentboard;
use Illuminate\Http\Request;
use TypiCMS\Modules\Tags\Models\Tag;

class PublicController extends BasePublicController
{
    public function index(Request $request): View
    {
        
        $models = Advertismentboard::published()->filterByTag($request['tag_id'])->order()->with('image')->paginate(config('typicms.news.per_page'));

         $tags = Tag::withCount('advertismentboards')->get();

         $countAdvertisment=Advertismentboard::published()->count();

            if ($request->ajax()) {
                return view('advertismentboards::public._list', compact('models'));
            }

        return view('advertismentboards::public.index')
            ->with(compact('models', 'tags', 'countAdvertisment'));
    }

    public function show($slug): View
    {
        $model = Advertismentboard::published()->whereSlugIs($slug)->firstOrFail();

        return view('advertismentboards::public.show')
            ->with(compact('model'));
    }
}
