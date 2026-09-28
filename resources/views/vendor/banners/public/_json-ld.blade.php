{{--
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "",
    "name": "{{ $banner->title }}",
    "description": "{{ $banner->summary !== '' ? $banner->summary : strip_tags($banner->body) }}",
    "image": [
        "{{ $banner->present()->image() }}"
    ],
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{{ $banner->uri() }}"
    }
}
</script>
--}}
