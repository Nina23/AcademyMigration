@push('js')
    <script src="{{ asset('components/ckeditor4/ckeditor.js') }}"></script>
    <script src="{{ asset('components/ckeditor4/config-full.js') }}"></script>
  
    <script src="{{ asset('js/transliterate.js') }}"> </script>
    
@endpush

@component('core::admin._buttons-form', ['model' => $model])
@endcomponent

{!! BootForm::hidden('id') !!}

<file-manager></file-manager>
<file-field type="image" field="image_id" :init-file="{{ $model->image ?? 'null' }}"></file-field>
<files-field :init-files="{{ $model->files }}"></files-field>

<div class="form-row">
    <div class="col-sm-6">
        {!! BootForm::date(__('Date'), 'date')->value(old('date') ? : $model->present()->dateOrNow('date'))->addClass('datepicker')->required() !!}
    </div>
</div>

@include('core::form._title-and-slug')
<div class="form-group">
{!! BootForm::select(__('Categories'), 'category_id', ['' => ''] + Categories::where('connection',0)->pluck('title', 'id')->all(), null, ['class' => 'form-control'])->addClass('custom-select') !!}
    
    {!! TranslatableBootForm::hidden('status')->value(0) !!}
    {!! TranslatableBootForm::checkbox(__('Published'), 'status') !!}

    {!! TranslatableBootForm::hidden('highlight')->value(0) !!}
    {!! TranslatableBootForm::checkbox(__('Highlight'), 'highlight') !!}
</div>
{!! TranslatableBootForm::textarea(__('Summary'), 'summary')->rows(4)->onkeyup("keyup('summary')") !!}
{!! TranslatableBootForm::textarea(__('Body'), 'body')->addClass('ckeditor-full')->onkeyup("keyup('body')") !!}
