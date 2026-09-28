<?php

namespace TypiCMS\Modules\Categories\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use TypiCMS\Modules\Core\Filters\FilterOr;
use TypiCMS\Modules\Core\Http\Controllers\BaseApiController;
use TypiCMS\Modules\Categories\Models\Category;

class ApiController extends BaseApiController
{
    public function index(Request $request): LengthAwarePaginator
    {
        $data = QueryBuilder::for(Category::class)
            ->selectFields($request->input('fields.categories'))
            ->allowedSorts(['status_translated', 'title_translated'])
            ->allowedFilters([
                AllowedFilter::custom('title', new FilterOr()),
            ])
            ->allowedIncludes(['image'])
            ->paginate($request->input('per_page'));

        return $data;
    }

    protected function updatePartial(Category $category, Request $request)
    {
        foreach ($request->only('status') as $key => $content) {
            if ($category->isTranslatableAttribute($key)) {
                foreach ($content as $lang => $value) {
                    $category->setTranslation($key, $lang, $value);
                }
            } else {
                $category->{$key} = $content;
            }
        }

        $category->save();
    }

    public function destroy(Category $category)
    {
        $category->delete();
    }
}
