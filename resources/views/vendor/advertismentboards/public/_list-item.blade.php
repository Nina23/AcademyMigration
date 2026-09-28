<!-- <li class="advertismentboard-list-item">
    <a class="advertismentboard-list-item-link" href="{{ $advertismentboard->uri() }}" title="{{ $advertismentboard->title }}">
        <span class="advertismentboard-list-item-title">{{ $advertismentboard->title }}</span>
        @if($advertismentboard->image)
        <span class="advertismentboard-list-item-image-wrapper">
            <img class="advertismentboard-list-item-image" src="{{ $advertismentboard->present()->image(null, 200) }}" width="{{ $advertismentboard->image->width }}" height="{{ $advertismentboard->image->height }}" alt="">
        </span>
        @endif
    </a>
</li> -->

<div class="blog-list clearfix" data-aos="fade-up" data-aos-delay="100">
    <a  href="{{ $advertismentboard->uri() }}" title="{{ $advertismentboard->title }}" class="item-image">
        @if($advertismentboard->image)
            <img class="news-list-item-image" src="{{ $advertismentboard->present()->image(null, 200) }}" width="{{ $advertismentboard->image->width }}" height="{{ $advertismentboard->image->height }}" alt="{{ $advertismentboard->title }}">
        @else
        <img class="news-list-item-image" src="/images/academy/image-placeholder.jpg"  alt="{{ $advertismentboard->title }}">
  
        @endif
   </a>
    <div class="item-content"> 
        <!-- Nina, ovde ide slicica zavisno od kategorije i tag imena slika su music, drama and fineArts,a moze da bude tako se zovu ikonice i odgovaraju tim kategorijama-->
        <div class="filter-group">
        @foreach($advertismentboard->tags->pluck('tag')->all() as $tag)
            <img alt="{{$advertismentboard->category->title}}" src="/images/academy/icons/{{$advertismentboard->category->id}}.svg">
	
            <span>{{__($tag)}}</span>
        @endforeach
        </div>
        <h3 class="item-title">
            <a  href="{{ $advertismentboard->uri() }}" title="{{ $advertismentboard->title }}">
            {{ $advertismentboard->title }}
            </a>
        </h3>
        <div class="post-meta ul-li mb-30 clearfix">
            <ul class="clearfix">
                <li>{{ $advertismentboard->present()->dateLocalized }}</li>
            </ul>
        </div>
        <div class="news-list-item-summary">{{ $advertismentboard->summary }}</div>
    </div>
</div>
