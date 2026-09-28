@push('js')
    <script src="{{ asset('components/ckeditor4/ckeditor.js') }}"></script>
    <script src="{{ asset('components/ckeditor4/config-full.js') }}"></script>
@endpush

@component('core::admin._buttons-form', ['model' => $model])
@endcomponent

{!! BootForm::hidden('id') !!}


<div class="form-group">
    @include('core::form._title-and-slug')
    {!! TranslatableBootForm::hidden('status')->value(0) !!}
    {!! TranslatableBootForm::checkbox(__('Published'), 'status') !!}
</div>

{!! BootForm::select(__('Departments'), 'announcement_department_id', $departments, null, ['class' => 'form-control'])->addClass('custom-select') !!}
<input name="announcement_category_id" id='announcement_category_id' type="hidden" value="1">

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


{!! TranslatableBootForm::textarea(__('Summary'), 'summary')->rows(4) !!}


