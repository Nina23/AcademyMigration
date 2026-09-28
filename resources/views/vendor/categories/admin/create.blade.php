@extends('core::admin.master')

@section('title', __('New category'))

@section('content')

    <div class="header">
        @include('core::admin._button-back', ['module' => 'categories'])
        <h1 class="header-title">@lang('New category')</h1>
    </div>

    {!! BootForm::open()->action(route('admin::index-categories'))->multipart()->role('form') !!}
        @include('categories::admin._form')
    {!! BootForm::close() !!}

@endsection
