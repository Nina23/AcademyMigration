<?php

namespace TypiCMS\Modules\Banners\Http\Controllers;

use Illuminate\View\View;
use TypiCMS\Modules\Core\Http\Controllers\BasePublicController;
use TypiCMS\Modules\Banners\Models\Banner;

class PublicController extends BasePublicController
{
    public function index(): View
    {
        $models = Banner::published()->order()->with('image')->get();

        return view('banners::public.index')
            ->with(compact('models'));
    }

    public function show($slug): View
    {
        $model = Banner::published()->whereSlugIs($slug)->firstOrFail();

        return view('banners::public.show')
            ->with(compact('model'));
    }
}
