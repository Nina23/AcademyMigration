{{--
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "",
    "name": "{{ $gallery->title }}",
    "description": "{{ $gallery->summary !== '' ? $gallery->summary : strip_tags($gallery->body) }}",
    "image": [
        "{{ $gallery->present()->image() }}"
    ],
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{{ $gallery->uri() }}"
    }
}
</script>
--}}
