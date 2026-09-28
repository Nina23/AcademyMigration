<?php

namespace TypiCMS\Modules\Galleries\Http\Controllers;

use Illuminate\View\View;
use TypiCMS\Modules\Core\Http\Controllers\BasePublicController;
use TypiCMS\Modules\Galleries\Models\Gallery;

class PublicController extends BasePublicController
{
    public function index(): View
    {
        $models = Gallery::published()->order()->with('image')->get();

        return view('galleries::public.index')
            ->with(compact('models'));
    }

    public function show($slug): View
    {
        $model = Gallery::published()->whereSlugIs($slug)->firstOrFail();

        return view('galleries::public.show')
            ->with(compact('model'));
    }
}
