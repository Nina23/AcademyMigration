<ul class="department-list-results-list">
    @foreach ($items as $department)
    <li class="department-list-results-item">
        <a class="department-list-results-item-link" href="{{ $department->uri() }}" title="{{ $department->title }}">
            <span class="department-list-results-item-title">{{ $department->title }}</span>
        </a>
    </li>
    @endforeach
</ul>
