<?php

namespace TypiCMS\Modules\News\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laracasts\Presenter\PresentableTrait;
use Spatie\Translatable\HasTranslations;
use TypiCMS\Modules\Core\Models\Base;
use TypiCMS\Modules\Files\Models\File;
use TypiCMS\Modules\Files\Traits\HasFiles;
use TypiCMS\Modules\History\Traits\Historable;
use TypiCMS\Modules\News\Presenters\ModulePresenter;
use Illuminate\Database\Eloquent\Builder;
use TypiCMS\Modules\Categories\Models\Category;

class News extends Base
{
    use HasFiles;
    use HasTranslations;
    use Historable;
    use PresentableTrait;

    protected $presenter = ModulePresenter::class;

    protected $dates = ['date'];

    protected $guarded = [];

    public $translatable = [
        'title',
        'slug',
        'status',
        'highlight',
        'summary',
        'body',
    ];

    public function getThumbAttribute(): string
    {
        return $this->present()->image(null, 54);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id');
    }

    public function scopeHighlight(Builder $query): Builder
    {
        if (request('preview')) {
            return $query;
        }
        $field = 'highlight';
        if (in_array($field, (array) $this->translatable)) {
            $field .= '->'.config('app.locale');
        }

        return $query->where($field, '1');
    }

    public function scopeFilterByCategory(Builder $query, $category_id=0): Builder
    {
        if ($category_id==0) {
            return $query;
        }
        $field = 'category_id';

        return $query->where($field, $category_id);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
