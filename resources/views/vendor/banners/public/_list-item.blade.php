<li class="banner-list-item">
    <a class="banner-list-item-link" href="{{ $banner->uri() }}" title="{{ $banner->title }}">
        <span class="banner-list-item-title">{{ $banner->title }}</span>
        <span class="banner-list-item-image-wrapper">
            <img class="banner-list-item-image" src="{{ $banner->present()->image(null, 200) }}" width="{{ $banner->image->width }}" height="{{ $banner->image->height }}" alt="">
        </span>
    </a>
</li>
