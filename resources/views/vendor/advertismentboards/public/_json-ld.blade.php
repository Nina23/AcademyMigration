{{--
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "",
    "name": "{{ $advertismentboard->title }}",
    "description": "{{ $advertismentboard->summary !== '' ? $advertismentboard->summary : strip_tags($advertismentboard->body) }}",
    "image": [
        "{{ $advertismentboard->present()->image() }}"
    ],
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{{ $advertismentboard->uri() }}"
    }
}
</script>
--}}
