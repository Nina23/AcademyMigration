<?php

namespace Tests\Feature\Views;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * The JSON-LD partials (resources/views/vendor/<module>/public/_json-ld and
 * _itemlist-json-ld) write the literal schema.org key "@context". Laravel 12
 * adds a Blade @context directive; the text must stay text.
 */
class JsonLdContextTest extends TestCase
{
    public function test_at_context_is_not_a_blade_directive()
    {
        $this->assertSame('"@context":"https://schema.org",', Blade::compileString('"@context":"https://schema.org",'));
        $this->assertSame('"@context": "http://schema.org",', Blade::compileString('"@context": "http://schema.org",'));
    }

    public function test_itemlist_json_ld_partial_renders_the_context_key()
    {
        $html = view('news::public._itemlist-json-ld', ['items' => collect()])->render();

        $this->assertStringContainsString('"@context":"https://schema.org",', $html);
        $this->assertStringContainsString('"@type":"ItemList",', $html);
    }
}
