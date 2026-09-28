
    @foreach ($models as $announcement)
    @include('announcements::public._list-item')
    @endforeach

{!! $models->appends(Request::except('page'))->links() !!}
