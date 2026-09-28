@extends('core::public.master')

@section('title', $model->title.' – '.__('Classschedules').' – '.$websiteTitle)
@section('ogTitle', $model->title)
@section('description', $model->summary)
@section('ogImage', $model->present()->image(1200, 630))
@section('bodyClass', 'body-classschedules body-classschedule-'.$model->id.' body-page body-page-'.$page->id)

@section('content')

<article class="classschedule">
    <header class="classschedule-header">
        <div class="classschedule-header-container">
            <div class="classschedule-header-navigator">
                @include('core::public._items-navigator', ['module' => 'Classschedules', 'model' => $model])
            </div>
            <h1 class="classschedule-title">{{ $model->title }}</h1>
        </div>
    </header>
    <div class="classschedule-body">
        @include('classschedules::public._json-ld', ['classschedule' => $model])
        @empty(!$model->summary)
        <p class="classschedule-summary">{!! nl2br($model->summary) !!}</p>
        @endempty
        @empty(!$model->image)
        <picture class="classschedule-picture">
            <img class="classschedule-picture-image" src="{{ $model->present()->image(2000, 1000) }}" width="{{ $model->image->width }}" height="{{ $model->image->height }}" alt="">
            @empty(!$model->image->description)
            <legend class="classschedule-picture-legend">{{ $model->image->description }}</legend>
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
