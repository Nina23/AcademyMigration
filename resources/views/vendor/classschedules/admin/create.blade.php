@extends('core::admin.master')

@section('title', __('New classschedule'))

@section('content')

    <div class="header">
        @include('core::admin._button-back', ['module' => 'classschedules'])
        <h1 class="header-title">@lang('New classschedule')</h1>
    </div>

    {!! BootForm::open()->action(route('admin::index-classschedules'))->multipart()->role('form') !!}
        @include('classschedules::admin._form')
    {!! BootForm::close() !!}

@endsection
