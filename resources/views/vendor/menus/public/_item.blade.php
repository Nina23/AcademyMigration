
<li <?php if($menulink->items->count() > 0) echo 'class="menu-item-has-child"';?> >



    <a  <?php if($menulink->items->count() == 0) echo "href='".url($menulink->href)."'"; else echo 'href="#home-'.$menulink->id.'" data-toggle="collapse" aria-expanded="false"';?>>
        @if ($menulink->image !== null)
            <img src="{{ $menulink->present()->image }}" width="32" height="32">
        @endif
        {{ $menulink->title }}
    </a>
    @if ($menulink->items->count() > 0)
        <ul  class="submenu collapse" id="home-<?php echo $menulink->id;?>" data-parent="#menu-list">
            @foreach ($menulink->items as $menulink)
                @include('menus::public._item', ['menulink' => $menulink])
            @endforeach
        </ul>
    @endif
</li>

