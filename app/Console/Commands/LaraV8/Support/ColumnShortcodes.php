<?php

namespace App\Console\Commands\LaraV8\Support;

/**
 * v8 editors made responsive columns with shortcodes in the HTML:
 * [kolom_1van2]…[/kolom_1van2][kolom_2van2]…[/kolom_2van2]. The v8 front turned them into a
 * Bootstrap row (FrontObjectTrait::processShortcodes(), breakpoint `md`); v10 has no shortcodes.
 * They become the grid of the Filament rich editor (user decision 2026-11-23, doormode), so the
 * columns stay content that editors can change:
 *
 *   <div class="grid-layout" data-cols="N" data-from-breakpoint="md" style="--cols: …">
 *     <div class="grid-layout-col" data-col-span="S" style="--col-span: …">…</div>
 *   </div>
 *
 * [kolom_XvanN]: N columns; X lists the column numbers it covers, so its span is the number of
 * digits (v8: 1/2/3/4, 12 and 23 in 3, 123 and 234 in 4). A grid starts at a column whose X
 * starts with 1 and ends after the one whose X ends with N. A marker alone in a paragraph
 * (also with spaces or &nbsp;, which v8 left in place) loses the paragraph. When a set is not
 * complete (an opening marker without its closing one), its markers are removed, as v8 did.
 */
class ColumnShortcodes
{
    /** v8 config lara-front.shortcode.bootstrap_breakpoint */
    public const BREAKPOINT = 'md';

    private const MARKER = '\[\/?kolom_(\d+)van(\d)\]';

    public static function has(?string $html): bool
    {
        return is_string($html) && preg_match('/'.self::MARKER.'/', $html) === 1;
    }

    /**
     * @return array{0: string, 1: int} the converted HTML and the number of grids built
     */
    public static function convert(string $html): array
    {
        if (! self::has($html)) {
            return [$html, 0];
        }

        // a marker alone in a paragraph: drop the paragraph
        $html = preg_replace('/<p>(?:\s|&nbsp;|\x{00A0})*('.self::MARKER.')(?:\s|&nbsp;|\x{00A0})*<\/p>/u', '$1', $html);

        // incomplete sets (per column count) are removed, as v8 did
        preg_match_all('/\[kolom_(\d+)van(\d)\]/', $html, $open, PREG_SET_ORDER);
        preg_match_all('/\[\/kolom_(\d+)van(\d)\]/', $html, $close, PREG_SET_ORDER);
        $opened = array_map(fn (array $m): string => $m[1].'van'.$m[2], $open);
        $closed = array_map(fn (array $m): string => $m[1].'van'.$m[2], $close);

        foreach (array_unique([...$opened, ...$closed]) as $marker) {
            if (! in_array($marker, $opened, true) || ! in_array($marker, $closed, true)) {
                $count = substr($marker, strrpos($marker, 'van') + 3);
                $html = preg_replace('/\[\/?kolom_\d+van'.$count.'\]/', '', $html);
            }
        }

        $grids = 0;

        $html = preg_replace_callback('/'.self::MARKER.'/', function (array $m) use (&$grids): string {
            [$marker, $columns, $count] = $m;
            $span = strlen($columns);

            if (str_starts_with($marker, '[/')) {
                return str_ends_with($columns, $count) ? '</div></div>' : '</div>';
            }

            $column = '<div class="grid-layout-col" data-col-span="'.$span.'" style="--col-span: span '.$span.' / span '.$span.'">';

            if (str_starts_with($columns, '1')) {
                $grids++;

                return '<div class="grid-layout" data-cols="'.$count.'" data-from-breakpoint="'.self::BREAKPOINT.'" style="--cols: repeat('.$count.', minmax(0, 1fr))">'.$column;
            }

            return $column;
        }, $html);

        return [$html, $grids];
    }
}
