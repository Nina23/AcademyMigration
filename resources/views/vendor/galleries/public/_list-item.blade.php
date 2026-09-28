<li class="gallery-list-item">
    <a class="gallery-list-item-link" href="{{ $gallery->uri() }}" title="{{ $gallery->title }}">
        <span class="gallery-list-item-title">{{ $gallery->title }}</span>
        @if($gallery->image)
        <span class="gallery-list-item-image-wrapper">
            <img class="gallery-list-item-image" src="{{ $gallery->present()->image(null, 200) }}" width="{{ $gallery->image->width }}" height="{{ $gallery->image->height }}" alt="">
        </span>
        @endif
    </a>
</li>
