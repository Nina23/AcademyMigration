{{--
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "",
    "name": "{{ $category->title }}",
    "description": "{{ $category->summary !== '' ? $category->summary : strip_tags($category->body) }}",
    "image": [
        "{{ $category->present()->image() }}"
    ],
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{{ $category->uri() }}"
    }
}
</script>
--}}
