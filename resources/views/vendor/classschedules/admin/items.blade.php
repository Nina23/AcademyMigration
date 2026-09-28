
<table class="table item-list-table">
    <thead>
    <tr>
        <th>{{__('Number')}}</th>

        <th>{{__('Time from ')}}</th>
        <th>{{__('Time to')}}</th>
        <th>{{__('Subject')}}</th>
        <th>{{__('Type')}}</th>
        <th>{{__('Location')}}</th>
        <th>{{__('Professor')}}</th>
        <th>{{__('Action')}}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($items as $item)
    <tr>
    <td>{{ $item->id }}</td>
    <td>
        <span>{{ $item->from_date->format('H:i') }}</span>
    </td>
    <td>
        <span>{{ $item->to_date->format('H:i') }}</span>
    </td>
    <td>
        <span>{{ $item->title }}</span>
    </td>
    <td>
        <span>{{ $item->formatted_type }}</span>
    </td>

    <td>
        <span>{{ $item->location }}</span>
    </td>

    <td>
        <span>{{ $item->professor }}</span>
    </td>
    <td>
        <a id="edit-{{$item->id}}" class="btn btn-xs btn-light mr-2" data-route="{{ route('admin::edit-class-schedule-item-confirm', $item) }}" data-target="#modaledit" data-toggle="modal"
           data-id="{{ $item->id }}">{{__('Edit')}}</a>

        <a id="delete-{{$item->id}}" class="btn btn-xs btn-primary mr-2" data-route="{{ route('admin::delete-class-schedule-item-confirm', $item->id) }}" data-toggle="modal" data-target="#modaldelete">
           {{__('Delete')}}  </a>
    </td>
</tr>
    @endforeach
    </tbody>
</table>

<div id="modaledit" class="modal fade" role="dialog">
<div class="modal-dialog">
        <div class="modal-content" id="modal-content-edit">
        </div>
    </div>
</div>

<div class="modal fade" id="modaldelete" tabindex="-1" role="dialog" aria-labelledby="user_delete_confirm_title" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="modal-content-delete">
        </div>
    </div>
</div>

@push('js')
    <script type="text/javascript">
        $(function () {
        $('body').on('hidden.bs.modal', '.modal', function () {
            $(this).removeData('bs.modal');
        });
    });

    $('a[id^="delete-"]').on('click', function (e) {
    var route_name =$(this).attr("data-route");
        $.ajax({
            url: route_name,
            type: 'get',
            datatype: 'html',
        }).done(function(data) {
            $('#modal-content-delete').empty().html(data);
        }).fail(function(jqXHR, ajaxOptions, thrownError) {
            alert('No response from server');
        });
    });

    $('a[id^="edit-"]').on('click', function (e) {
    var route_name =$(this).attr("data-route");
        $.ajax({
            url: route_name,
            type: 'get',
            datatype: 'html',
        }).done(function(data) {
            $('#modal-content-edit').empty().html(data);
        }).fail(function(jqXHR, ajaxOptions, thrownError) {
            alert('No response from server');
        });
    });




    </script>
@endpush
