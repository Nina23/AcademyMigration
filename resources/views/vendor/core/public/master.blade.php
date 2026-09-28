<!doctype html>
<html lang="{{ config('app.locale') }}">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <meta name="description" content="@yield('description')">
    <meta name="keywords" content="@yield('keywords')">

    <meta property="og:site_name" content="{{ $websiteTitle }}">
    <meta property="og:title" content="@yield('ogTitle')">
    <meta property="og:description" content="@yield('description')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ URL::full() }}">
	<meta property="og:image" content="@yield('ogImage')">
	<meta property="og:image:url" content="@yield('ogImage')">
	

    @if (config('typicms.twitter_site') !== null)
    <meta name="twitter:site" content="{{ config('typicms.twitter_site') }}">
    <meta name="twitter:card" content="summary_large_image">
    @endif

    @if (config('typicms.facebook_app_id') !== null)
    <meta property="fb:app_id" content="{{ config('typicms.facebook_app_id') }}">
    @endif
    <!-- Tempalte Style -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/template/fontawesome-all.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/template/icomoon.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/template/animate.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/template/owl.carousel.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/template/owl.theme.default.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/template/magnific-popup.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/template/jquery-ui.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/template/aos.css') }}">
	<link href="{{ App::environment('production') ? mix('css/public.css') : asset('css/public.css') }}" rel="stylesheet">
	
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-151022266-1"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());

		gtag('config', 'UA-151022266-1');
	</script>


    @include('core::public._feed-links')

    @stack('css')

</head>

