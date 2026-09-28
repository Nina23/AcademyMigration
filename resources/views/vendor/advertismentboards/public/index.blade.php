@extends('pages::public.master')

@section('bodyClass', 'body-advertismentboards body-advertismentboards-index body-page body-page-'.$page->id)

@section('page')

<div class="page-body">
    <!-- banner-section - start
			================================================== -->
    <div class="agency-creative-banner align-items-center banner-section bg-default-red clearfix d-flex text-white" id="banner-section">
        <div class="container">
            <div class="align-items-center justify-content-lg-between justify-content-lg-start row">
                <div class="col-8 col-lg-8 col-md-8 col-sm-8">
                    <div class="banner-content">
                        <h1>{{__('buletin-board')}}</h1>

                        <h4>{{ __('Akademija umjetnosti Univerziteta u Banjoj Luci')}}</h4>
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
    
        @include('advertismentboards::public._itemlist-json-ld', ['items' => $models])

       
        <!-- blog-section - start
			================================================== -->
			<section id="blog-section" class="blog-section sec-ptb-100 clearfix">
				<div class="container">
					<div class="row justify-content-lg-between justify-content-md-center justify-content-sm-center">
                    <div class="col-lg-3 col-md-5 col-sm-12 col-xs-12">
							<aside id="sidebar-section" style="display:none" class="sidebar-section">
                                <!-- Search block -->
								<!-- <div class="widget widget-search" data-aos="fade-up" data-aos-delay="100">
									<h3 class="widget-title"><span>SEARCH</span></h3>
									<form action="#">
										<div class="form-item">
											<input type="search" name="search" placeholder="Type Your Keywords">
											<button type="submit" class="submit-btn"><i class="icon-magnifying-glass"></i></button>
										</div>
									</form>
								</div> -->

								
<!-- Nina - povezati sa tagovima -->
								<div class="widget widget-category" data-aos="fade-up" data-aos-delay="300">
									<h3 class="widget-title"><span>{{__('Filters')}}</span></h3>
									<div class="items-list ul-li-block clearfix">
										<ul class="clearfix">
											<li data-value="0" onClick="filter(this)">{{__('All')}} <span>{{$countAdvertisment}}</span></li>
                                            @foreach($tags as $tag)
                                            <li data-value="{{$tag->id}}" onClick="filter(this)">{{__($tag->tag)}} <span>{{$tag->advertismentboards_count}}</span></li>
                                            @endforeach
										</ul>
									</div>
								</div>

							</aside>
                        </div>
                        
						<div class="col-lg-9 col-md-9 col-sm-12 col-xs-12">
                        <div id="tag_container">
                        @includeWhen($models->count() > 0, 'advertismentboards::public._list', ['models' => $models])
                        </div>

                        </div>
                    </div>
				</div>
			</section>
			<!-- blog-section - end
            ================================================== -->
            
           

        <div class="rich-content">{!! $page->present()->body !!}</div>

        

        

    </div>

    <!-- <section id="board-newsletter-section" class="blog-section clearfix">
        <div class="bg-default-light pb-0 sec-ptb-100">
            <div class="clearfix pt-0 sec-ptb-60">
                <div class="container">
                    <div class="mb-60 section-title aos-init aos-animate" data-aos="fade-up" data-aos-delay="100">
                        <h2>{{__('newsletter-title')}}</h2>

                            <p>{{__('newsletter-bulletin-board-message')}}</p>
                    </div>

                    <div class="row aos-init aos-animate" data-aos="fade-up" data-aos-delay="300">
                        <form action="#" style="width:100%;">
                            <div class="row">
                                <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">
                                    <div class="form-item">
                                        <input type="text" name="name" placeholder="{{__('email')}}">
                                    </div>
                                </div>

                                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                    <div class="form-item">
                                        <button type="submit" class="btn">{{__('submit')}}</button>
                                    </div>
                                </div>

                                
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section> -->

</div>

@endsection

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<script type="text/javascript">

	tag=0

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
		console.log('sta se desava');
        $.ajax(
        {
            url: '?page=' + page,
            type: "get",
            datatype: "html",
			data: { tag_id: tag },
        }).done(function(data){
            $("#tag_container").empty().html(data);
            location.hash = page;
        }).fail(function(jqXHR, ajaxOptions, thrownError){
              alert('No response from server');
        });
    }

	function filter(elm){
		tag = elm.getAttribute('data-value');
        console.log(tag);
		getData(1);
	}
</script>
