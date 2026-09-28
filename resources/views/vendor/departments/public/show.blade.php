@extends('core::public.master')

@section('title', $model->title.' – '.__('Departments').' – '.$websiteTitle)
@section('ogTitle', $model->title)
@section('description', $model->summary)
@section('ogImage', $model->present()->image(1200, 630))
@section('bodyClass', 'body-departments body-department-'.$model->id.' body-page body-page-'.$page->id)

@section('content')

<article class="department">
    <header class="department-header">
        <div class="department-header-container">
            <div class="department-header-navigator">
                @include('core::public._items-navigator', ['module' => 'Departments', 'model' => $model])
            </div>
            <h1 class="department-title">{{ $model->title }}</h1>
        </div>
    </header>
    <div class="department-body">
        @include('departments::public._json-ld', ['department' => $model])
        @empty(!$model->summary)
        <p class="department-summary">{!! nl2br($model->summary) !!}</p>
        @endempty
        @empty(!$model->image)
        <picture class="department-picture">
            <img class="department-picture-image" src="{{ $model->present()->image(2000, 1000) }}" width="{{ $model->image->width }}" height="{{ $model->image->height }}" alt="">
            @empty(!$model->image->description)
            <legend class="department-picture-legend">{{ $model->image->description }}</legend>
            @endempty
        </picture>
        @endempty
        @empty(!$model->body)
        <div class="rich-content">{!! $model->present()->body !!}</div>
        @endempty
        @include('files::public._documents')
        @include('files::public._images')
    </div>
</article>

@endsection
