<ul class="advertismentboard-list-results-list">
    @foreach ($items as $advertismentboard)
    <li class="advertismentboard-list-results-item">
        <a class="advertismentboard-list-results-item-link" href="{{ $advertismentboard->uri() }}" title="{{ $advertismentboard->title }}">
            <span class="advertismentboard-list-results-item-title">{{ $advertismentboard->title }}</span>
        </a>
    </li>
    @endforeach
</ul>
