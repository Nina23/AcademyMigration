<?php

namespace TypiCMS\Modules\Biographies\Http\Requests;

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
            'directory_departments.*' => 'boolean',
            'chef_departments.*' => 'boolean',
            'summary.*' => 'nullable',
            'name.*' => 'nullable|max:255',
            'email'=>'nullable|email',
            'department_id' => 'integer|exists:departments,id',
            'phone'=>'nullable|numeric',
            'external_link'=>'nullable|active_url',
            'imdb'=>'nullable|active_url',
            'linkedin'=>'nullable|active_url',
            'instagram' => 'nullable|active_url',
            'awards'=>'nullable',
            'section'=>'nullable',
            'publications'=>'nullable'
        ];
    }
}
