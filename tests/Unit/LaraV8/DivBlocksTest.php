<?php

namespace Tests\Unit\LaraV8;

use App\Console\Commands\LaraV8\Support\DivBlocks;
use PHPUnit\Framework\TestCase;

class DivBlocksTest extends TestCase
{
    public function test_consecutive_div_lines_become_one_paragraph_and_a_spacer_ends_it(): void
    {
        $html = "<p>Intro<br />tekst</p>\n<div><strong>Gastheer<br /></strong>Mark Pit</div>\n<div>Telefoon: 06-27400694&nbsp;</div>\n<div>&nbsp;</div>\n<div>Volgende</div>";

        [$converted, $changed] = DivBlocks::convert($html);

        $this->assertSame("<p>Intro<br />tekst</p>\n<p><strong>Gastheer<br /></strong>Mark Pit<br />\nTelefoon: 06-27400694&nbsp;</p>\n\n<p>Volgende</p>", $converted);
        $this->assertSame(4, $changed);
    }

    public function test_a_line_that_ends_in_a_break_is_joined_without_another_break(): void
    {
        // inventiverepair Contactgegevens: v8 showed one empty line after the telephone, not two
        $html = "<div>Haskerveen</div>\n<div><br /><strong>T: 0513 - 62 13 45<br /><br /></strong></div>\n<div>F: 0513 - 623322</div>";

        [$converted] = DivBlocks::convert($html);

        $this->assertSame("<p>Haskerveen<br />\n<br /><strong>T: 0513 - 62 13 45<br /><br /></strong>\nF: 0513 - 623322</p>", $converted);
    }

    public function test_a_spacer_that_follows_no_lines_stays_an_empty_paragraph(): void
    {
        [$converted] = DivBlocks::convert("<p>Tekst</p>\n<div>&nbsp;</div>\n<p>Meer</p>");

        $this->assertSame("<p>Tekst</p>\n<p>&nbsp;</p>\n<p>Meer</p>", $converted);
    }

    public function test_left_or_justified_lines_join_and_another_alignment_starts_a_new_paragraph(): void
    {
        [$converted] = DivBlocks::convert('<div><strong>Havenmeesters</strong></div><div style="text-align: justify;">Mark Pit</div><div style="text-align: left;">Anouk Stulp</div><div style="text-align: center;">Midden</div>');

        $this->assertSame('<p><strong>Havenmeesters</strong><br />Mark Pit<br />Anouk Stulp</p><p style="text-align: center;">Midden</p>', $converted, 'left and justify count as no alignment; center starts a new paragraph.');
    }

    public function test_lines_separated_by_other_content_are_separate_paragraphs(): void
    {
        [$converted] = DivBlocks::convert('<div>Een</div><p>Tussen</p><div>Twee</div>');

        $this->assertSame('<p>Een</p><p>Tussen</p><p>Twee</p>', $converted);
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
