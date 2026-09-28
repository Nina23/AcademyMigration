{{--
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "",
    "name": "{{ $biography->title }}",
    "description": "{{ $biography->summary !== '' ? $biography->summary : strip_tags($biography->body) }}",
    "image": [
        "{{ $biography->present()->image() }}"
    ],
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{{ $biography->uri() }}"
    }
}
</script>
--}}
