<?php

namespace TypiCMS\Modules\Departments\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laracasts\Presenter\PresentableTrait;
use Spatie\Translatable\HasTranslations;
use TypiCMS\Modules\Core\Models\Base;
use TypiCMS\Modules\Files\Models\File;
use TypiCMS\Modules\Files\Traits\HasFiles;
use TypiCMS\Modules\History\Traits\Historable;
use TypiCMS\Modules\Departments\Presenters\ModulePresenter;
use TypiCMS\Modules\Categories\Models\Category;
use TypiCMS\Modules\Biographies\Models\Biography;


class Department extends Base
{
    use HasFiles;
    use HasTranslations;
    use Historable;
    use PresentableTrait;

    protected $presenter = ModulePresenter::class;

    protected $guarded = [];

    public $translatable = [
        'title',
        'slug',
        'status',
    ];

    public function getThumbAttribute(): string
    {
        return $this->present()->image(null, 54);
    }


    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function biographies(){
        return $this->hasMany(Biography::class);
    }
}
