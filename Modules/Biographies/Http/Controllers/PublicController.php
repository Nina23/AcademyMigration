<?php

namespace TypiCMS\Modules\Biographies\Http\Controllers;

use Illuminate\View\View;
use TypiCMS\Modules\Core\Http\Controllers\BasePublicController;
use TypiCMS\Modules\Biographies\Models\Biography;
use Illuminate\Http\Request;
use TypiCMS\Modules\Categories\Models\Category;

class PublicController extends BasePublicController
{
    public function index(Request $request): View
    {
        $urlArray= explode('/',$request->url());
        $categorySlug=end($urlArray);
        $category= Category::whereSlugIs($categorySlug)->firstOrFail();
        $departments=[];
        if($category){
           $departments= $category->departments()->get();
        }
      
        $biographies= Biography::whereIn('department_id',$departments->pluck('id'))->get();
        return view('biographies::public.index')
            ->with(compact('category', 'departments', 'biographies'));
    }

    public function show($slug): View
    {
        $model = Biography::published()->whereSlugIs($slug)->firstOrFail();

        return view('biographies::public.show')
            ->with(compact('model'));
    }
}
