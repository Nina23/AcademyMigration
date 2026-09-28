<?php

namespace TypiCMS\Modules\Announcements\Http\Requests;

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
            'highlight.*' => 'nullable',
            'location.*' => 'nullable|max:255',
            'announcement_category_id' => 'nullable|integer',
            'year' => 'required|max:255',
            'program' => 'nullable|max:255',
            'date'=>'date',
            'expiry'=>'date',
        ];
    }
}
