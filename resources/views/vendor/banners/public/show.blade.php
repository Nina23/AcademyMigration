@extends('core::public.master')

@section('title', $model->title.' – '.__('Banners').' – '.$websiteTitle)
@section('ogTitle', $model->title)
@section('description', $model->summary)
@section('ogImage', $model->present()->image(1200, 630))
@section('bodyClass', 'body-banners body-banner-'.$model->id.' body-page body-page-'.$page->id)

@section('content')

<article class="banner">
    <header class="banner-header">
        <div class="banner-header-container">
            <div class="banner-header-navigator">
                @include('core::public._items-navigator', ['module' => 'Banners', 'model' => $model])
            </div>
            <h1 class="banner-title">{{ $model->title }}</h1>
        </div>
    </header>
    <div class="banner-body">
        @include('banners::public._json-ld', ['banner' => $model])
        @empty(!$model->summary)
        <p class="banner-summary">{!! nl2br($model->summary) !!}</p>
        @endempty
        @empty(!$model->image)
        <picture class="banner-picture">
            <img class="banner-picture-image" src="{{ $model->present()->image(2000, 1000) }}" width="{{ $model->image->width }}" height="{{ $model->image->height }}" alt="">
            @empty(!$model->image->description)
            <legend class="banner-picture-legend">{{ $model->image->description }}</legend>
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
