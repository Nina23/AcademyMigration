<?php

namespace TypiCMS\Modules\Biographies\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use TypiCMS\Modules\Core\Filters\FilterOr;
use TypiCMS\Modules\Core\Http\Controllers\BaseApiController;
use TypiCMS\Modules\Biographies\Models\Biography;

class ApiController extends BaseApiController
{
    public function index(Request $request): LengthAwarePaginator
    {
        $data = QueryBuilder::for(Biography::class)
            ->selectFields($request->input('fields.biographies'))
            ->allowedSorts(['status_translated', 'title_translated'])
            ->allowedFilters([
                AllowedFilter::custom('title', new FilterOr()),
            ])
            ->allowedIncludes(['image'])
            ->paginate($request->input('per_page'));

        return $data;
    }

    protected function updatePartial(Biography $biography, Request $request)
    {
        foreach ($request->only('status') as $key => $content) {
            if ($biography->isTranslatableAttribute($key)) {
                foreach ($content as $lang => $value) {
                    $biography->setTranslation($key, $lang, $value);
                }
            } else {
                $biography->{$key} = $content;
            }
        }

        $biography->save();
    }

    public function destroy(Biography $biography)
    {
        $biography->delete();
    }
}
