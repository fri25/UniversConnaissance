<?php

namespace Tests\Unit;

use App\Support\RichText;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RichTextTest extends TestCase
{
    public function test_keeps_formatting(): void
    {
        $html = '<h2>Titre</h2><p><strong>gras</strong> <em>italique</em> <u>souligné</u> <s>barré</s></p>'
            .'<p style="text-align: center"><span style="color: #e60000; background-color: rgb(255, 255, 0); font-size: 28px">mis en avant</span></p>'
            .'<ul><li>un</li><li>deux</li></ul><ol><li>premier</li></ol><blockquote>citation</blockquote>';

        $this->assertSame($html, RichText::sanitize($html));
    }

    public function test_removes_scripts_and_event_handlers(): void
    {
        $clean = RichText::sanitize('<p onclick="steal()">Bonjour<script>alert(1)</script></p><img src="https://exemple.com/a.png" onerror="alert(2)"><iframe src="https://evil.test"></iframe><style>body{display:none}</style>');

        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('alert', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('iframe', $clean);
        $this->assertStringNotContainsString('display', $clean);
        $this->assertStringContainsString('<p>Bonjour</p>', $clean);
        $this->assertStringContainsString('src="https://exemple.com/a.png"', $clean);
    }

    public function test_blocks_dangerous_links_and_styles(): void
    {
        $clean = RichText::sanitize('<p><a href="javascript:alert(1)">x</a> <a href="https://ok.test">ok</a> <span style="position: fixed; background-image: url(https://evil.test/x); color: red">t</span></p>');

        $this->assertStringNotContainsString('javascript', $clean);
        $this->assertStringContainsString('<a href="https://ok.test" rel="noopener nofollow" target="_blank">ok</a>', $clean);
        $this->assertStringNotContainsString('position', $clean);
        $this->assertStringNotContainsString('url(', $clean);
        $this->assertStringContainsString('style="color: red"', $clean);
    }

    public function test_unknown_tags_are_unwrapped_and_empty_content_is_null(): void
    {
        $this->assertSame('<p>texte <strong>gras</strong></p>', RichText::sanitize('<div><p>texte <font face="x"><strong>gras</strong></font></p></div>'));
        $this->assertNull(RichText::sanitize('<p><br></p>'));
        $this->assertNull(RichText::sanitize(null));
    }

    public function test_pasted_base64_images_are_stored_as_files(): void
    {
        Storage::fake('public');
        $png = base64_encode(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

        $clean = RichText::sanitize('<p><img src="data:image/png;base64,'.$png.'"></p>');

        $this->assertStringNotContainsString('base64', $clean);
        $this->assertStringContainsString('/storage/descriptions/', $clean);
        $this->assertCount(1, Storage::disk('public')->files('descriptions'));

        // Faux contenu image : rejeté.
        $this->assertNull(RichText::sanitize('<p><img src="data:image/png;base64,'.base64_encode('<?php echo 1;').'"></p>'));
    }

    public function test_non_breaking_spaces_are_normalised(): void
    {
        $this->assertSame('<p>un mot puis un autre</p>', RichText::sanitize("<p>un&nbsp;mot\u{00A0}puis un autre</p>"));
    }
}
