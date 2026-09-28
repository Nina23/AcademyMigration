<?php

namespace TypiCMS\Modules\Galleries\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use TypiCMS\Modules\Core\Filters\FilterOr;
use TypiCMS\Modules\Core\Http\Controllers\BaseApiController;
use TypiCMS\Modules\Galleries\Models\Gallery;

class ApiController extends BaseApiController
{
    public function index(Request $request): LengthAwarePaginator
    {
        $data = QueryBuilder::for(Gallery::class)
            ->selectFields($request->input('fields.galleries'))
            ->allowedSorts(['status_translated', 'title_translated'])
            ->allowedFilters([
                AllowedFilter::custom('title', new FilterOr()),
            ])
            ->allowedIncludes(['image'])
            ->paginate($request->input('per_page'));

        return $data;
    }

    protected function updatePartial(Gallery $gallery, Request $request)
    {
        foreach ($request->only('status') as $key => $content) {
            if ($gallery->isTranslatableAttribute($key)) {
                foreach ($content as $lang => $value) {
                    $gallery->setTranslation($key, $lang, $value);
                }
            } else {
                $gallery->{$key} = $content;
            }
        }

        $gallery->save();
    }

    public function destroy(Gallery $gallery)
    {
        $gallery->delete();
    }
}
