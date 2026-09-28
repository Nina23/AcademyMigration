@extends('core::public.master')

@section('title', $model->title.' – '.__('Galleries').' – '.$websiteTitle)
@section('ogTitle', $model->title)
@section('description', $model->summary)
@section('ogImage', $model->present()->image(1200, 630))
@section('bodyClass', 'body-galleries body-gallery-'.$model->id.' body-page body-page-'.$page->id)

@section('content')

<article class="gallery">
    <header class="gallery-header">
        <div class="gallery-header-container">
            <div class="gallery-header-navigator">
                @include('core::public._items-navigator', ['module' => 'Galleries', 'model' => $model])
            </div>
            <h1 class="gallery-title">{{ $model->title }}</h1>
        </div>
    </header>
    <div class="gallery-body">
        @include('galleries::public._json-ld', ['gallery' => $model])
        @empty(!$model->summary)
        <p class="gallery-summary">{!! nl2br($model->summary) !!}</p>
        @endempty
        @empty(!$model->image)
        <picture class="gallery-picture">
            <img class="gallery-picture-image" src="{{ $model->present()->image(2000, 1000) }}" width="{{ $model->image->width }}" height="{{ $model->image->height }}" alt="">
            @empty(!$model->image->description)
            <legend class="gallery-picture-legend">{{ $model->image->description }}</legend>
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
