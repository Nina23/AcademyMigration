
    
    @foreach ($models as $news)
        @include('news::public._list-item')
    @endforeach

    {!! $models->appends(Request::except('page'))->links() !!}


