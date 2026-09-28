<ul class="announcement-list-results-list">
    @foreach ($items as $announcement)
    <li class="announcement-list-results-item">
        <a class="announcement-list-results-item-link" href="{{ $announcement->uri() }}" title="{{ $announcement->title }}">
            <span class="announcement-list-results-item-title">{{ $announcement->title }}</span>
        </a>
    </li>
    @endforeach
</ul>
