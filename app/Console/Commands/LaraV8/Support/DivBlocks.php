<?php

namespace App\Console\Commands\LaraV8\Support;

/**
 * v8's TinyMCE let editors build text from plain <div> blocks (one line per div, `<div>&nbsp;</div>`
 * as a spacer). The v10 rich editor (Filament, TipTap) has no plain div node: saving such a record
 * unwraps the divs, so their lines run together (hwn 2026-11-27: "Mark PitTelefoon: …"). They become
 * paragraphs at import, so the first save keeps the lines (user decision 2026-11-27):
 *
 *   - a plain <div> (no class, id or data-* attribute) with inline content → <p> with the same
 *     attributes (e.g. `style="text-align: left;"`);
 *   - a plain <div> around block elements (p, div, lists, tables, headings, …) is unwrapped: a <p>
 *     cannot hold blocks, and the editor would drop the div anyway.
 *
 * Divs with a class, id or data-* attribute (the rich editor grids from ColumnShortcodes, embeds)
 * stay. Only the div tags change; the rest of the HTML keeps its bytes.
 */
class DivBlocks
{
    private const BLOCKS = 'p|div|ul|ol|table|h[1-6]|blockquote|pre|figure|hr|section|article|form|iframe|address|dl';

    public static function has(?string $html): bool
    {
        return is_string($html) && preg_match('/<div(?:\s[^>]*)?>/i', $html) === 1 && self::convert($html)[1] > 0;
    }

    /**
     * @return array{0: string, 1: int} the converted HTML and the number of plain divs changed
     */
    public static function convert(string $html): array
    {
        if (preg_match_all('/<(\/?)('.self::BLOCKS.')\b([^>]*)>/i', $html, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === 0) {
            return [$html, 0];
        }

        /** @var list<array{plain: bool, offset: int, length: int, attributes: string, blocks: bool}> $stack */
        $stack = [];
        /** @var array<int, array{0: int, 1: string}> $edits offset => [length, replacement] */
        $edits = [];
        $changed = 0;

        foreach ($tags as $tag) {
            [$whole, $offset] = $tag[0];
            $closing = $tag[1][0] === '/';
            $name = strtolower($tag[2][0]);
            $attributes = $tag[3][0];

            if (! $closing && $stack !== []) {
                $stack[array_key_last($stack)]['blocks'] = true;
            }

            if ($name !== 'div') {
                continue;
            }

            if (! $closing) {
                $stack[] = [
                    'plain' => preg_match('/\s(?:class|id|data-[\w-]+)\s*=/i', $attributes) !== 1,
                    'offset' => $offset,
                    'length' => strlen($whole),
                    'attributes' => rtrim($attributes, " \t\n\r/"),
                    'blocks' => false,
                ];

                continue;
            }

            $open = array_pop($stack);

            if ($open === null || ! $open['plain']) {
                continue;
            }

            $edits[$open['offset']] = [$open['length'], $open['blocks'] ? '' : '<p'.$open['attributes'].'>'];
            $edits[$offset] = [strlen($whole), $open['blocks'] ? '' : '</p>'];
            $changed++;
        }

        if ($stack !== [] || $changed === 0) {
            // unbalanced divs: leave the HTML as it is
            return [$html, 0];
        }

        krsort($edits);

        foreach ($edits as $offset => [$length, $replacement]) {
            $html = substr_replace($html, $replacement, $offset, $length);
        }

        return [$html, $changed];
    }
}
