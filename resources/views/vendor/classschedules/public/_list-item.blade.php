    <h5 class=" clearfix">{{$class_schedule->announcementDepartment->formatted_program}}, {{$class_schedule->announcementDepartment->title}}, {{$class_schedule->formatted_year}} {{__('Year')}}</h5>
    <p class="mb-30">
        {{$class_schedule->summary}}
    </p>
  
    @includeWhen($class_schedule->classScheduleItems->where('day_id', 1)->count() > 0, 'classschedules::public.schedule_days', ['day'=>__('Ponedeljak'),'schedule_days' => $class_schedule->classScheduleItems->where('day_id', 1)])
    @includeWhen($class_schedule->classScheduleItems->where('day_id', 2)->count() > 0, 'classschedules::public.schedule_days', ['day'=>__('Utorak'),'schedule_days' => $class_schedule->classScheduleItems->where('day_id', 2)])
    @includeWhen($class_schedule->classScheduleItems->where('day_id', 3)->count() > 0, 'classschedules::public.schedule_days', ['day'=>__('Srijeda'),'schedule_days' => $class_schedule->classScheduleItems->where('day_id', 3)])
    @includeWhen($class_schedule->classScheduleItems->where('day_id', 4)->count() > 0, 'classschedules::public.schedule_days', ['day'=>__('Cetvrtak'),'schedule_days' => $class_schedule->classScheduleItems->where('day_id', 4)])
    @includeWhen($class_schedule->classScheduleItems->where('day_id', 5)->count() > 0, 'classschedules::public.schedule_days', ['day'=>__('Petak'),'schedule_days' => $class_schedule->classScheduleItems->where('day_id', 5)])
    @includeWhen($class_schedule->classScheduleItems->where('day_id', 6)->count() > 0, 'classschedules::public.schedule_days', ['day'=>__('Subota'),'schedule_days' => $class_schedule->classScheduleItems->where('day_id', 6)])
