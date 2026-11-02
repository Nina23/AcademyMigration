<?php

namespace App\Pagination;

use Illuminate\Pagination\LengthAwarePaginator as BaseLengthAwarePaginator;
use Illuminate\Support\Arr;

/**
 * Laravel's paginator, with one difference: the entries of "links" have only
 * url, label and active, as on Laravel 8-11.
 *
 * Laravel 12 adds a "page" key to every entry, which changes the JSON of every
 * paginated admin API list. Bound in AppServiceProvider (Laravel builds paginators
 * through the container). Check against Illuminate\Pagination\LengthAwarePaginator
 * in every Laravel upgrade (UPGRADE_PLAN.md).
 */
class LengthAwarePaginator extends BaseLengthAwarePaginator
{
    public function linkCollection()
    {
        return parent::linkCollection()->map(function ($link) {
            return Arr::except($link, 'page');
        });
    }
}
