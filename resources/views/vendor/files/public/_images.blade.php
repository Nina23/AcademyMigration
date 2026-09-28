@if ($model->images->count() > 0)

<div class="blog-section clearfix sec-ptb-60" id="blog-section">
    <div class="container">
        <div class="mb-60 section-title text-center aos-init aos-animate" data-aos="fade-up" data-aos-delay="100">
            <h2>{{__('gallery-title')}}</h2>

            <p>{{__('gallery')}}</p>
        </div>

        <div class="row sec-ptb-30">
            @foreach ($model->images as $image)
            <div class="col-lg-3 col-md-3 col-sm-4 col-xs-6 element-item drama" data-category="drama">
                <div class=" bg-white" data-aos="fade-up" data-aos-delay="100">
                    <a class="image-list-item-link"
                        href="{!! $image->present()->image(1200, 1200, ['resize']) !!}"
                        data-caption="{{ $image->alt_attribute }}"
                        data-fancybox="{{ $model->slug ? : 'group' }}"
                        data-options='{ "buttons": ["close"], "infobar": false }'
                    >
                        <img class="image-list-item-image" src="{!! $image->present()->image(1200, 1200) !!}" alt="{{ $image->alt_attribute }}">
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
