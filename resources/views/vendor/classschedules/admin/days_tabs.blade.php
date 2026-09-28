
<div class="p-3">
    <ul class="nav nav-tabs active" id="myTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active"  data-id="1" onClick="fillData(1, {{$model->id}})" data-toggle="tab" href="#monday" role="tab" aria-controls="movies"
               aria-selected="true">{{__('Monday')}}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="2" onClick="fillData(2, {{$model->id}})" data-toggle="tab" role="tab"
               aria-controls="scheduled-movies" aria-selected="false">{{__('Tuesday')}}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="3" onClick="fillData(3, {{$model->id}})" data-toggle="tab" role="tab"
               aria-controls="scheduled-movies" aria-selected="false">{{__('Wednesday')}}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="4" onClick="fillData(4,{{$model->id}})" data-toggle="tab"  role="tab"
               aria-controls="scheduled-movies" aria-selected="false">{{__('Thursday')}}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="5" onClick="fillData(5, {{$model->id}})" data-toggle="tab" role="tab"
               aria-controls="scheduled-movies" aria-selected="false">{{__('Friday')}}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="6" onClick="fillData(6, {{$model->id}})" data-toggle="tab" role="tab"
               aria-controls="scheduled-movies" aria-selected="false">{{__('Saturday')}}</a>
        </li>
    </ul>
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="monday" role="tabpanel" aria-labelledby="movies-tab">
            <div class="btn-toolbar mb-4">
                <h5>Lista casova</h5>
                <div class="btn-group btn-group-sm ml-auto">
                    <a class="deleteProduct btn btn-sm btn-primary mr-2" data-target="#modaladd" data-toggle="modal">{{__('Add schedule item')}}</a>
                </div>
            </div>
            <div id="tag_container">
                @includeWhen($items->count() > 0, 'classschedules::admin.items', ['items' => $items])
            </div>

        </div>
        <div class="tab-pane fade" id="scheduled-movies" role="tabpanel" aria-labelledby="scheduled-movies-tab">

        </div>

    </div>
</div>
<div id="modaladd" class="modal fade" role="dialog">
    @include('classschedules::admin._add_item_modal_form')
</div>

</div>

@push('js')
    <script type="text/javascript">


        function fillData($day_id, $schedule_id){
            $('#tour_id').val($day_id);
            $.ajax(
                {
                    url: '/admin/class/schedules/item',
                    type: "get",
                    datatype: "html",
                    data: { day_id: $day_id, schedule_id: $schedule_id },
                }).done(function(data){
                $("#tag_container").empty().html(data);
            }).fail(function(jqXHR, ajaxOptions, thrownError){
                alert('No response from server');
            });
        }
        $("#modaladd").on('show.bs.modal', function(e) {
        });


    </script>
@endpush


