<?php

namespace Tests\Unit\LaraV8;

use App\Console\Commands\LaraV8\Support\ColumnShortcodes;
use PHPUnit\Framework\TestCase;

class ColumnShortcodesTest extends TestCase
{
    private const GRID2 = '<div class="grid-layout" data-cols="2" data-from-breakpoint="md" style="--cols: repeat(2, minmax(0, 1fr))">';

    private const GRID3 = '<div class="grid-layout" data-cols="3" data-from-breakpoint="md" style="--cols: repeat(3, minmax(0, 1fr))">';

    private const COL1 = '<div class="grid-layout-col" data-col-span="1" style="--col-span: span 1 / span 1">';

    private const COL2 = '<div class="grid-layout-col" data-col-span="2" style="--col-span: span 2 / span 2">';

    public function test_two_columns_become_a_rich_editor_grid_and_lose_their_paragraphs(): void
    {
        // doormode contact page 1059: the last closing marker shares its paragraph with an &nbsp;
        $html = "<p>[kolom_1van2]</p>\n<p>Adres</p>\n<p>[/kolom_1van2]</p>\n<p>[kolom_2van2]</p>\n<p>Mail</p>\n<p>&nbsp;[/kolom_2van2]</p>";

        [$converted, $grids] = ColumnShortcodes::convert($html);

        $this->assertSame(1, $grids);
        $this->assertSame(self::GRID2.self::COL1."\n<p>Adres</p>\n</div>\n".self::COL1."\n<p>Mail</p>\n</div></div>", $converted);
    }

    public function test_a_wide_column_spans_the_columns_it_lists(): void
    {
        [$converted] = ColumnShortcodes::convert('[kolom_12van3]A[/kolom_12van3][kolom_3van3]B[/kolom_3van3]');

        $this->assertSame(self::GRID3.self::COL2.'A</div>'.self::COL1.'B</div></div>', $converted);
    }

    public function test_an_incomplete_set_is_removed(): void
    {
        [$converted, $grids] = ColumnShortcodes::convert('<p>[kolom_1van2]</p><p>Tekst</p>');

        $this->assertSame(0, $grids);
        $this->assertSame('<p>Tekst</p>', $converted);
    }

    public function test_html_without_shortcodes_is_left_alone(): void
    {
        $this->assertFalse(ColumnShortcodes::has('<p>[geen shortcode]</p>'));
        $this->assertSame(['<p>Tekst</p>', 0], ColumnShortcodes::convert('<p>Tekst</p>'));
    }
}
