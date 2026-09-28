<?php

namespace TypiCMS\Modules\Advertismentboards\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use TypiCMS\Modules\Core\Filters\FilterOr;
use TypiCMS\Modules\Core\Http\Controllers\BaseApiController;
use TypiCMS\Modules\Advertismentboards\Models\Advertismentboard;

class ApiController extends BaseApiController
{
    public function index(Request $request): LengthAwarePaginator
    {
        $data = QueryBuilder::for(Advertismentboard::class)
            ->selectFields($request->input('fields.advertismentboards'))
            ->allowedSorts(['status_translated', 'title_translated'])
            ->allowedFilters([
                AllowedFilter::custom('title', new FilterOr()),
            ])
            ->allowedIncludes(['image'])
            ->paginate($request->input('per_page'));

        return $data;
    }

    protected function updatePartial(Advertismentboard $advertismentboard, Request $request)
    {
        foreach ($request->only('status') as $key => $content) {
            if ($advertismentboard->isTranslatableAttribute($key)) {
                foreach ($content as $lang => $value) {
                    $advertismentboard->setTranslation($key, $lang, $value);
                }
            } else {
                $advertismentboard->{$key} = $content;
            }
        }

        $advertismentboard->save();
    }

    public function destroy(Advertismentboard $advertismentboard)
    {
        $advertismentboard->delete();
    }
}
