<ul class="category-list-results-list">
    @foreach ($items as $category)
    <li class="category-list-results-item">
        <a class="category-list-results-item-link" href="{{ $category->uri() }}" title="{{ $category->title }}">
            <span class="category-list-results-item-title">{{ $category->title }}</span>
        </a>
    </li>
    @endforeach
</ul>
