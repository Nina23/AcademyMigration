<div class="items-navigator row mt-30">
    <div class="other-post-wrap back-part col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <a class="other-post items-navigator-back" href="{{ url($page->uri($lang)) }}">
            ← @lang('Back')
        </a>
    </div>
    <div class="next-part col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div class="other-post-wrap clearfix">
            <div class="row no-gutter">
                <a class="other-post col-lg-6 col-md-6 col-sm-6 col-xs-6 @if (!$prev = $module::prev($model))disabled @endif"
                    href="@if ($prev){{ route($lang.'::'.Str::singular($model->getTable()), $prev->slug) }}@endif">
                    ← @lang('Previous')
                </a>
                <a class="other-post col-lg-6 col-md-6 col-sm-6 col-xs-6 @if (!$next = $module::next($model))disabled @endif"
                    href="@if ($next){{ route($lang.'::'.Str::singular($model->getTable()), $next->slug) }}@endif">
                    @lang('Next') →
                </a>
            </div>
        </div>
    </div>
</div>
