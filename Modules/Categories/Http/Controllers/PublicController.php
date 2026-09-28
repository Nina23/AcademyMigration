<?php

namespace TypiCMS\Modules\Categories\Http\Controllers;

use Illuminate\View\View;
use TypiCMS\Modules\Core\Http\Controllers\BasePublicController;
use TypiCMS\Modules\Categories\Models\Category;

class PublicController extends BasePublicController
{
    public function index(): View
    {
        $models = Category::published()->order()->with('image')->get();

        return view('categories::public.index')
            ->with(compact('models'));
    }

    public function show($slug): View
    {
        $model = Category::published()->whereSlugIs($slug)->firstOrFail();

        return view('categories::public.show')
            ->with(compact('model'));
    }
}
