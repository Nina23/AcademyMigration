<li class="department-list-item">
    <a class="department-list-item-link" href="{{ $department->uri() }}" title="{{ $department->title }}">
        <span class="department-list-item-title">{{ $department->title }}</span>
        <span class="department-list-item-image-wrapper">
            <img class="department-list-item-image" src="{{ $department->present()->image(null, 200) }}" width="{{ $department->image->width }}" height="{{ $department->image->height }}" alt="">
        </span>
    </a>
</li>
