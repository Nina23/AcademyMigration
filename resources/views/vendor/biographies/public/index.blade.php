@extends('pages::public.master')

@section('bodyClass', 'body-biographies body-biographies-index body-page body-page-'.$page->id)

@section('page')

<div class="page-body">

    <div class="">

	<!-- header-section - start
			================================================== -->
		<div class="agency-creative-banner align-items-center banner-section bg-default-red clearfix d-flex text-white" id="banner-section">
			<div class="container">
				<div class="align-items-center justify-content-lg-between justify-content-lg-start row">
					<div class="col-8 col-lg-8 col-md-8 col-sm-8">
						<div class="banner-content">
						<h1>{{$category->title}}</h1>

						<h4>{{__('Akademija umjetnosti Univerziteta u Banjoj Luci')}}</h4>
						</div>
					</div>
				</div>
			</div>
			<div class="absolute-social-wrap pt-0 sec-ptb-60">
				<div class="container">
					<div class="clearfix social-links-text ul-li-right" data-aos="fade-left" data-aos-delay="100">
						<ul>
							<li><a href="https://www.facebook.com/aubl.org/" target="_blank">facebook</a></li>
							<li><a href="https://www.youtube.com/user/AkademijaUmjetnosti" target="_blank">youtube</a></li>
							<li><a href="https://www.instagram.com/akademija_umjetnosti_banjaluka/" target="_blank">instagram</a></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<!-- header-section - end
					================================================== -->
					@empty(!$page->body)
        <div class="rich-content">{!! $page->present()->body !!}</div>
        @endempty


       
        @if ($page->publishedSections->count() > 0)
        <div class="page-sections">
            @foreach ($page->publishedSections as $section)
            <div class="page-section" id="{{ $section->slug.'-'.$section->id }}">
                <div class="rich-content">{!! $section->present()->body !!}</div>
            </div>
            @endforeach
        </div>
        @endif

@foreach($biographies as $biography)

@if($biography->isDirectoryDepartments())
		<!--
			================================================== -->
		<div class="about-section clearfix" id="about-section">
			<div class="container">
				<div class="align-items-center justify-content-lg-between justify-content-md-between justify-content-sm-center row">
					<div class="col-lg-4 col-md-4 col-sm-5 col-xs-12">
						<div class="about-image creative-image" data-aos="fade-up" data-aos-delay="100">
							@if($biography->image)
							<img class="" src="{{ $biography->present()->image(1000, 1200) }}"  width="{{ $biography->image->width }}" height="{{ $biography->image->height }}"  alt="" />
							@else
							<img class="news-list-item-image" src="/images/academy/image-placeholder.jpg"  alt="{{ $biography->title }}"/>
							@endif
						</div>
					</div>

					<div class="col-lg-8 col-md-7 col-sm-7 col-xs-12">
						<div class="about-content" data-aos="fade-up" data-aos-delay="300">
							<div class="child-item" data-aos="fade-up" data-aos-delay="100">
								<h6>{{__('program-director')}}</h6>

								<div class="person-name">
									<h5>{{$biography->title}}</h5>
								</div>
								<p class="item-brand">{{$biography->department->title}}</p>

								<div class="contact-item"><img alt="Dekan email" src="/images/academy/icons/email.svg" /> <a href="mailto: radoslav.tadic@au.unibl.org">{{$biography->email}} </a></div>
									<a class="details-btn" href="{{ $biography->uri() }}" title="Dragana Purković Macan">{{__('Biography')}}<i class="fas fa-long-arrow-alt-right"></i></a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			@endif
@endforeach
@foreach($departments as $department)

        @include('biographies::public._itemlist-json-ld', ['items' => $department->biographies()->get()])
        <div id="portfolio-section" class="portfolio-section clearfix  mt-60">
			
            <div class="container">
				<h3 class="item-title mb-30">
					{{$department->title}}
				</h3>
				<div class="grid row mb-60">
                    @includeWhen($department->biographies()->count() > 0, 'biographies::public._list', ['items' => $department->biographies()->get()])
                </div>
            </div>
        </div>
		@endforeach
		

    </div> 

</div>

@endsection
