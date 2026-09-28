<?php

namespace TypiCMS\Modules\Banners\Http\Requests;

use TypiCMS\Modules\Core\Http\Requests\AbstractFormRequest;

class FormRequest extends AbstractFormRequest
{
    public function rules()
    {
        return [
            'image_id' => 'nullable|integer',
            'title.*' => 'nullable|max:255',
            'slug.*' => 'nullable',
            'status.*' => 'boolean',
            'summary.*' => 'nullable',
            'link' => 'required',
        ];
    }
}
