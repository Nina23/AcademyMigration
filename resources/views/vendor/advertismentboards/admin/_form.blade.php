@push('js')
    <script src="{{ asset('components/ckeditor4/ckeditor.js') }}"></script>
    <script src="{{ asset('components/ckeditor4/config-full.js') }}"></script>
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
    {!! TranslatableBootForm::checkbox(__('Published'), 'status') !!}
    {!! BootForm::select(__('Categories'), 'category_id', ['' => ''] + Categories::where('connection',1)->pluck('title', 'id')->all(), null, ['class' => 'form-control'])->addClass('custom-select') !!}
    {!! BootForm::text(__('Author'), 'author') !!} 
    {!! BootForm::text(__('Tags'), 'tags')->value(old('tags') ? : implode(',', $model->tags->pluck('tag')->all())) !!}

    <div class="row gx-3">
    <div class="col-sm-6">
        {!! BootForm::date(__('Date'), 'date')->value(old('date') ? : $model->present()->dateOrNow('date'))->addClass('datepicker') !!}
    </div>
</div>
</div>
{!! TranslatableBootForm::textarea(__('Summary'), 'summary')->rows(4) !!}
{!! TranslatableBootForm::textarea(__('Body'), 'body')->addClass('ckeditor-full') !!}
