@extends('pages::public.master')

@section('page')

<div class="page-body">
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

     

        <!-- contact-section - start
			================================================== -->
			<section id="contact-section" class="contact-section sec-ptb-100 clearfix">
				<div class="container">

					<div class="row justify-content-center mb-60">
						<div class="col-lg-6 col-md-7 col-sm-9" data-aos="fade-up" data-aos-delay="100">
							<div class="section-title text-center size-increase">
								<h2 class="title-text mb-0">{{__('Contact')}}</h2>
							</div>
						</div>
                    </div>
                    
                    <div class="align-items-center justify-content-lg-between row">
                       
                        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 order-last">
                            <div class="float-left item-image" data-aos="fade-up" data-aos-delay="100" style="width:100%;">
                                <div class="map-section clearfix" id="map-section">
                                    <div data-info="Bulevar vojvode Petra Bojovića 1a, Banja Luka, Republika Srpska 78000, Bosna i Hercegovina" data-lat="44.7742" data-lon="17.2137" data-mlat="44.7742" data-mlon="17.2137" data-zoom="17" id="mapBox">&nbsp;</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5 col-md-6 col-sm-12 col-xs-12">
                            <div class="section-title" data-aos="fade-up" data-aos-delay="300">
                                <h3>{{__('Contact-form')}}</h3>

                                {!! BootForm::open()->action(route('contact-admin'))->multipart()->role('form') !!}
								<div class="form-item">
                                {!! BootForm::text(__('Name'), 'name') !!}
                                </div>
                                <div class="form-item">
                                {!! BootForm::text(__('Email'), 'email') !!}
                                </div>
                                <div class="form-item">
                                {!! BootForm::textarea(__('Subject'), 'subject') !!}
                                </div>
                                <button type="submit" class="btn" >{{__('Send')}}</button>
                                {!! BootForm::close() !!}   
                            </div>
                        </div>
                        
				</div>
			</section>
			<!-- contact-section - end
			================================================== -->

    <!-- <div class="">

        <div class="bg-default-light pb-0 sec-ptb-100">
            <div class="clearfix pt-0 sec-ptb-60">
                <div class="container">
                    <div class="mb-60 section-title" data-aos="fade-up" data-aos-delay="100">
                        <h2>{{__('Contact')}}</h2>
                    </div>

                    <div class="row" data-aos="fade-up" data-aos-delay="300" >
                        {!! BootForm::open()->action(route('contact-admin'))->multipart()->role('form') !!}
                        <div class="row">
                            <div class="col-lg-12 col-md-8 col-sm-8 col-xs-12">
                                <div class="form-item">
                                {!! BootForm::text(__('Name'), 'name') !!}
                                {!! BootForm::text(__('Email'), 'email') !!}
                                {!! BootForm::textarea(__('Subject'), 'subject') !!}
                            </div>

                            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                <div class="form-item"><button class="btn" type="submit">{{__('contact')}}</button></div>
                            </div>
                        </div>
                        
                        {!! BootForm::close() !!}
                    </div>
                </div>
            </div>
        </div> 

    </div> -->

</div>

@endsection



