@if(!$biography->isDirectoryDepartments())
    <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6 element-item {{ $biography->isChefDepartments() ? 'head-department' : ''  }}" data-category="">
        <div class="" data-aos="fade-up" data-aos-delay="100">
           
            <div class="about-image creative-image aos-init aos-animate" data-aos="fade-up" data-aos-delay="100" style="padding-top:10px">
                 @if($biography->image)
                <img class="" src="{{ $biography->present()->image(1000, 1200) }}"  width="{{ $biography->image->width }}" height="{{ $biography->image->height }}"  alt="" />
                @else
                <img class="news-list-item-image" src="/images/academy/image-placeholder-portrait.jpg"  alt="{{ $biography->title }}"/>
                @endif
            </div>
            
            <div class="item-content mt-30" style="padding-bottom:10px">
                <h6 class="item-title">{{ $biography->title }}</h6>
                <p class="item-brand">{{ $biography->name }}</p>
                <a href="{{ $biography->uri() }}" title="{{ $biography->title }}" class="details-btn">{{__('Biography')}} <i class="fas fa-long-arrow-alt-right"></i></a>
            </div>
        </div>
	</div>
    @endif
