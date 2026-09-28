<ul class="biography-list-results-list">
    @foreach ($items as $biography)
    <li class="biography-list-results-item">
        <a class="biography-list-results-item-link" href="{{ $biography->uri() }}" title="{{ $biography->title }}">
            <span class="biography-list-results-item-title">{{ $biography->title }}</span>
        </a>
    </li>
    @endforeach
</ul>
