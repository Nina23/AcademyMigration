@extends('pages::public.master')

@section('bodyClass', 'body-classschedules body-classschedules-index body-page body-page-'.$page->id)


@section('page')

<!-- Nina ovo je dio vezan za module schedule, pogledaj kako je u biografijama
			================================================== -->

<!-- header-section - start
           ================================================== -->
<div class="agency-creative-banner align-items-center banner-section bg-default-red clearfix d-flex text-white" id="banner-section">
    <div class="container">
        <div class="align-items-center justify-content-lg-between justify-content-lg-start row">
            <div class="col-8 col-lg-8 col-md-8 col-sm-8">
                <div class="banner-content">
                    <h1>{{__('Raspored casova')}}</h1>
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
                    <li><i class="fas fa-chevron-circle-right mr-1"></i> {{__('Raspored casova')}}</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<!-- header-section - end ================================================== -->


<!-- testimonial-section - start
           ================================================== -->
<section id="testimonial-section" class="bg-deep-gray testimonial-section sec-ptb-100 pb-50 clearfix">
    <div class="container">

        <div class="section-title mb-60 " data-aos="fade-up" data-aos-delay="100">
            <h2 class="title-text mb-15">{{__('Vazna obavjestenja')}}</h2>
            <p class="m-0">
                {{__('Promjena rasporeda')}}
            </p>
        </div>

        <div id="testimonial-carousel-2" class=" testimonial-carousel-2 arrow-top-right owl-carousel owl-theme" data-aos="fade-up" data-aos-delay="300">
            @foreach($announcements as $announcement)
            <div class="item">
                <div class="testimonial-boxed">

                <p >{{$announcement->formatted_program}} | {{$announcement->formatted_year}} </p>
                    <h4 class="person-name">{{$announcement->title}}</h4>
                   
                    <a href="#!">{{__('Detalji')}}</a>

                </div>
            </div>
            @endforeach
        </div>

    </div>
</section>
<!-- blog-section - start
    ================================================== -->
