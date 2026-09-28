@push('js')
    <script src="{{ asset('components/ckeditor4/ckeditor.js') }}"></script>
    <script src="{{ asset('components/ckeditor4/config-full.js') }}"></script>
    <script src="{{ asset('js/transliterate.js') }}"> </script>
@endpush

@component('core::admin._buttons-form', ['model' => $model])
@endcomponent

{!! BootForm::hidden('id') !!}

<file-manager related-table="{{ $model->getTable() }}" :related-id="{{ $model->id ?? 0 }}"></file-manager>
<file-field type="image" field="image_id" :init-file="{{ $model->image ?? 'null' }}"></file-field>
<files-field :init-files="{{ $model->files }}"></files-field>

@include('core::form._title-and-slug')
<div class="form-group">
    {!! TranslatableBootForm::hidden('status')->value(0) !!}
    {!! TranslatableBootForm::hidden('directory_departments')->value(0) !!}
    {!! TranslatableBootForm::hidden('chef_departments')->value(0) !!}
    {!! TranslatableBootForm::checkbox(__('Published'), 'status') !!}
    {!! TranslatableBootForm::checkbox(__('Department directory'), 'directory_departments') !!}
    {!! TranslatableBootForm::checkbox(__('Chef department'), 'chef_departments') !!}
    {!! BootForm::select(__('Departments'), 'department_id', ['' => ''] + Departments::pluck('title', 'id')->all(), null, ['class' => 'form-control'])->addClass('custom-select') !!}
   
</div>
{!! TranslatableBootForm::textarea(__('Summary'), 'summary')->rows(4)->onkeyup("keyup('summary')") !!}
{!! TranslatableBootForm::text(__('User title'), 'name') !!}
{!! TranslatableBootForm::textarea(__('Section'), 'section')->addClass('ckeditor-full') !!}
{!! BootForm::text(__('Email'), 'email') !!} 
{!! BootForm::text(__('Phone'), 'phone') !!}
{!! BootForm::text(__('External link'), 'external_link')->placeholder('http://') !!}  
{!! BootForm::text(__('Linkedin'), 'linkedin') !!} 
{!! BootForm::text(__('Instagram'), 'instagram') !!} 
{!! BootForm::text(__('IMDB'), 'imdb') !!} 
{!! TranslatableBootForm::textarea(__('Awards'), 'awards')->addClass('ckeditor-full') !!}
{!! TranslatableBootForm::textarea(__('Publications'), 'publications')->addClass('ckeditor-full') !!}
