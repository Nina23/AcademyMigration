<a href="{{ TypiCMS::homeUrl() }}">
    @if (TypiCMS::hasLogo())
        <img class="logo" src="{{ Storage::url('files/'.config('typicms.image'))}}" alt="{{ TypiCMS::title() }}" height="150">
    @else
        {{ TypiCMS::title() }}
    @endif
</a>
