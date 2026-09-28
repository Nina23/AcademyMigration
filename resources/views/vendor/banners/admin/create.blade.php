@extends('core::admin.master')

@section('title', __('New banner'))

@section('content')

    <div class="header">
        @include('core::admin._button-back', ['module' => 'banners'])
        <h1 class="header-title">@lang('New banner')</h1>
    </div>

    {!! BootForm::open()->action(route('admin::index-banners'))->multipart()->role('form') !!}
        @include('banners::admin._form')
    {!! BootForm::close() !!}

@endsection
