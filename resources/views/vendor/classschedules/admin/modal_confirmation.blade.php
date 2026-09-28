<div class="modal-header">
                <h5 class="modal-title">{{__('Do you want to delete this item?')}}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            {!! BootForm::open()->action(route('admin::delete-class-schedule-item', $id))->multipart()->role('form') !!}
            <div class="modal-body">
                <p>{{__('If you delete this item it will be removed from classschedule list.')}}</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-primary mr-2" value="true" id="exit" name="exit"
                        type="submit">{{ __('YES') }}</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
            </div>
            {!! BootForm::close() !!}