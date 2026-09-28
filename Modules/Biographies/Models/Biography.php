<?php

namespace TypiCMS\Modules\Biographies\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laracasts\Presenter\PresentableTrait;
use Spatie\Translatable\HasTranslations;
use TypiCMS\Modules\Core\Models\Base;
use TypiCMS\Modules\Files\Models\File;
use TypiCMS\Modules\Files\Traits\HasFiles;
use TypiCMS\Modules\History\Traits\Historable;
use TypiCMS\Modules\Biographies\Presenters\ModulePresenter;
use TypiCMS\Modules\Departments\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;

class Biography extends Base
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
        'name',
        'section',
        'awards',
        'publications',
        'chef_departments',
        'directory_departments'
    ];

    public function getThumbAttribute(): string
    {
        return $this->present()->image(null, 54);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }


    public function isDirectoryDepartments($locale = null): bool
    {

        $locale = $locale ?: app()->getLocale();
        return (bool) $this->translate('directory_departments', $locale);
    
    }

    public function isChefDepartments($locale = null): bool
    {
        
        $locale = $locale ?: app()->getLocale();
        return (bool) $this->translate('chef_departments', $locale);
    }

    public function uri($locale = null): string
    {
    
        $locale = $locale ?: config('app.locale');
        $route = $locale.'::'.Str::singular($this->getTable());
        if (Route::has($route)) {
            return $this->department->category->slug .'/' .$this->translate('slug', $locale);
        }

        return '/';
    }
}
