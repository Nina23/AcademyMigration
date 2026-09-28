@extends('core::admin.master')

@section('title', $model->present()->title)

@section('content')

    <div class="header">
        @include('core::admin._button-back', ['module' => 'classschedules'])
        <h1 class="header-title @if (!$model->present()->title)text-muted @endif">
            {{ $model->present()->title ?: __('Untitled') }}
        </h1>
    </div>

    {!! BootForm::open()->put()->action(route('admin::update-classschedule', $model->id))->multipart()->role('form') !!}
    {!! BootForm::bind($model) !!}
        @include('classschedules::admin._form')
    {!! BootForm::close() !!}

    @include('classschedules::admin.days_tabs')

@endsection
