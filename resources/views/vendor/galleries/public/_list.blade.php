<ul class="gallery-list-list">
    @foreach ($items as $gallery)
    @include('galleries::public._list-item')
    @endforeach
</ul>
