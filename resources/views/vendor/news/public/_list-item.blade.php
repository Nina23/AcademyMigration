<!-- <li class="news-list-item">
    <a class="news-list-item-link" href="{{ $news->uri() }}">
    @if($news->image)
        <img class="news-list-item-image" src="{{ $news->present()->image(540, 400) }}" alt="">
        @endif
        <div class="news-list-item-info">
            <h2 class="news-list-item-title">{{ $news->title }}</h2>
            <div class="news-list-item-date">{{ $news->present()->dateLocalized }}</div>
            @empty(!$news->summary)
            <div class="news-list-item-summary">{{ $news->summary }}</div>
            @endempty
        </div>
    </a>
</li> -->

<div class="blog-list clearfix" data-aos="fade-up" data-aos-delay="100">
    <a href="{{ $news->uri() }}" class="item-image">
        @if($news->image)
            <img class="news-list-item-image" src="{{ $news->present()->image(540, 400) }}" alt="{{ $news->title }}">
            @else
        <img class="news-list-item-image" src="/images/academy/image-placeholder.jpg"  alt="{{ $news->title }}">
  
        @endif
    </a>
    <div class="item-content">
        <h3 class="item-title">
            <a href="{{ $news->uri() }}">
            {{ $news->title }}
            </a>
        </h3>
        <div class="post-meta ul-li mb-30 clearfix">
            <ul class="clearfix">
                <li>{{ $news->present()->dateLocalized }}</li>
            </ul>
        </div>
        <div class="news-list-item-summary">{{ $news->summary }}</div>
        
    </div>
</div>
