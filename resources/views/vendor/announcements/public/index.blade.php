@extends('pages::public.master')

@section('bodyClass', 'body-announcements body-announcements-index body-page body-page-'.$page->id)

@section('page')

<div class="page-body">
    <!-- header-section - start
			================================================== -->
    <div class="agency-creative-banner align-items-center banner-section bg-default-red clearfix d-flex text-white" id="banner-section">
        <div class="container">
            <div class="align-items-center justify-content-lg-between justify-content-lg-start row">
                <div class="col-8 col-lg-8 col-md-8 col-sm-8">
                    <div class="banner-content">
                        <h1>{{__('buletin-board')}}</h1>

                        <h4>{{__('Akademija umjetnosti Univerziteta u Banjoj Luci')}}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="absolute-social-wrap pt-0 sec-ptb-60">
            <div class="container">
                <div class="clearfix social-links-text ul-li-right" data-aos="fade-left" data-aos-delay="100">
                    <ul>
                        <li><a href="index.html"><i class="fas fa-home mr-1"></i>{{__('buletin-board')}}</a></li>
                        <li><i class="fas fa-chevron-circle-right mr-1"></i> {{__('Oglasi')}}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <!-- header-section - end ================================================== -->

    <!-- testimonial-section - start
            ================================================== -->
    @if(count($schedules)>0)
    <section id="testimonial-section" class="bg-deep-gray testimonial-section sec-ptb-100 mb-60 pb-50 clearfix">
        <div class="container">


            <div class="section-title mb-60 " data-aos="fade-up" data-aos-delay="100">
                <h2 class="title-text mb-15">{{__('Vazna obavjestenja')}}</h2>
                <p class="m-0">
                    {{__('Istaknuti oglasi')}}
                </p>
            </div>

            <div id="testimonial-carousel-2" class=" testimonial-carousel-2 arrow-top-right owl-carousel owl-theme" data-aos="fade-up" data-aos-delay="300">
                @foreach($schedules as $schedule)
                <div class="item">
                    <div class="testimonial-boxed">


                        <p >{{$schedule->formatted_program}} | {{$schedule->formatted_year}} </p>
                        
                        <h4 class="person-name">
                        {{$schedule->title}} 
