<ul class="banner-list-results-list">
    @foreach ($items as $banner)
    <li class="banner-list-results-item">
        <a class="banner-list-results-item-link" href="{{ $banner->uri() }}" title="{{ $banner->title }}">
            <span class="banner-list-results-item-title">{{ $banner->title }}</span>
        </a>
    </li>
    @endforeach
</ul>
