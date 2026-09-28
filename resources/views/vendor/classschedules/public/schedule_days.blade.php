<div class="schedule-day testimonial-boxed mb-30">
<h6 class="clearfix">{{$day}}</h6> 
@foreach($schedule_days as $item)
    <div class="blog-list clearfix">
        <div class="item-content">
            <a class="post-date">{{$item->from_date->format('H:i')}}
                    - {{$item->to_date->format('H:i')}}</a> 
                    <h6>{{$item->title}}</h6>
            <div class="clearfix social-links-text ul-li-right">
                <ul>
                <li>{{$item->professor}}</li>
                <li>{{$item->formatted_type}}</li>
                <li>{{$item->location}}</li>
                </ul>
            </div>
        </div>
    </div>
@endforeach
</div>
