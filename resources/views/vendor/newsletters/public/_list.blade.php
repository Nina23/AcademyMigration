<ul class="newsletter-list-list">
    @foreach ($items as $newsletter)
    @include('newsletters::public._list-item')
    @endforeach
</ul>
