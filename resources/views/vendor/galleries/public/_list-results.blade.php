<ul class="gallery-list-results-list">
    @foreach ($items as $gallery)
    <li class="gallery-list-results-item">
        <a class="gallery-list-results-item-link" href="{{ $gallery->uri() }}" title="{{ $gallery->title }}">
            <span class="gallery-list-results-item-title">{{ $gallery->title }}</span>
        </a>
    </li>
    @endforeach
</ul>
