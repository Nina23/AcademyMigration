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

{!! BootForm::select(__('Announcement categories'), 'announcement_category_id', $categories, null, ['class' => 'form-control'])->addClass('custom-select') !!}


@include('core::form._title-and-slug')
<div class="form-group">
    {!! TranslatableBootForm::hidden('status')->value(0) !!}
    {!! TranslatableBootForm::checkbox(__('Published'), 'status') !!}
</div>

<div class="widget product-size" data-aos="fade-up" data-aos-delay="100">
    <label>{{__('Izaberi program')}}</label>

    <div class="size-btns-group ul-li clearfix">

        <ul class="clearfix">
            <li> <input name="program[]" type="checkbox" class="form-check-input" value="0" {{ (($model->program && in_array('0',$model->program)) ? 'checked' : '')}}>{{__('fine-arts')}}
            </li>
            <li> <input name="program[]" type="checkbox" class="form-check-input" value="1" {{ (($model->program && in_array('1',$model->program)) ? 'checked' : '')}}>{{__('music-art')}}
            </li>
            <li>
            <input name="program[]" type="checkbox" class="form-check-input" value="2" {{ (($model->program && in_array('2',$model->program)) ? 'checked' : '')}}>{{__('dramatic-arts')}}
            </li>
        </ul>
    </div>
</div>

<div class="widget product-size" data-aos="fade-up" data-aos-delay="100">
    <label>{{__('Izaberi godinu')}}</label>

    <div class="size-btns-group ul-li clearfix">

        <ul class="clearfix">
            {!!BootForm::radio( __('I godina'),'year', '1')!!}
            {!!BootForm::radio( __('II godina'),'year', '2')!!}
            {!!BootForm::radio(__('III godina'),'year',  '3')!!}
            {!!BootForm::radio(__('IV godina'),'year',  '4')!!}
            {!!BootForm::radio(__('Master studiji'),'year',  '5')!!}
            {!!BootForm::radio(__('Doktorski studiji'),'year',  '6')!!}
        </ul>
    </div>
</div>

{!! BootForm::text(__('Tags'), 'tags')->value(old('tags') ? : implode(',', $model->tags->pluck('tag')->all())) !!}


<div class="col-sm-6">
    {!! BootForm::dateTimeLocal(__('Date'), 'date')->value(old('date') ? : $model->present()->dateTimeOrNow('date'))->addClass('datetimepicker') !!}
</div>
<div class="col-sm-6">
    {!! TranslatableBootForm::hidden('highlight')->value(0) !!}
    {!! TranslatableBootForm::checkbox(__('Highlight'), 'highlight') !!}
</div>

<div class="col-sm-6">
    {!! BootForm::dateTimeLocal(__('Expiry'), 'expiry')->value(old('expiry') ? : $model->present()->dateTimeOrNow('expiry'))->addClass('datetimepicker') !!}
</div>
{!! TranslatableBootForm::textarea(__('Summary'), 'summary')->rows(4) !!}
{!! TranslatableBootForm::textarea(__('Body'), 'body')->addClass('ckeditor-full') !!}
