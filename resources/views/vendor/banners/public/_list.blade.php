<ul class="banner-list-list">
    @foreach ($items as $banner)
    @include('banners::public._list-item')
    @endforeach
</ul>
