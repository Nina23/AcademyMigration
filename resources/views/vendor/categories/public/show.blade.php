@extends('core::public.master')

@section('title', $model->title.' – '.__('Categories').' – '.$websiteTitle)
@section('ogTitle', $model->title)
@section('description', $model->summary)
@section('ogImage', $model->present()->image(1200, 630))
@section('bodyClass', 'body-categories body-category-'.$model->id.' body-page body-page-'.$page->id)

@section('content')

<article class="category">
    <header class="category-header">
        <div class="category-header-container">
            <div class="category-header-navigator">
                @include('core::public._items-navigator', ['module' => 'Categories', 'model' => $model])
            </div>
            <h1 class="category-title">{{ $model->title }}</h1>
        </div>
    </header>
    <div class="category-body">
        @include('categories::public._json-ld', ['category' => $model])
        @empty(!$model->summary)
        <p class="category-summary">{!! nl2br($model->summary) !!}</p>
        @endempty
        @empty(!$model->image)
        <picture class="category-picture">
            <img class="category-picture-image" src="{{ $model->present()->image(2000, 1000) }}" width="{{ $model->image->width }}" height="{{ $model->image->height }}" alt="">
            @empty(!$model->image->description)
            <legend class="category-picture-legend">{{ $model->image->description }}</legend>
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
