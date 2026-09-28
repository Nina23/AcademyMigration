@push('js')
    <script src="{{ asset('components/ckeditor4/ckeditor.js') }}"></script>
    <script src="{{ asset('components/ckeditor4/config-full.js') }}"></script>
@endpush

@component('core::admin._buttons-form', ['model' => $model])
@endcomponent

{!! BootForm::hidden('id') !!}

{!! BootForm::text(__('Email'), 'email') !!}
{!! BootForm::text(__('Name'), 'name') !!}
{!! BootForm::hidden('type')->value(1) !!}
