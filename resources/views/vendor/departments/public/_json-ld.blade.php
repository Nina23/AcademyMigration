{{--
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "",
    "name": "{{ $department->title }}",
    "description": "{{ $department->summary !== '' ? $department->summary : strip_tags($department->body) }}",
    "image": [
        "{{ $department->present()->image() }}"
    ],
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{{ $department->uri() }}"
    }
}
</script>
--}}
