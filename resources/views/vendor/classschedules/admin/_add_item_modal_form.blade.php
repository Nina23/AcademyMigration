<div class="modal-dialog modal-xl">

    <!-- Modal content-->
    <div class="modal-content">

        <div class="modal-header">
            <h4 class="modal-title">{{__('Add')}}</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        {!!BootForm::open()->action(route('admin::store-class-schedule-item'))->post() !!}

        <div class="modal-body">
            <div class="row">
                <div class="col-sm-6">
                    {!! BootForm::dateTimeLocal(__('From date'), 'from_date') !!}
                </div>
                <div class="col-sm-6">
                    {!! BootForm::dateTimeLocal(__('To date'), 'to_date') !!}
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    {!! TranslatableBootForm::text(__('Title'), 'title')!!}
                    {!! TranslatableBootForm::text(__('Professor'), 'professor') !!}
                    {!! TranslatableBootForm::text(__('Location'), 'location') !!}
                    {!! BootForm::select(__('Type'), 'type_id', ['' => ''] + $types, null, ['class' => 'form-control'])->addClass('custom-select') !!}

                    <input name="class_schedule_id" type="hidden" value="{{$model->id}}">
                    <input name="day_id" id='tour_id' type="hidden" value="1">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            {!! BootForm::submit('Submit') !!}
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        </div>
        {!! BootForm::close() !!}
    </div>
</div>

