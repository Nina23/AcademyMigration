<li class="category-list-item">
    <a class="category-list-item-link" href="{{ $category->uri() }}" title="{{ $category->title }}">
        <span class="category-list-item-title">{{ $category->title }}</span>
        @if($category->image)
        <span class="category-list-item-image-wrapper">
            <img class="category-list-item-image" src="{{ $category->present()->image(null, 200) }}" width="{{ $category->image->width }}" height="{{ $category->image->height }}" alt="">
        </span>
        @endif
    </a>
</li>
