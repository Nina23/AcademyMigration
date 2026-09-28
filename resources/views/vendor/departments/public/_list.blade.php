<ul class="department-list-list">
    @foreach ($items as $department)
    @include('departments::public._list-item')
    @endforeach
</ul>
