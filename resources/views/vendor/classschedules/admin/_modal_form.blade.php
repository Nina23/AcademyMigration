

        <div class="modal-header">
            <h4 class="modal-title">{{__('Edit')}}</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        {!!BootForm::open()->action(route('admin::update-class-schedule-item', $item))->put() !!}
        {!! BootForm::bind($item) !!}

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
                    
                    {!! BootForm::hidden(__('Class'), 'class_schedule_id',1) !!}
                    
                </div>
            </div>
            <div class="row">
                    <div class="col-sm-6">
                    {!! BootForm::select(__('Type'), 'type', ['' => ''] + $types, null, ['class' => 'form-control'])->addClass('custom-select') !!}

                    </div>
                    <div class="col-sm-6">
                        {!! BootForm::hidden(__('Day'), 'day_id') !!}
                    </div>
                </div>
        </div>
        <div class="modal-footer">
            {!! BootForm::submit('Submit') !!}
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
        {!! BootForm::close() !!}

