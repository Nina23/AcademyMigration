<?php

namespace TypiCMS\Modules\Banners\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use TypiCMS\Modules\Core\Filters\FilterOr;
use TypiCMS\Modules\Core\Http\Controllers\BaseApiController;
use TypiCMS\Modules\Banners\Models\Banner;

class ApiController extends BaseApiController
{
    public function index(Request $request): LengthAwarePaginator
    {
        $data = QueryBuilder::for(Banner::class)
            ->selectFields($request->input('fields.banners'))
            ->allowedSorts(['status_translated', 'title_translated'])
            ->allowedFilters([
                AllowedFilter::custom('title', new FilterOr()),
            ])
            ->allowedIncludes(['image'])
            ->paginate($request->input('per_page'));

        return $data;
    }

    protected function updatePartial(Banner $banner, Request $request)
    {
        foreach ($request->only('status') as $key => $content) {
            if ($banner->isTranslatableAttribute($key)) {
                foreach ($content as $lang => $value) {
                    $banner->setTranslation($key, $lang, $value);
                }
            } else {
                $banner->{$key} = $content;
            }
        }

        $banner->save();
    }

    public function destroy(Banner $banner)
    {
        $banner->delete();
    }
}
