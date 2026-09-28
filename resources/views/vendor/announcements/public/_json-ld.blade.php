{{--
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "",
    "name": "{{ $announcement->title }}",
    "description": "{{ $announcement->summary !== '' ? $announcement->summary : strip_tags($announcement->body) }}",
    "image": [
        "{{ $announcement->present()->image() }}"
    ],
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{{ $announcement->uri() }}"
    }
}
</script>
--}}
