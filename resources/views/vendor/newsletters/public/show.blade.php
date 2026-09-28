@extends('core::public.master')

@section('title', $model->title.' – '.__('Newsletters').' – '.$websiteTitle)
@section('ogTitle', $model->title)
@section('description', $model->summary)
@section('ogImage', $model->present()->image(1200, 630))
@section('bodyClass', 'body-newsletters body-newsletter-'.$model->id.' body-page body-page-'.$page->id)

@section('content')

<article class="newsletter">
    <header class="newsletter-header">
        <div class="newsletter-header-container">
            <div class="newsletter-header-navigator">
                @include('core::public._items-navigator', ['module' => 'Newsletters', 'model' => $model])
            </div>
            <h1 class="newsletter-title">{{ $model->title }}</h1>
        </div>
    </header>
    <div class="newsletter-body">
        @include('newsletters::public._json-ld', ['newsletter' => $model])
        @empty(!$model->summary)
        <p class="newsletter-summary">{!! nl2br($model->summary) !!}</p>
        @endempty
        @empty(!$model->image)
        <picture class="newsletter-picture">
            <img class="newsletter-picture-image" src="{{ $model->present()->image(2000, 1000) }}" width="{{ $model->image->width }}" height="{{ $model->image->height }}" alt="">
            @empty(!$model->image->description)
            <legend class="newsletter-picture-legend">{{ $model->image->description }}</legend>
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
