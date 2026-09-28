@if ($menu = Menus::getMenu($name))
    @if ($menu->menulinks->count() > 0)
    <ul class="clearfix" role="menu">
        @foreach ($menu->menulinks as $menulink)
            @include('menus::public._item')
        @endforeach
    </ul>
    @endif
@endif

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<script>

$(".submenu").on("click", function (event) {
   event.stopPropagation();
  var target = event.currentTarget;
  $(target).children('.menu-item-has-child').children('.submenu').addClass('show');
  $(target).addClass("show");
});
</script>






