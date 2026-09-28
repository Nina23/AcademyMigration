<?php

namespace TypiCMS\Modules\Categories\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laracasts\Presenter\PresentableTrait;
use Spatie\Translatable\HasTranslations;
use TypiCMS\Modules\Core\Models\Base;
use TypiCMS\Modules\Files\Models\File;
use TypiCMS\Modules\Files\Traits\HasFiles;
use TypiCMS\Modules\History\Traits\Historable;
use TypiCMS\Modules\Categories\Presenters\ModulePresenter;
use Illuminate\Database\Eloquent\Builder;
use TypiCMS\Modules\Advertismentboards\Models\Advertismentboard;
use TypiCMS\Modules\News\Models\News;
use TypiCMS\Modules\Departments\Models\Department;


class Category extends Base
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
        'summary',
        'body',
        'type'
    ];

    public function getThumbAttribute(): string
    {
        return $this->present()->image(null, 54);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id');
    }
    
    public function news()
    {
        return $this->hasMany(News::class);
    }

    public function advertismentboards()
    {
        return $this->hasMany(Advertismentboard::class);
    }

    public function scopeConnection(Builder $query, $connection=0): Builder
    {
        $field = 'connection';
        return $query->where($field, $connection);
    }

    public function departments(){
        return $this->hasMany(Department::class);
    }

}
