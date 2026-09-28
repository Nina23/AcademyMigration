@extends('core::admin.master')

@section('title', __('New gallery'))

@section('content')

    <div class="header">
        @include('core::admin._button-back', ['module' => 'galleries'])
        <h1 class="header-title">@lang('New gallery')</h1>
    </div>

    {!! BootForm::open()->action(route('admin::index-galleries'))->multipart()->role('form') !!}
        @include('galleries::admin._form')
    {!! BootForm::close() !!}

@endsection
