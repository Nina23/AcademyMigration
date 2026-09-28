<?php

namespace TypiCMS\Modules\Advertismentboards\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laracasts\Presenter\PresentableTrait;
use Spatie\Translatable\HasTranslations;
use TypiCMS\Modules\Core\Models\Base;
use TypiCMS\Modules\Files\Models\File;
use TypiCMS\Modules\Files\Traits\HasFiles;
use TypiCMS\Modules\History\Traits\Historable;
use TypiCMS\Modules\Categories\Models\Category;
use TypiCMS\Modules\Advertismentboards\Presenters\ModulePresenter;
use TypiCMS\Modules\Tags\Traits\HasTags;
use TypiCms\Modules\Tags\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class Advertismentboard extends Base
{
    use HasFiles;
    use HasTags;
    use HasTranslations;
    use Historable;
    use PresentableTrait;

    protected $presenter = ModulePresenter::class;

    protected $guarded = [];

     protected $dates = ['date'];

    public $translatable = [
        'title',
        'slug',
        'status',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function scopeFilterByTag(Builder $query,$tag_id): Builder
    {
        if ($tag_id==0) {
            return $query;
        }
       return $query->whereHas('tags', function ($q) use ($tag_id) {
            return $q->where('tags.id', $tag_id);
        });
    }
}
