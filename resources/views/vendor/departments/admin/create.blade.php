@extends('core::admin.master')

@section('title', __('New department'))

@section('content')

    <div class="header">
        @include('core::admin._button-back', ['module' => 'departments'])
        <h1 class="header-title">@lang('New department')</h1>
    </div>

    {!! BootForm::open()->action(route('admin::index-departments'))->multipart()->role('form') !!}
        @include('departments::admin._form')
    {!! BootForm::close() !!}

@endsection
