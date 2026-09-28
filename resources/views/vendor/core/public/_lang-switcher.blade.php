@if ($enabledLocales = TypiCMS::enabledLocales() and count($enabledLocales) > 1)


    <!-- <button class="lang-switcher ul-li-left dropdown-toggle " data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="dropdownLangSwitcher">
        {{ $lang }}
    </button>
    <div class="lang-switcher-list dropdown-menu" aria-labelledby="dropdownLangSwitcher">
        @foreach ($enabledLocales as $locale)
            @if ($locale !== $lang)
                @isset($page)
                    @if ($page->isPublished($locale))
                        <a class="lang-switcher-item dropdown-item" href="{{ isset($model) ? url($model->uri($locale)) : url($page->uri($locale)) }}">{{ $locale }}</a>
                    @else
                        <a class="lang-switcher-item dropdown-item" href="{{ url('/'.$locale) }}">{{ $locale }}</a>
                    @endif
                @else
                    <a class="lang-switcher-item dropdown-item" href="{{ url('/'.$locale) }}">{{ $locale }}</a>
                @endisset
            @endif
        @endforeach
    </div> -->

    

<div class="container">
<div class="clearfix social-links-text ul-li-right" data-aos="fade-left" data-aos-delay="100">
<ul>
	<li> <a href=""  >
    @lang('languages.'.$lang.'_public')
      
    </a></li>
	<!-- @foreach ($enabledLocales as $locale) -->
    @if ($locale !== $lang)
                @isset($page)
                    @if ($page->isPublished($locale))
                        <li class="lang-item">
                            <a href="{{ isset($model) ? url($model->uri($locale)) : url($page->uri($locale)) }}">@lang('languages.'.$locale.'_public')</a>
                        </li>
                        @else
                        <li class="lang-item">
                            <a  href="{{ url('/'.$locale) }}">@lang('languages.'.$locale.'_public') </a>
                        </li>
                        @endif
                @else
                    <li class="lang-item">
                        <a href="{{ url('/'.$locale) }}">@lang('languages.'.$locale.'_public')</a>
                    </li>
                @endisset
            @endif
        <!-- @endforeach -->
</ul>
</div>
</div>

@endif