</h4>
                        <a href="{{$schedule->uri()}}"> {{__('Detalji')}}</a>

                    </div>
                </div>
                @endforeach

            </div>

        </div>
    </section>
    @endif

    <!-- blog-section - start
                            ================================================== -->


    <div class="page-body-container">

        <div class="rich-content">{!! $page->present()->body !!}</div>


        <div class="rich-content">{!! $page->present()->body !!}</div>
        <!-- shop-section - start
			================================================== -->
        <section id="shop-section" class="shop-section sec-pSb-100 clearfix">
            <div class="container">
                <div class="row justify-content-lg-between justify-content-md-center justify-content-sm-center">
                    <div style="display:none" class="col-lg-4 col-md-5 col-sm-6 col-xs-12">
                        <aside id="sidebar-section" class="sidebar-section">
                            <div class="widget widget-category aos-init aos-animate mb-30" data-aos="fade-up" data-aos-delay="300">
                                <h3 class="widget-title"><span>{{__('IZABERI PROGRAM')}}</span></h3>
                                <div class="items-list ul-li-block clearfix">
                                    <ul class="clearfix">
                                        <li data-value="0" class="filter-group" onClick="setProgram(3)">
                                            <img alt="Likovni program" src="/images/academy/icons/11.svg">
                                            {{__('All')}} <strong>{{$countAnnouncement}}</strong>
                                        </li>
                                        <li class="filter-group" onClick="setProgram(0)">
                                            <img alt="Likovni program" src="/images/academy/icons/9.svg">
                                            {{__('fine-arts')}} <strong>{{$countFineArtsAnnouncement}}</strong>
                                        </li>
                                        <li class="filter-group" onClick="setProgram(1)">
                                            <img alt="Likovni program" src="/images/academy/icons/8.svg">
                                            {{__('music-art')}} <strong>{{$countMusicArtsAnnouncement}}</strong>
                                        </li>
                                        <li class="filter-group" onClick="setProgram(2)">
                                            <img alt="Likovni program" src="/images/academy/icons/10.svg">
                                            {{__('dramatic-arts')}} <strong>{{$countDramaticArtsAnnouncement}}</strong>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="widget widget-category aos-init aos-animate mb-30" data-aos="fade-up" data-aos-delay="300">
                                <h3 class="widget-title"><span>{{__('IZABERI KATEGORIJU')}}</span></h3>
                                <div class="items-list ul-li-block clearfix">
                                    <ul class="clearfix">
                                        @foreach($categories as $category)
                                        <li data-value="0" class="filter-group" onClick="setCategory({{$category->id}})">
                                            {{ $category->title}} <strong>{{$category->announcement->count()}}</strong>
                                        </li>
                                        @endforeach

                                    </ul>
                                </div>
                            </div>

                            <div class="absolute-social-wrap widget-category mb-30">
                                <h3 class="widget-title"><span>{{__('IZABERI TAG')}}</span></h3>
                                <div class="clearfix social-links-text ul-li-right">
                                    <ul>
                                        <li data-value="0" onClick="setTag(this)">{{__('All')}} {{$countAnnouncement}}</li>
                                        @foreach($tags as $tag)
                                        <li data-value="{{$tag->id}}" onClick="setTag(this)"> {{__($tag->tag)}} <span>{{$tag->announcement_count}}</span></li>
                                        @endforeach


                                    </ul>
                                </div>
                            </div>



                            <div class="mb-60 btns-group ul-li" data-aos="fade-up" data-aos-delay="100">
                                <ul class="clearfix">
                                    <li onClick="searchAnnouncements()"><a href="#!" class="btn">{{__('Potvrdi')}}</a></li>
                                    <li onClick="resetAnnouncements()"><a href="#!" class="btn btn-border">{{__('Izbrisi')}}</a></li>
                                </ul>
                            </div>

                        </aside>
                    </div>

                    <div class="col-lg-8 col-md-12 col-sm-12 col-xs-12">

                        <section id="blog-section" class="blog-section sec-pb-100 clearfix">
                            <div class="container">
                                <div class="row justify-content-lg-between justify-content-md-center justify-content-sm-center">

                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <h4 class="mb-60 clearfix">{{__('Lista oglasa')}}</h4>

                                        <div id="empty-note" class="bg-deep-gray text-center testimonial-section sec-ptb-100 pb-50 mb-30 sclearfix">
                                            <h5>{{__('Nema rezultata')}}</h5>
                                        </div>

                                        <div class="schedule-day mb-30">

                                            <div class="blog-list clearfix" data-aos="fade-up" data-aos-delay="100">
                                                <div id="tag_container">
                                                    <ul class="announcement-list-list">
                                                        @includeWhen($models->count() > 0, 'announcements::public._list', ['models' => $models])
                                                    </ul>
                                                </div>

                                            </div>



                                        </div>



                                    </div>
                                </div>
                        </section>
                        <!-- blog-section - end
			================================================== -->




                    </div>

                </div>

                @endsection

                <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
                <script type="text/javascript">
                    $(window).on('hashchange', function() {
                        if (window.location.hash) {
                            var page = window.location.hash.replace('#', '');
                            if (page == Number.NaN || page <= 0) {
                                return false;
                            } else {
                                //       getData(page);
                            }
                        }
                    });

                    let program = 3;

                    function setProgram(choosedProgram) {
                        program = choosedProgram;

                        //brisem sve klase active koji postoje na ovom elementu
                        var elems = document.querySelectorAll(".active");
                        [].forEach.call(elems, function(el) {
                            el.classList.remove("active");
                        });

                    }

                    let category = 0;

                    function setCategory(choosedCategory) {
                        category = choosedCategory;

                        //brisem sve klase active koji postoje na ovom elementu
                        var elems = document.querySelectorAll(".active");
                        [].forEach.call(elems, function(el) {
                            el.classList.remove("active");
                        });

                    }

                    let tag = 0;

                    function setTag(elm) {
                        //filtere zavrsiti
                        tag = elm.getAttribute('data-value');
                    }


                    $(document).ready(function() {
                        document.getElementById('empty-note').style.display = 'none';
                        $(document).on('click', '.pagination a', function(event) {
                            event.preventDefault();

                            $('li').removeClass('active');
                            $(this).parent('li').addClass('active');

                            var myurl = $(this).attr('href');
                            var page = $(this).attr('href').split('page=')[1];

                            getData(page);
                        });

                    });

                    function searchAnnouncements() {
                        getData(1);
                    }

                    function resetAnnouncements() {
                        tag = 0;
                        program = 3;
                        category = 0;
                        getData(1);
                    }

                    function getData(page) {
                        $.ajax({
                            url: '?page=' + page,
                            type: "get",
                            datatype: "html",
                            data: {
                                tag_id: tag,
                                program_id: program,
                                category_id: category
                            },
                        }).done(function(data) {
                            if (data.length == 0) {
                                document.getElementById('empty-note').style.display = 'block';
                            } else {
                                document.getElementById('empty-note').style.display = 'none';
                            }
                            $("#tag_container").empty().html(data);
                            location.hash = page;
                        }).fail(function(jqXHR, ajaxOptions, thrownError) {
                            alert('No response from server');
                        });
                    }
                </script>