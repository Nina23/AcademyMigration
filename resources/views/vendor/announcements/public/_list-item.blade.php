<div class="item-content mb-60">
    <div class="filter-group mb-15 ul-li">
        <ul class="clearfix">
        @foreach($announcement->formatted_program_output as $program)
            <img alt="{{$program}}" src="/images/academy/icons/{{$program}}.svg">
        @endforeach
            <strong>{{$announcement->announcementCategory->title}}</strong> |
            <span>{{$announcement->present()->dateLocalized}}</span>
        </ul>
    </div>
    <h5 class="">
        <a href="{{$announcement->uri()}}" title="title"> {{$announcement->title}} </a>
    </h5>
    <div class="post-meta ul-li clearfix">
        <ul class="clearfix">
        @foreach($announcement->tags->pluck('tag')->all() as $tag)
        <li><span>{{__($tag)}}</span></li>
        @endforeach
        </ul>
    </div>
    <div class="news-list-item-summary">{{$announcement->summary}}</div>
    
</div>

