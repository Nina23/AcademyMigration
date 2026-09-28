@extends('core::public.master')

@section('title', $model->title.' – '.__('Announcements').' – '.$websiteTitle)
@section('ogTitle', $model->title)
@section('description', $model->summary)
@section('ogImage', $model->present()->image(1200, 630))
@section('bodyClass', 'body-announcements body-announcement-'.$model->id.' body-page body-page-'.$page->id)

@section('content')

<!-- details-section - start
			================================================== -->
<section id="details-section" class="details-section blog-details sec-ptb-100 clearfix">
    <div class="container">
        <header class="advertismentboard-header mb-30">
            <div class="advertismentboard-header-container">
                <div class="advertismentboard-header-navigator">
                    @include('core::public._items-navigator', ['module' => 'Announcements', 'model' => $model])
                </div>
            </div>
        </header>
        <div class="details-content" data-aos="fade-up" data-aos-delay="100">
            <h2 class="item-title">{{ $model->title }}</h2>
            <div class="post-meta ul-li mb-60 clearfix filter-group">
                @foreach($model->formatted_program_output as $program)
                <img alt="{{$program}}" src="/images/academy/icons/{{$program}}.svg">
                @endforeach
                <strong>{{$model->announcementCategory->title}}</strong> |
                <span>{{$model->present()->dateLocalized}}</span>

                <div class="post-meta ul-li clearfix">
                    <ul class="clearfix">
                        @foreach($model->tags->pluck('tag')->all() as $tag)
                        <li><span>{{__($tag)}}</span></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="details-image h-auto mb-60" data-aos="fade-up" data-aos-delay="300">
            @empty(!$model->image)
            <img class="announcement-picture-image" src="{{ $model->present()->image(2000, 1000) }}" width="{{ $model->image->width }}" height="{{ $model->image->height }}" alt="">
            @empty(!$model->image->description)
            <legend class="announcement-picture-legend">{{ $model->image->description }}</legend>
            @endempty
            @endempty
        </div>

        <div class="details-content mb-100">

            <div class="advertismentboard-body">
                @include('advertismentboards::public._json-ld', ['advertismentboard' => $model])
                @empty(!$model->body)
                <div class="rich-content">{!! $model->present()->body !!}</div>

                @endempty
                @include('files::public._documents')
                @include('files::public._images')
            </div>
        </div>
    </div>

</section>
<!-- details-section - end
            ================================================== -->







@endsection