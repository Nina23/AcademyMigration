@extends('core::admin.master')

@section('title', __('New announcement'))

@section('content')

    <div class="header">
        @include('core::admin._button-back', ['module' => 'announcements'])
        <h1 class="header-title">@lang('New announcement')</h1>
    </div>

    {!! BootForm::open()->action(route('admin::index-announcements'))->multipart()->role('form') !!}
        @include('announcements::admin._form')
    {!! BootForm::close() !!}

@endsection
