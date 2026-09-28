@extends('core::admin.master')

@section('title', __('New newsletter'))

@section('content')

    <div class="header">
        @include('core::admin._button-back', ['module' => 'newsletters'])
        <h1 class="header-title">@lang('New newsletter')</h1>
    </div>

    {!! BootForm::open()->action(route('admin::index-newsletters'))->multipart()->role('form') !!}
        @include('newsletters::admin._form')
    {!! BootForm::close() !!}

@endsection
