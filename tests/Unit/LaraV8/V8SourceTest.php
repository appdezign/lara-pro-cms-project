<?php

namespace Tests\Unit\LaraV8;

use App\Console\Commands\LaraV8\Support\V8Source;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class V8SourceTest extends TestCase
{
    /**
     * @return array<string, array{string, ?string, ?string}>
     */
    public static function contentTables(): array
    {
        return [
            'page' => ['page', 'page', 'lara_content_pages'],
            'entity' => ['blog', 'entity', 'lara_content_blogs'],
            'entity with irregular plural' => ['gallery', 'entity', 'lara_content_galleries'],
            'block' => ['larawidget', 'block', 'lara_blocks_larawidgets'],
            'form' => ['contactform', 'form', 'lara_form_contactforms'],
            'system screen' => ['menuitem', 'menu', null],
            'no group' => ['tag', null, null],
        ];
    }

    #[DataProvider('contentTables')]
    public function test_the_content_table_follows_the_v8_naming(string $entityKey, ?string $groupKey, ?string $table): void
    {
        $this->assertSame($table, (new V8Source)->contentTable($entityKey, $groupKey));
    }

    public function test_only_pages_blocks_forms_and_entities_are_content_groups(): void
    {
        $source = new V8Source;

        foreach (['page', 'block', 'form', 'entity'] as $group) {
            $this->assertTrue($source->isContentGroup($group), $group);
        }

        foreach (['menu', 'tag', 'auth', 'builder', 'tools', 'seo', null] as $group) {
            $this->assertFalse($source->isContentGroup($group), (string) $group);
        }
    }

    public function test_v8_empty_values_include_the_string_null(): void
    {
        $this->assertTrue(V8Source::isEmpty(null));
        $this->assertTrue(V8Source::isEmpty(''));
        $this->assertTrue(V8Source::isEmpty('NULL'));

        $this->assertFalse(V8Source::isEmpty('type'));
        $this->assertFalse(V8Source::isEmpty('0'));
        $this->assertFalse(V8Source::isEmpty(0));
    }
}
