<ul class="classschedule-list-results-list">
    @foreach ($items as $classschedule)
    <li class=" mb-30">
        <a class="classschedule-list-results-item-link" href="{{ $classschedule->uri() }}" title="{{ $classschedule->title }}">
            <span class="classschedule-list-results-item-title">{{ $classschedule->title }}</span>
        </a> 
    </li>
    @endforeach
</ul>


