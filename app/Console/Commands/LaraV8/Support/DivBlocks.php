<?php

namespace App\Console\Commands\LaraV8\Support;

/**
 * v8's TinyMCE let editors build text from plain <div> blocks (one line per div, `<div>&nbsp;</div>`
 * as a spacer). The v10 rich editor (Filament, TipTap) has no plain div node: saving such a record
 * unwraps the divs, so their lines run together (hwn 2026-11-27: "Mark PitTelefoon: …"). They become
 * paragraphs at import, so the first save keeps the lines (user decision 2026-11-27):
 *
 *   - consecutive plain divs (no class, id or data-* attribute) with inline content and the same
 *     attributes become one <p> with the first line's attributes, the lines joined by <br /> (v8
 *     showed them without a gap; a <p> per line would add the paragraph margin between them).
 *     `text-align: left` and `justify` count as no alignment here (the same for short lines; hwn
 *     mixes them with plain divs); another alignment starts a new paragraph;
 *   - a spacer div (only spaces, &nbsp; or <br>) right after such lines closes the paragraph and is
 *     dropped (the paragraph margin takes its place); elsewhere it becomes <p>&nbsp;</p>;
 *   - a plain div around block elements (p, div, lists, tables, headings, …) is unwrapped: a <p>
 *     cannot hold blocks, and the editor would drop the div anyway.
 *
 * Divs with a class, id or data-* attribute (the rich editor grids from ColumnShortcodes, embeds)
 * stay. Only the div tags change; the rest of the HTML keeps its bytes.
 */
class DivBlocks
{
    private const BLOCKS = 'p|div|ul|ol|table|h[1-6]|blockquote|pre|figure|hr|section|article|form|iframe|address|dl';

    private const EMPTY = '/^(?:\s|&nbsp;|\x{00A0}|<br\s*\/?>)*$/u';

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
        /** @var list<array{open: int, openLength: int, close: int, closeLength: int, attributes: string, spacer: bool}> $lines */
        $lines = [];
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

            $changed++;

            if ($open['blocks']) {
                $edits[$open['offset']] = [$open['length'], ''];
                $edits[$offset] = [strlen($whole), ''];

                continue;
            }

            $inner = substr($html, $open['offset'] + $open['length'], $offset - $open['offset'] - $open['length']);
            $lines[] = [
                'open' => $open['offset'],
                'openLength' => $open['length'],
                'close' => $offset,
                'closeLength' => strlen($whole),
                'attributes' => $open['attributes'],
                'spacer' => preg_match(self::EMPTY, $inner) === 1,
                // a line that already ends in a break (before closing inline tags): v8 showed no
                // empty line after it at the end of its div, so joining adds no second break
                'endsWithBreak' => preg_match('/<br\s*\/?>\s*(?:<\/[a-z][a-z0-9]*>\s*)*$/i', $inner) === 1,
            ];
        }

        if ($stack !== [] || $changed === 0) {
            // unbalanced divs: leave the HTML as it is
            return [$html, 0];
        }

        usort($lines, fn (array $a, array $b): int => $a['open'] <=> $b['open']);
        $previous = null;

        foreach ($lines as $line) {
            $adjacent = $previous !== null
                && trim(substr($html, $previous['close'] + $previous['closeLength'], $line['open'] - $previous['close'] - $previous['closeLength'])) === '';

            if ($line['spacer'] && $adjacent && ! $previous['spacer']) {
                // ends the paragraph of the lines before it
                $edits[$line['open']] = [$line['close'] + $line['closeLength'] - $line['open'], ''];
                $line['spacer'] = true;
            } elseif (! $line['spacer'] && $adjacent && ! $previous['spacer'] && self::key($previous['attributes']) === self::key($line['attributes'])) {
                // the next line of the same paragraph
                $edits[$previous['close']] = [$previous['closeLength'], $previous['endsWithBreak'] ? '' : '<br />'];
                $edits[$line['open']] = [$line['openLength'], ''];
                $edits[$line['close']] = [$line['closeLength'], '</p>'];
            } else {
                $edits[$line['open']] = [$line['openLength'], '<p'.$line['attributes'].'>'];
                $edits[$line['close']] = [$line['closeLength'], '</p>'];
            }

            $previous = $line;
        }

        krsort($edits);

        foreach ($edits as $offset => [$length, $replacement]) {
            $html = substr_replace($html, $replacement, $offset, $length);
        }

        return [$html, $changed];
    }

    /** The attributes that decide whether two lines share a paragraph. */
    private static function key(string $attributes): string
    {
        $key = preg_replace('/text-align:\s*(?:left|justify)\s*;?/i', '', $attributes);

        return trim((string) preg_replace('/\sstyle\s*=\s*"\s*"/i', '', (string) $key));
    }
}
