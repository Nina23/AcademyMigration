<?php

namespace TypiCMS\Modules\Classschedules\Http\Requests;

use TypiCMS\Modules\Core\Http\Requests\AbstractFormRequest;

class FormRequest extends AbstractFormRequest
{
    public function rules()
    {
        return [
            'image_id' => 'nullable|integer',
            'title.*' => 'nullable|max:255',
            'slug.*' => 'nullable|alpha_dash|max:255|required_if:status.*,1|required_with:title.*',
            'status.*' => 'boolean',
            'summary.*' => 'nullable',
            'body.*' => 'nullable',
            'professor.*' => 'nullable|max:255',
            'location.*' => 'nullable|max:255',
            'announcement_category_id' => 'nullable|integer',
            'announcement_department_id' => 'nullable|integer',
            'year' => 'nullable|integer',
            'from_date'=>'date',
            'to_date'=>'date',
            'expiry'=>'date',
            'day_id' => 'nullable|integer',
            'position' => 'nullable|integer',
        ];
    }
}
