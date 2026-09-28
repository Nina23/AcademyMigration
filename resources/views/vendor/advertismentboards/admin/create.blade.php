@extends('core::admin.master')

@section('title', __('New advertismentboard'))

@section('content')

    <div class="header">
        @include('core::admin._button-back', ['module' => 'advertismentboards'])
        <h1 class="header-title">@lang('New advertisment')</h1>
    </div>

    {!! BootForm::open()->action(route('admin::index-advertismentboards'))->multipart()->role('form') !!}
        @include('advertismentboards::admin._form')
    {!! BootForm::close() !!}

@endsection
