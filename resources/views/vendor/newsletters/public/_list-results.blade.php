<ul class="newsletter-list-results-list">
    @foreach ($items as $newsletter)
    <li class="newsletter-list-results-item">
        <a class="newsletter-list-results-item-link" href="{{ $newsletter->uri() }}" title="{{ $newsletter->title }}">
            <span class="newsletter-list-results-item-title">{{ $newsletter->title }}</span>
        </a>
    </li>
    @endforeach
</ul>
