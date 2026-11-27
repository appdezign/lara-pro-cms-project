<?php

namespace Tests\Unit\LaraV8;

use App\Console\Commands\LaraV8\Support\DivBlocks;
use PHPUnit\Framework\TestCase;

class DivBlocksTest extends TestCase
{
    public function test_plain_divs_with_inline_content_become_paragraphs(): void
    {
        $html = "<p>Intro<br />tekst</p>\n<div><strong>Gastheer<br /></strong>Mark Pit</div>\n<div>Telefoon: 06-27400694&nbsp;</div>\n<div>&nbsp;</div>";

        [$converted, $changed] = DivBlocks::convert($html);

        $this->assertSame("<p>Intro<br />tekst</p>\n<p><strong>Gastheer<br /></strong>Mark Pit</p>\n<p>Telefoon: 06-27400694&nbsp;</p>\n<p>&nbsp;</p>", $converted);
        $this->assertSame(3, $changed);
    }

    public function test_a_paragraph_keeps_the_div_style(): void
    {
        [$converted] = DivBlocks::convert('<div style="text-align: left;">Anouk Stulp</div>');

        $this->assertSame('<p style="text-align: left;">Anouk Stulp</p>', $converted);
    }

    public function test_a_plain_div_around_blocks_is_unwrapped(): void
    {
        [$converted, $changed] = DivBlocks::convert('<div><div>Regel</div><ul><li>Punt</li></ul></div>');

        $this->assertSame('<p>Regel</p><ul><li>Punt</li></ul>', $converted);
        $this->assertSame(2, $changed);
    }

    public function test_divs_with_a_class_id_or_data_attribute_stay(): void
    {
        $grid = '<div class="grid-layout" data-cols="2"><div class="grid-layout-col" data-col-span="1"><div>Links</div></div></div>';

        [$converted, $changed] = DivBlocks::convert($grid.'<div id="kaart"></div><div data-embed="x">Film</div>');

        $this->assertSame('<div class="grid-layout" data-cols="2"><div class="grid-layout-col" data-col-span="1"><p>Links</p></div></div><div id="kaart"></div><div data-embed="x">Film</div>', $converted);
        $this->assertSame(1, $changed);
    }

    public function test_html_without_plain_divs_or_with_unbalanced_divs_is_left_alone(): void
    {
        $this->assertFalse(DivBlocks::has('<p>Tekst</p>'));
        $this->assertFalse(DivBlocks::has('<div class="x">Tekst</div>'));
        $this->assertFalse(DivBlocks::has(null));
        $this->assertSame(['<div>Open', 0], DivBlocks::convert('<div>Open'));
        $this->assertTrue(DivBlocks::has('<DIV>Tekst</DIV>'));
    }
}
