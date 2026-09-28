@if ($model->documents->count() > 0)

<div class="bg-default-light clearfix sec-ptb-100 service-section text-center" id="service-section">
    <div class="container">
        <div class="mb-60 section-title text-center aos-init aos-animate" data-aos="fade-up" data-aos-delay="100">
            <h2>{{__('Documents')}}</h2>

            <p>{{__('documents-message')}}</p>
        </div>

        <div class="row sec-ptb-30">
            @foreach ($model->documents as $document)
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="plr-70 document-default" data-aos="fade-up" data-aos-delay="100">
                <img alt="image_not_found" src="/images/academy/icons/dokumentacija.svg" />
                <span>{{ $document->name }} </span>
                <a href="{{ Storage::url($document->path) }}" target="_blank" download> <i class="fas fa-file-download"></i> </a></div>
            </div>
            @endforeach
        </div>
    </div>
</div>


<!-- <div class="bg-default-light clearfix sec-ptb-100 service-section text-center" id="service-section">
    <div class="container">
        <div class="mb-60 section-title text-center aos-init aos-animate" data-aos="fade-up" data-aos-delay="100">
            <h2>{{__('Documents')}}</h2>

            <p>{{__('documents-message')}}</p>
        </div>

        <div class="row sec-ptb-30">
            @foreach ($model->documents as $document)
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="plr-70 service-default" data-aos="fade-up" data-aos-delay="100"><img alt="image_not_found" src="/images/academy/icons/dokumentacija.svg" />
                <h5>{{__("Document")}}</h5>

                <p>{{ $document->name }}</p>
                <a href="{{ Storage::temporaryUrl($document->path, now()->addMinutes(5)) }}" download><i class="fas fa-file-download"></i> {{__("Download")}}</a></div>
            </div>
            @endforeach
        </div>
    </div>
</div> -->
@endif
