@extends('pages::public.master')

@section('page')

<div class="page-body">

    <div class="">

    <!-- SUbpafes navigation, check Nina -->

        <!-- @if ($children->count() > 0)
        <ul class="nav nav-subpages">
            @foreach ($children as $child)
            @include('pages::public._list-item', ['child' => $child])
            @endforeach
        </ul>
        @endif -->

        <!-- @empty(!$page->image)
            <img class="page-image" src="{!! $page->present()->image(200, 200) !!}" alt="">
        @endempty -->

        @empty(!$page->body)
        <div class="rich-content">{!! $page->present()->body !!}</div>
        @endempty


       
        @if ($page->publishedSections->count() > 0)
        <div class="page-sections">
            @foreach ($page->publishedSections as $section)
            <div class="page-section" id="{{ $section->slug.'-'.$section->id }}">
                <!-- <h2 class="page-section-title">{{ $section->title }}</h2> -->
                <div class="rich-content">{!! $section->present()->body !!}</div>
            </div>
            @endforeach
        </div>
        @endif


        @include('files::public._documents', ['model' => $page])
        @include('files::public._images', ['model' => $page])

         <!-- oglasi-section - start
			================================================== -->
        <div class="blog-section bg-gray clearfix hotel-section sec-ptb-100" style="display:none" id="highlighted-news">
                <div class="container">
                    <div class="mb-30 section-title text-center" data-aos="fade-up" data-aos-delay="100">
                        <h2>{{__('News')}}</h2>

                        <p>{{__('events-academy')}}</p>
                    </div>

                    <div class="row">

                        @foreach($highlightNews as $news)
                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                            <div class="blog-grid clearfix" data-aos="fade-up" data-aos-delay="300">
                            @if($news->image)
           						<img class="news-list-item-image" src="{{ $news->present()->image(540, 400) }}" alt="{{ $news->title }}">
                             @else
                                <img class="news-list-item-image" src="/images/academy/image-placeholder.jpg"  alt="{{ $news->title }}">

                            @endif
                            <div class="mt-30">
									<h3 class="item-title">
										<a href="{{ $news->uri() }}">
                                        {{ $news->title }}
										</a>
									</h3>
									<div class="post-meta ul-li mb-30 clearfix">
										<ul class="clearfix">
											<li>{{ $news->present()->dateLocalized }}</li>
										</ul>
									</div>
								</div>
                               
                            </div>
                        </div> 
                        @endforeach

                    </div>
                </div>
            </div> 
            <!-- oglasi-section - end
			================================================== -->


             <div class="clearfix partners-section sec-ptb-100" id="partners-section" style="display:none">
                <div class="container" data-aos="fade-up" data-aos-delay="100">
                    <div class="mb-60 section-title text-center" data-aos="fade-up" data-aos-delay="100">
                        <h2>@lang('Partners title')</h2>

                        <p>@lang('All partners')</p>
                    </div>

                    <div class="arrow-left-right owl-carousel owl-theme partners-carousel" id="partners-carousel">

                    @foreach($banners as $banner)
                        <div class="item">
                        <a class="partner-logo" href="{{$banner->link}}" target="_blank">
                        @if($banner->image)
                        <img alt="{{ $banner->title }}" src="{{ $banner->present()->image(540, 400) }}" />
                        @else
                        <img alt="{{ $banner->title }}" src="/images/academy/Partners/AEC-Logo-transparent.gif" /> 
                        @endif
                        </a>
                        </div>
                       
                    @endforeach
                    
                    </div>
                 </div>
            </div>
<!-- partners-section - end
			================================================== -->

            <!-- newsletter-section - start
			================================================== -->
<!-- <div class="bg-default-light pb-0 sec-ptb-100">
    <div class="clearfix pt-0 sec-ptb-60">
        <div class="container">
            <div class="mb-60 section-title" data-aos="fade-up" data-aos-delay="100">
                <h2>{{__('want-newsletter')}}</h2>

                <p>{{__('insert-email')}}</p>
            </div>

            <div class="row" data-aos="fade-up" data-aos-delay="300" >
            {!! BootForm::open()->action(route('store-newsletter'))->multipart()->role('form') !!}
                <div class="row">
                    <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">
                        <div class="form-item">
                        {!! BootForm::text(__('Email'), 'email') !!}
                        {!! BootForm::text(__('Name'), 'name') !!}
                        {!! BootForm::hidden('type')->value(0) !!}
                    </div>

                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                        <div class="form-item"><button class="btn" type="submit">{{__('login')}}</button></div>
                    </div>
                </div>
                
                {!! BootForm::close() !!}
            </div>
        </div>
    </div>
</div> -->
<!--  newsletter-section - end


    </div>

  

</div>-->

@endsection

