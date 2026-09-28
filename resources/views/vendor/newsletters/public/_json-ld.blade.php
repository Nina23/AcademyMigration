{{--
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "",
    "name": "{{ $newsletter->title }}",
    "description": "{{ $newsletter->summary !== '' ? $newsletter->summary : strip_tags($newsletter->body) }}",
    "image": [
        "{{ $newsletter->present()->image() }}"
    ],
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{{ $newsletter->uri() }}"
    }
}
</script>
--}}
