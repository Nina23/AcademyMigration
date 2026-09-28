<li class="newsletter-list-item">
    <a class="newsletter-list-item-link" href="{{ $newsletter->uri() }}" title="{{ $newsletter->title }}">
        <span class="newsletter-list-item-title">{{ $newsletter->title }}</span>
        <span class="newsletter-list-item-image-wrapper">
            <img class="newsletter-list-item-image" src="{{ $newsletter->present()->image(null, 200) }}" width="{{ $newsletter->image->width }}" height="{{ $newsletter->image->height }}" alt="">
        </span>
    </a>
</li>
