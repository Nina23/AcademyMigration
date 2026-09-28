<ul class="category-list-list">
    @foreach ($items as $category)
    @include('categories::public._list-item')
    @endforeach
</ul>
