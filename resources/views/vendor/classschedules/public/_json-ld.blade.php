{{--
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "",
    "name": "{{ $classschedule->title }}",
    "description": "{{ $classschedule->summary !== '' ? $classschedule->summary : strip_tags($classschedule->body) }}",
    "image": [
        "{{ $classschedule->present()->image() }}"
    ],
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{{ $classschedule->uri() }}"
    }
}
</script>
--}}