<body class="body-{{ $lang }} @yield('bodyClass') @if ($navbar)has-navbar @endif" id="top">
	

    @section('skip-links')
    <a href="#main" class="skip-to-content">@lang('Skip to content')</a>
    @show

    @include('core::_navbar')

    	
	
        @section('site-header')
		<header id="header-section" class="header-section hanging-header clearfix">
			<div class="container">
				<div class="header-wrap">
					<div class="row align-items-center">
						<div class="col-lg-3">
							<div class="brand-logo">
								<a href="{{ (app()->getLocale()=='sr') ?  url('/'): url(app()->getLocale()) }}">
									<img class="logo" src="/images/academy/master-logo.png" width="" height="{{ $height ?? 80 }}" alt="{{ TypiCMS::title() }}">
								</a>
								<div class="mobile-menu-btns float-right ul-li-right">
									<ul class="clearfix">
										<li>
											<button type="button" class="menu-btn">
												<span></span>
												<span></span>
												<span></span>
											</button>
										</li>
									</ul>
								</div>
							</div>
						</div>
						<div class="col-lg-7">
							@section('site-nav')
								<nav class="main-menu menu-header ul-li-center clearfix" id="site-nav">
									@menu('main')
								</nav>
							@show
						</div>
						<div class="col-lg-2">
							
								<nav class="main-menu ul-li-left clearfix" id="site-nav">
									@menu('academy-header')
								</nav>
						</div>

					</div>
				</div>
			</div>
		</header>





               
            <!-- <div class="site-header-container"> -->
			<!-- <img class="logo" src="{{ Storage::url('settings/'.config('typicms.image')) }}" width="" height="{{ $height ?? 80 }}" alt="{{ TypiCMS::title() }}"> -->
                <!-- @section('site-title')
                <div class="site-title">@include('core::public._site-title')</div>
                @show -->
                <!-- <a href="#navigation" class="d-flex d-lg-none btn-offcanvas" data-toggle="offcanvas" title="@lang('Open navigation')" aria-label="@lang('Open navigation')" role="button" aria-controls="navigation" aria-expanded="false">
                    <svg width="2.5rem" height="2.5rem" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M2.5 11.5A.5.5 0 0 1 3 11h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4A.5.5 0 0 1 3 7h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4A.5.5 0 0 1 3 3h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                    </svg>
                </a> -->
                <!-- <div class="site-header-offcanvas" id="navigation"> -->
                    <!-- <button class="d-flex d-lg-none btn-offcanvas btn-offcanvas-close" data-toggle="offcanvas" title="@lang('Close navigation')" aria-label="@lang('Close navigation')">
                        <svg width="2.5rem" height="2.5rem" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </button> -->
                    <!-- @section('site-nav')
                    <nav class="main-menu ul-li-right clearfix" id="site-nav">
                        @menu('main')
                    </nav>
                    @show -->


                    <!-- @include('search::public._form') -->
                    
                <!-- </div> -->
            <!-- </div> -->

			<div class="sidebar-menu-wrapper">
				<div id="sidebar-menu" class="sidebar-menu">
				<span class="close-btn"><i class="fal fa-times"></i></span>

				<div id="menu-list" class="menu-list ul-li-block clearfix">
					
						@menu('main')
						@menu('academy-header')
							
				</div>
			</div>

			

			<div class="overlay"></div>
		</div>
		

        
        @show

        @if (session('verified'))
            <div class="alert alert-success">@lang('Your email address has been verified.')</div>
        @endif

        <main class="main" id="main">
		<div class="top-header">
			<div class="container">
				<div class="row">
					<div class="col-5 display-small-none">
					<div class="social-links-round ul-li-left clearfix aos-init aos-animate" data-aos="fade-up" data-aos-delay="300">
					<ul class="clearfix">
						<li><a href="https://www.facebook.com/aubl.org/" target="_blank"><i class="fab fa-facebook-f"></i></a></li>
						<li><a href="https://www.youtube.com/user/AkademijaUmjetnosti" target="_blank"><i class="fab fa-youtube"></i></a></li>
						
						<li><a href="https://www.instagram.com/akademija_umjetnosti_banjaluka/" target="_blank"><i class="fab fa-instagram"></i></a></li>
					</ul>
				</div>
					</div>
					<div class="col-sm-7 col-12">
						<div class="container">
							<div class="clearfix social-links-text ul-li-right" style="padding-top:5px;" data-aos="fade-left" data-aos-delay="100">
								<ul class="display-small-center">
									@include('core::public._lang-switcher')
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
			@include('core::public.flash-messages')
            @yield('content')
        </main>

        <!-- preloader - start -->
		<div id="preloader"></div>
        <!-- preloader - end -->
        
		<!-- backtotop - start -->
		<div id="thetop"></div>
		<div id="backtotop">
			<a href="#top" id="scroll" class="disabled">
				<i class="far fa-arrow-up"></i>
			</a>
		</div>
		<!-- backtotop - end -->

		

        <!-- <a href="#top" class="smooth-scroll anchor-top disabled" id="anchor-top" aria-label="@lang('Back to top')">⇧</a> -->

        @section('site-footer')

        <!-- footer-section - start
		================================================== -->
		<footer id="footer-section" class="footer-section sec-ptb-100 bg-default-red text-white clearfix">
			<div class="container">
				<div class="row">

					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<div class="brand-logo clearfix" data-aos="fade-up" data-aos-delay="100">
							<a href="{{ app('router')->has('home') ? route('home') : url('/') }}">
								<img src="/images/academy/logo_footer.svg" style="height:72px;" alt="Akademija umjetnosti logo">
                            </a>
                            
                        <p class="text-white">{{__('Akademija umjetnosti Univerziteta u Banjoj Luci')}}</p>
                        </div>
					</div>

					<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
						<div class="row">
							<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                
								<div class="useful-links ul-li-block clearfix" data-aos="fade-up" data-aos-delay="300">
									@menu('academy-footer')
								</div>
							</div>

							<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
								<div class="useful-links ul-li-block clearfix" data-aos="fade-up" data-aos-delay="500">
                                    @menu('useful_links')
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                        <div class="useful-links ul-li-block clearfix" data-aos="fade-up" data-aos-delay="300">
                            <ul class="clearfix">
                                <li><a href="tel:+38751348800">+387(0)51 348 800</a></li>
                                <li><a taget="_blank" href="mailto:info@au.unibl.org">info@au.unibl.org</a></li>
                                <li>{{__('academy-address')}} </li>
                            </ul>
                        </div>
						<div class="social-links-text ul-li-right clearfix " data-aos="fade-up" data-aos-delay="700">
							<ul class="clearfix">
								<li><a href="https://www.facebook.com/aubl.org/" target="_blank">facebook</a></li>
								<li><a href="https://www.youtube.com/user/AkademijaUmjetnosti" target="_blank">youtube</a></li>
								<li><a href="https://www.instagram.com/akademija_umjetnosti_banjaluka/" target="_blank">instagram</a></li>
							</ul>
						</div>
					</div>
					
				</div>
			</div>
		</footer>
		<!-- footer-section - end
		================================================== -->
		<!-- footer-section - start
		================================================== -->
		<footer id="footer-bottom" class="footer-section footer-bottom bg-default-light clearfix">
			<div class="container">
				<div class="row">

					<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
					<span>
						{{__('copyrights')}}
					</span>
					</div>

					<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                        
						<div class=" text-rc clearfix">
							<span>{{__('designed by')}} <a href="https://myartmomentum.com/" target="_blank"> MAM Design Studio</a></span>
						</div>
					</div>
					
				</div>
			</div>
		</footer>
		<!-- footer-section - end
		================================================== -->



        <!-- <footer class="site-footer">
            <div class="site-footer-container">
                <nav class="site-footer-nav">
                    @menu('social')
                </nav>
                <nav class="site-footer-nav">
                    @menu('footer')
                </nav>
                <nav class="site-footer-nav">
                    @menu('legal')
                </nav>
            </div>

            
        </footer> -->
        @show

    </div>

        <!-- template jquery include -->
        


        <script src="{{ asset('js/template/jquery-1.10.1.min.js') }}"></script>
        <script src="{{ asset('js/template/popper.min.js') }}"></script>
        <script src="{{ asset('js/template/bootstrap.min.js') }}"></script>
        <script src="{{ asset('js/template/owl.carousel.min.js') }}"></script>
        <script src="{{ asset('js/template/counterup.min.js') }}"></script>
        <script src="{{ asset('js/template/countdown.js') }}"></script>
        <script src="{{ asset('js/template/waypoints.min.js') }}"></script>
        <script src="{{ asset('js/template/magnific-popup.min.js') }}"></script>
        <script src="{{ asset('js/template/isotope.pkgd.min.js') }}"></script>
        <script src="{{ asset('js/template/masonry.pkgd.min.js') }}"></script>
        <script src="{{ asset('js/template/imagesloaded.pkgd.min.js') }}"></script>
        <script src="{{ asset('js/template/jquery-ui.js') }}"></script>
        <script src="{{ asset('js/template/aos.js') }}"></script>
        <script src="{{ asset('js/template/validate.js') }}"></script>

        <!-- google map - jquery include -->
        <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDk2HrmqE4sWSei0XdKGbOMOHN3Mm2Bf-M&ver=2.1.6"></script>
        <script src="{{ asset('js/template/gmaps.min.js') }}"></script>

        <!-- mobile menu - jquery include -->
        <script src="{{ asset('js/template/mCustomScrollbar.js') }}"></script>

        <!-- custom - jquery include -->
        <script src="{{ asset('js/template/custom.js') }}"></script>
        
    <script src="{{ App::environment('production') ? mix('js/public.js') : asset('js/public.js') }}"></script>
    @if (request('preview'))
    <script src="{{ asset('js/previewmode.js') }}"></script>
    @endif

    @stack('js')

</body>

</html>
