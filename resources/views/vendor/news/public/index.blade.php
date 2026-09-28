@extends('pages::public.master')

@section('bodyClass', 'body-news body-news-index body-page body-page-'.$page->id)

@section('page')

<div class="page-body">
    <!-- banner-section - start
			================================================== -->
<div class="agency-creative-banner align-items-center banner-section bg-default-red clearfix d-flex text-white" id="banner-section">
    <div class="container">
        <div class="align-items-center justify-content-lg-between justify-content-lg-start row">
            <div class="col-8 col-lg-8 col-md-8 col-sm-8">
                <div class="banner-content">
                    <h1>{{__('News-title')}}</h1>

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
<!-- banner-section - end
			================================================== -->

    <div class="page-body-container">

        @include('news::public._itemlist-json-ld', ['items' => $models])
        

    <!-- blog-section - start
			================================================== -->
			<section id="blog-section" class="blog-section sec-ptb-100 clearfix">
				<div class="container">
					<div class="row justify-content-lg-between justify-content-md-center justify-content-sm-center">
						

						<div class="col-lg-3 col-md-5 col-sm-12 col-xs-12">
							<aside id="sidebar-section" class="sidebar-section">
								<div class="widget widget-category" data-aos="fade-up" data-aos-delay="300">
									<h3 class="widget-title"><span>{{__('Categories')}}</span></h3>
									<div class="items-list ul-li-block clearfix">
										<ul class="clearfix">
											<li data-value="0" onClick="filter(this)";>{{__('All')}} <span>{{ $countNews }}</span></li>
											@foreach($categories as $category)
											<li data-value="{{$category->id}}" onClick="filter(this)";>{{$category->title}} <span>{{$category->news_count}}</span></li>
											@endforeach
										</ul>
									</div>
								</div>

								<div class="widget widget-blogs clearfix mt-60" data-aos="fade-up" data-aos-delay="200">
									<h3 class="widget-title"><span>{{__('Highlight')}}</span></h3>
									<div class="items-list ul-li-block clearfix">
										<ul class="clearfix">
										@foreach($highlightNews as $highlight)
											<li>
												<div class="small-blog clearfix">
													<a href="{{ $highlight->uri() }}" class="item-image">
													@if($highlight->image)
           												 <img class="news-list-item-image" src="{{ $highlight->present()->image(540, 400) }}" alt="{{ $highlight->title }}">
															@else
        												<img class="news-list-item-image" src="/images/academy/image-placeholder.jpg"  alt="{{ $highlight->title }}">
  
													   @endif
													
													</a>
													<div class="item-content">
														<h3 class="item-title">
															<a href="{{ $highlight->uri() }}">
															{{ $highlight->title }}
															</a>
														</h3>
														<span class="post-date">{{ $highlight->present()->dateLocalized }}</span>
													</div>
												</div>
											</li>
											@endforeach
										</ul>
									</div>
								</div>

								

							

							</aside>
						</div>
						<div class="col-lg-9 col-md-9 col-sm-12 col-xs-12">
							<div id="tag_container">
								@includeWhen($models->count() > 0, 'news::public._list', ['models' => $models])
							</div>
						</div>
					</div>
				</div>
			</section>
			<!-- blog-section - end
			================================================== -->
        

        <div class="rich-content">{!! $page->present()->body !!}</div>



    </div>

</div>

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
                        {!! BootForm::hidden('type')->value(2) !!}
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

@endsection
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<script type="text/javascript">

	category=0

    $(window).on('hashchange', function() {
        if (window.location.hash) {
            var page = window.location.hash.replace('#', '');
            if (page == Number.NaN || page <= 0) {
                return false;
            }else{
         //       getData(page);
            }
        }
    });
    
    $(document).ready(function()
    {
        $(document).on('click', '.pagination a',function(event)
        {
            event.preventDefault();
  
            $('li').removeClass('active');
            $(this).parent('li').addClass('active');
  
            var myurl = $(this).attr('href');
            var page=$(this).attr('href').split('page=')[1];
  
            getData(page);
        });
  
    });
  
    function getData(page){
        $.ajax(
        {
            url: '?page=' + page,
            type: "get",
            datatype: "html",
			data: { category_id: category },
        }).done(function(data){
            $("#tag_container").empty().html(data);
            location.hash = page;
        }).fail(function(jqXHR, ajaxOptions, thrownError){
              alert('No response from server');
        });
    }

	function filter(elm){
		category = elm.getAttribute('data-value');
		getData(1);
	}
</script>