<?php

namespace TypiCMS\Modules\Newsletters\Http\Controllers;

use Illuminate\View\View;
use TypiCMS\Modules\Core\Http\Controllers\BasePublicController;
use TypiCMS\Modules\Newsletters\Models\Newsletter;

class PublicController extends BasePublicController
{
    public function index(): View
    {
        $models = Newsletter::published()->order()->with('image')->get();

        return view('newsletters::public.index')
            ->with(compact('models'));
    }

    public function show($slug): View
    {
        $model = Newsletter::published()->whereSlugIs($slug)->firstOrFail();

        return view('newsletters::public.show')
            ->with(compact('model'));
    }
}
