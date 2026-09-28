@extends('core::admin.master')

@section('title', __('New biography'))

@section('content')

    <div class="header">
        @include('core::admin._button-back', ['module' => 'biographies'])
        <h1 class="header-title">@lang('New biography')</h1>
    </div>

    {!! BootForm::open()->action(route('admin::index-biographies'))->multipart()->role('form') !!}
        @include('biographies::admin._form')
    {!! BootForm::close() !!}

@endsection
