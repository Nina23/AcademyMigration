<?php

namespace TypiCMS\Modules\Departments\Http\Controllers;

use Illuminate\View\View;
use TypiCMS\Modules\Core\Http\Controllers\BasePublicController;
use TypiCMS\Modules\Departments\Models\Department;

class PublicController extends BasePublicController
{
    public function index(): View
    {
        $models = Department::published()->order()->with('image')->get();

        return view('departments::public.index')
            ->with(compact('models'));
    }

    public function show($slug): View
    {
        $model = Department::published()->whereSlugIs($slug)->firstOrFail();

        return view('departments::public.show')
            ->with(compact('model'));
    }
}
