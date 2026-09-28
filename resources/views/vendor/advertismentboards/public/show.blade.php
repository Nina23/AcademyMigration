@extends('core::public.master')

@section('title', $model->title.' – '.__('Advertisment boards').' – '.$websiteTitle)
@section('ogTitle', $model->title)
@section('description', $model->summary)
@section('ogImage', $model->present()->image(1200, 630))
@section('bodyClass', 'body-advertismentboards body-advertismentboard-'.$model->id.' body-page body-page-'.$page->id)

@section('content')
<!-- details-section - start
			================================================== -->
			<section id="details-section" class="details-section blog-details sec-ptb-100 clearfix">
				<div class="container">
                    <header class="advertismentboard-header mb-30">
                        <div class="advertismentboard-header-container">
                            <div class="advertismentboard-header-navigator">
                                @include('core::public._items-navigator', ['module' => 'Advertismentboards', 'model' => $model])
                            </div>
                        </div>
                    </header>
					<div class="details-content" data-aos="fade-up" data-aos-delay="100">
						<h2 class="item-title">{{ $model->title }}</h2>
						<div class="post-meta ul-li mb-60 clearfix">
                            <!-- Nina, ovde uvezati kategoriju, tag i datum objave -->
							<ul class="clearfix">
                                <li>{{$model->category->title}}</li>
								@foreach($model->tags->pluck('tag')->all() as $tag)
								<li>{{__($tag)}}</li>
								@endforeach
								<li>{{ $model->present()->dateLocalized }}</li>
							</ul>
						</div>
					</div>

					<div class="details-image h-auto mb-60" data-aos="fade-up" data-aos-delay="300">
                        @empty(!$model->image)
                            <img class="advertismentboard-picture-image" src="{{ $model->present()->image(2000, 1000) }}" width="{{ $model->image->width }}" height="{{ $model->image->height }}" alt="{{ $model->title }}">       
                        @endempty
                    </div>

					<div class="details-content mb-100">
						<!-- <div class="blog-share ul-li mb-60 clearfix" data-aos="fade-up" data-aos-delay="100">
							<ul class="clearfix">
								<li>
									<div class="post-counter">
										<strong>221</strong>
										<span>SHARES</span>
									</div>
								</li>
								<li>
									<a href="#!" class="bg-facebook">
										<span><i class="fab fa-facebook-f"></i></span>
										<small>SHARE POST</small>
									</a>
								</li>
								<li>
									<a href="#!" class="bg-twitter">
										<span><i class="fab fa-twitter"></i></span>
										<small>TWEET POST</small>
									</a>
								</li>
								<li>
									<a href="#!" class="bg-googleplus">
										<span><i class="fab fa-google-plus-g"></i></span>
										<small>SHARE POST</small>
									</a>
								</li>
							</ul>
                        </div> -->
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
            <div class="advertismentboard-footer">
                <div class="advertismentboard-header-container">
                    <div class="advertismentboard-header-navigator">
                        @include('core::public._items-navigator', ['module' => 'Advertismentboards', 'model' => $model])
                    </div>
                </div>
            </div>



@endsection