<div class="about-section clearfix" id="about-section">


    <div class="page-body-container">

        <div class="rich-content">{!! $page->present()->body !!}</div>
        <!-- shop-section - start
                ================================================== -->
        <section id="shop-section" class="shop-section sec-ptb-100 clearfix">
            <div class="container">
                <div class="row justify-content-lg-between justify-content-md-center justify-content-sm-center">
                    <div class="col-lg-4 col-md-5 col-sm-6 col-xs-12">
                        <aside id="sidebar-section" class="sidebar-section">

                            <div class="widget widget-category" data-aos="fade-up" data-aos-delay="100">
                                <h3 class="widget-title"><span>{{__('IZABERI SMJER')}}</span></h3>
                                <div id="category-items-list" class="category-items-list ul-li-block clearfix">
                                    <ul class="clearfix">

                                    <li class="item-has-child">
                                            <a href="#men-submenu" data-toggle="collapse" aria-expanded="false">{{__('Likovni program')}}</a>
                                            <div class="radio-btns-group ul-li-block">
                                                <ul class="sub-menu collapse" id="men-submenu" data-parent="#category-items-list">
                                                    @foreach($departments as $department)
                                                    @if($department->program_id == 1)
                                                    <li onClick="setDepartment({{$department->id}})">
                                                        <div class="radio-btn clearfix">
                                                            <input type="radio" id="{{$department->title}}" name="{{$department->title}}">
                                                            <a for="{{$department->title}}">{{$department->title}} </a>
                                                        </div>
                                                    </li>
                                                    @endif
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </li>
                                        <li class="item-has-child">
                                            <a href="#women-submenu" data-toggle="collapse" aria-expanded="false">{{__('Muzicki program')}}</a>
                                            <div class="radio-btns-group ul-li-block">
                                                <ul class="sub-menu collapse" id="women-submenu" data-parent="#category-items-list">
                                                    @foreach($departments as $department)
                                                    @if($department->program_id == 2)
                                                    <li onClick="setDepartment({{$department->id}})">
                                                        <div class="radio-btn clearfix">
                                                            <input type="radio" id="{{$department->title}}" name="{{$department->title}}">
                                                            <a for="{{$department->title}}">{{$department->title}} </a>
                                                        </div>
                                                    </li>
                                                    @endif
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </li>
                                        <li class="item-has-child">
                                            <a href="#kids-submenu" data-toggle="collapse" aria-expanded="false">{{__('Dramski program')}}</a>
                                            <div class="radio-btns-group ul-li-block">
                                                <ul class="sub-menu collapse" id="kids-submenu" data-parent="#category-items-list">
                                                    @foreach($departments as $department)
                                                    @if($department->program_id == 3)
                                                    <li onClick="setDepartment({{$department->id}})">
                                                        <div class="radio-btn clearfix">
                                                            <input type="radio" id="{{$department->title}}" name="{{$department->title}}">
                                                            <a for="{{$department->title}}">{{$department->title}} </a>
                                                        </div>

                                                    </li>
                                                    @endif
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="widget product-size" data-aos="fade-up" data-aos-delay="100">
                                <h3 class="widget-title"><span>{{__('IZABERI GODINU STUDIJA')}}</span></h3>
                                <div class="size-btns-group ul-li clearfix">
                                    <ul class="clearfix">
                                        <li class="mb-30"><a  onClick="setYear(1)">I</a></li>
                                        <li class="mb-30"><a onClick="setYear(2)">II</a></li>
                                        <li class="mb-30"><a onClick="setYear(3)">III</a></li>
                                        <li class="mb-30"><a onClick="setYear(4)">IV</a></li>
                                        <li class="mb-30"><a onClick="setYear(5)">MA</a></li>
                                        <li class="mb-30"><a onClick="setYear(6)">DR</a></li>
                                    </ul>
                                </div>
                            </div>


                            <div class="widget btns-group ul-li" data-aos="fade-up" data-aos-delay="100">
                                <ul class="clearfix">
                                    <li><a onClick="searchSchedule()" class="btn">{{__('Potvrdi')}} </a></li>
                                    <li><a onClick="resetSchedule()" class="btn btn-border">{{__('Izbrisi')}}</a>
                                    </li>
                                </ul>
                            </div>

                        </aside>
                    </div>

                    <div class="col-lg-8 col-md-12 col-sm-12 col-xs-12">


                        <div id="show-note" class="bg-deep-gray text-center testimonial-section sec-ptb-100 pb-50 mb-30 sclearfix">
                            <h5>{{__('Izaberi zeljeni odsjek i godinu studija')}} </h5>
                        </div>
                        <div id="empty-note" class="bg-deep-gray text-center testimonial-section sec-ptb-100 pb-50 mb-30 sclearfix">
                            <h5>{{__('Nema rasporeda')}}</h5>
                        </div>

                        <div id="tag_container" class=" mb-30">
                            @includeWhen($models->count() > 0, 'classschedules::public._list', ['items' => $models])
                        </div>
        </section>


        {{--@include('files::public._documents', ['model' => $page])
                            @include('files::public._images', ['model' => $page])

                            @include('classschedules::public._itemlist-json-ld', ['items' => $models])

                            --}}

    </div>

</div>

@endsection
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<script type="text/javascript">
    let year = 0;

    $(document).ready(function() {
        document.getElementById('empty-note').style.display = 'none';
    });

    function setYear(choosedYear) {
        year = choosedYear;

        //brisem sve klase active koji postoje na ovom elementu
        var elems = document.querySelectorAll(".active");
        [].forEach.call(elems, function(el) {
            el.classList.remove("active");
        });
    }
    //dodajem novu klasu active na novi klinkutni element
    document.addEventListener('click', function classClicked(event) {
        event.target.classList.add('active');

    });

    let department = 1;

    function setDepartment(choosedDepartment) {
        department = choosedDepartment;

        $('.department').on('click', function() {
            var elems = document.querySelectorAll(".department");
            [].forEach.call(elems, function(el) {
                el.classList.remove("active");
            });

            $(this).addClass('active');
        });

    }

    let reset = 0;

    function searchSchedule() {
        document.getElementById('show-note').style.display = 'none';
        //  document.getElementById( 'empty-note' ).style.display = 'block';
        $.ajax({
            url: '',
            type: 'get',
            datatype: 'html',
            data: {
                department_id: department,
                year: year
            },
        }).done(function(data) {
            if (data.length == 0 && reset === 0) {
                document.getElementById('empty-note').style.display = 'block';
            } else if (reset === 1) {
                reset = 0;
                document.getElementById('empty-note').style.display = 'none';
                document.getElementById('show-note').style.display = 'block';
            } else {
                document.getElementById('empty-note').style.display = 'none';
            }
            $('#tag_container').empty().html(data);
        }).fail(function(jqXHR, ajaxOptions, thrownError) {
            alert('No response from server');
        });
    }

    function resetSchedule() {
        year = 0;
        department = 1;
        reset = 1;
        searchSchedule();
    }
</script>