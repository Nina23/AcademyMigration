<?php

namespace TypiCMS\Modules\Tags\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laracasts\Presenter\PresentableTrait;
use TypiCMS\Modules\Core\Models\Base;
use TypiCMS\Modules\History\Traits\Historable;
use TypiCMS\Modules\Tags\Presenters\ModulePresenter;
use TypiCMS\Modules\Advertismentboards\Models\Advertismentboard;
use TypiCMS\Modules\Announcements\Models\Announcement;

class Tag extends Base
{
    use Historable;
    use PresentableTrait;

    protected $presenter = ModulePresenter::class;

    protected $guarded = [];

    public function scopePublished(Builder $query): Builder
    {
        return $query;
    }

    public function advertismentboards(): MorphToMany
    {
        return $this->morphedByMany(Advertismentboard::class, 'taggable');
    }

    public function uri($locale = null): string
    {
        $locale = $locale ?: config('app.locale');
        $route = $locale.'::'.Str::singular($this->getTable());
        if (Route::has($route)) {
            return route($route, $this->slug);
        }

        return '/';
    }

    public function announcement(): MorphToMany
    {
        return $this->morphedByMany(Announcement::class, 'taggable');
    }
}
