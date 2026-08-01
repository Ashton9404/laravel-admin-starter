<?php

namespace Tests\Feature\Products;

use App\Support\RichText;
use Tests\TestCase;

class RichTextSanitizerTest extends TestCase
{
    private RichText $richText;

    protected function setUp(): void
    {
        parent::setUp();

        $this->richText = new RichText;
    }

    public function test_it_keeps_the_formatting_the_editor_produces(): void
    {
        $html = '<h2>Title</h2><p>Some <strong>bold</strong> and <em>italic</em> text.</p>'
            .'<ul><li>One</li><li>Two</li></ul><blockquote><p>Quoted</p></blockquote>';

        $this->assertSame($html, $this->richText->sanitize($html));
    }

    public function test_it_strips_script_tags(): void
    {
        $clean = $this->richText->sanitize('<p>Hello</p><script>alert(1)</script>');

        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringContainsString('<p>Hello</p>', $clean);
    }

    public function test_it_strips_event_handler_attributes(): void
    {
        $clean = $this->richText->sanitize('<img src="https://example.com/x.png" onerror="alert(1)">');

        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringContainsString('src="https://example.com/x.png"', $clean);
    }

    public function test_it_strips_javascript_urls(): void
    {
        $clean = $this->richText->sanitize('<a href="javascript:alert(1)">click</a>');

        $this->assertStringNotContainsString('javascript', $clean);
    }

    public function test_it_forces_rel_on_links(): void
    {
        $clean = $this->richText->sanitize('<a href="https://example.com">link</a>');

        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
    }

    public function test_it_drops_elements_outside_the_allow_list(): void
    {
        $clean = $this->richText->sanitize('<p>Kept</p><iframe src="https://evil.test"></iframe><form></form>');

        $this->assertStringNotContainsString('iframe', $clean);
        $this->assertStringNotContainsString('form', $clean);
        $this->assertStringContainsString('Kept', $clean);
    }

    public function test_an_untouched_editor_stores_null_rather_than_an_empty_paragraph(): void
    {
        $this->assertNull($this->richText->sanitize('<p></p>'));
        $this->assertNull($this->richText->sanitize(''));
        $this->assertNull($this->richText->sanitize(null));
    }
}
