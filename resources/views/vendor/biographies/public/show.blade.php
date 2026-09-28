@extends('core::public.master')

@section('title', $model->title.' – '.__('Biographies').' – '.$websiteTitle)
@section('ogTitle', $model->title)
@section('description', $model->summary)
@section('ogImage', $model->present()->image(1200, 630))
@section('bodyClass', 'body-biographies body-biography-'.$model->id.' body-page body-page-'.$page->id)

@section('content')

<article class="biography">
    <!-- breadcrumb-section - start
			================================================== -->
			<div id="breadcrumb-section" class="breadcrumb-section sec-ptb-100 pb-0 clearfix">
				<div class="container">
					<div class="row align-items-center justify-content-lg-between justify-content-md-between justify-content-sm-center">
						<div class="col-lg-8 col-md-8 col-sm-7 col-xs-12">
                            <!-- Nina, ovdje ide kategorija  -->
                            <h2 class="page-title mb-0" data-aos="fade-right" data-aos-delay="100">{{ $model->department->title }}</h2>
                            
						</div>

						<div class="col-lg-4 col-md-4 col-sm-7 col-xs-12">
							<div class="breadcrumb-nev ul-li-right clearfix" data-aos="fade-left" data-aos-delay="300">
								<ul class="clearfix">
									<li>{{__('academy')}}</li>
									<li>{{__('teachers')}}</li>
									<li>{{__('Biography')}}</li>
                                </ul>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- breadcrumb-section - end
            ================================================== -->
    <div class="biography-body">

    @include('biographies::public._json-ld', ['biography' => $model])

    <!-- about-section - start
			================================================== -->
			<div id="about-section" class="about-section sec-ptb-100 clearfix">
				<div class="container">
					<div class="row align-items-center justify-content-lg-between justify-content-md-center justify-content-sm-center">

						<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
							<div class="about-image" data-aos="fade-up" data-aos-delay="100">
                            @empty(!$model->image)
                                <img src="{{ $model->present()->image(1000, 1200) }}" width="{{ $model->image->width }}" height="{{ $model->image->height }}" alt="">
                                @empty(!$model->image->description)
                                <legend class="biography-picture-legend">{{ $model->image->description }}</legend>
                                @endempty
                            @endempty
								
							</div>
						</div>

						<div class="col-lg-7 col-md-7 col-sm-7 col-xs-12">
							<div class="about-content" data-aos="fade-up" data-aos-delay="300">
								<h5>{{ $model->title }}</h5>
								<p class="item-brand">{{ $model->name }}</p>
                               
<!-- Nina, ovdje uvezujes i prikazujes samoono sto je uneseno u adminu. Telefon i email prikazujes i u href linku i kao ispis, a ostale samo kao href link, a uz ikonicu staticki neka pise, Instagram, LinkedIn... -->
								<div class="bottom-line">
									@if($model->email)
                                    <div class="contact-item"><img alt="Professor email" src="/images/academy/icons/email.svg" /> <a href="mailto:{{ $model->email }}"> {{ $model->email }} </a></div>
									@endif
										@if($model->phone)
                                    <div class="contact-item mb-30"><img alt="Professor broj telefona" src="/images/academy/icons/phone.svg" /> <a href="tel:+38751348800"> {{ $model->phone }} </a></div>
									@endif
<!-- Nian, postavi uslove da se ne prikazuju ikonice ako nema linka -->
                                    <div class="mt-60 social-links ul-li clearfix aos-init aos-animate" data-aos="fade-up" data-aos-delay="300">
                                        <ul class="clearfix">
											@if($model->external_link)
                                            <li><a href="{{ $model->external_link }}" target="_blank"><i class="fas fa-external-link-alt"></i></a></li>
											@endif
											@if($model->linkedin)
											<li><a href="{{ $model->linkedin }}" target="_blank"><i class="fab fa-linkedin"></i></a></li>
											@endif
											@if($model->instagram)
											<li><a href="{{ $model->instagram }}" target="_blank"><i class="fab fa-instagram"></i></a></li>
											@endif
											@if($model->imdb)
											<li><a href="{{ $model->imdb }}" target="_blank"><i class="fab fa-imdb"></i></a></li>
											@endif
                                        </ul>
                                    </div>
							</div>

							<p class=" aos-init aos-animate" data-aos="fade-up" data-aos-delay="200"> {!! nl2br($model->summary) !!} </p>
						</div>
					</div>
				</div>
			</div>
			<!-- about-section - end
            ================================================== -->

            <!-- profesionalno iskustvo - start
			================================================== -->
			@if($model->section)
			<div id="details-section" class="details-section clearfix">
				<div class="details-content sec-ptb-60">
					<div class="container">
						<div class="row">
							<div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
								<h2 class="item-title align-right" data-aos="fade-up" data-aos-delay="100">{{__('experience')}}</h2>
								<div class="info-list ul-li-block clearfix">
                                    </div>
							</div>

							<div class="col-lg-7 col-md-7 col-sm-12 col-xs-12">
								<div data-aos="fade-up" data-aos-delay="200">
								{!! $model->section !!}
                                </div>
							</div>
						</div>
					</div>
				</div>
			</div>
			@endif
			<!-- details-section - end
            ================================================== -->
            
              <!-- {nagrade} iskustvo - start
			================================================== -->
			@if($model->awards)
			<div id="details-section" class="details-section clearfix">
				<div class="details-content sec-ptb-60">
					<div class="container">
						<div class="row">
							<div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
								<h2 class="item-title align-right" data-aos="fade-up" data-aos-delay="100">{{__('awards')}}</h2>
								<div class="info-list ul-li-block clearfix">
                                    </div>
							</div>

							<div class="col-lg-7 col-md-7 col-sm-12 col-xs-12">
								<div data-aos="fade-up" data-aos-delay="200">
									{!! $model->awards !!}
                                </div>
							</div>
						</div>
					</div>
				</div>
			</div>
			@endif
			<!-- details-section - end
			================================================== -->
			
            
              <!-- publikacije iskustvo - start
			================================================== -->
			@if($model->publications)
			<div id="details-section" class="details-section clearfix">
				<div class="details-content sec-ptb-60">
					<div class="container">
						<div class="row">
							<div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
								<h2 class="item-title align-right" data-aos="fade-up" data-aos-delay="100">{{__('publications')}}</h2>
								<div class="info-list ul-li-block clearfix">
                                    </div>
							</div>

							<div class="col-lg-7 col-md-7 col-sm-12 col-xs-12">
								<div data-aos="fade-up" data-aos-delay="200">
								{!! $model->publications !!}
									<!-- Nina ovde uvezati Section -->
                                   
                                </div>
							</div>
						</div>
					</div>
				</div>
			</div>
			@endif
			<!-- details-section - end
			================================================== -->
        
        
        @empty(!$model->body)
        <div class="rich-content">{!! $model->present()->body !!}</div>
        @endempty

         <!-- galerija fotografije- start
			================================================== -->
			<div id="details-section" class="details-section clearfix">
				<div class="details-content sec-ptb-60">
                    @include('files::public._images')
				</div>
			</div>
			<!-- details-section - end
            ================================================== -->
            
             <!-- galerija dokumenti - start
			================================================== -->
			<div id="details-section" class="details-section clearfix">
				<div class="details-content sec-ptb-60">
                    @include('files::public._documents')
				</div>
            </div>
			<!-- details-section - end
			================================================== -->
    </div>
</article>

@endsection
