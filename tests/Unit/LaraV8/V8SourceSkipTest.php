<?php

namespace Tests\Unit\LaraV8;

use App\Console\Commands\LaraV8\Support\V8Source;
use Tests\TestCase;

/**
 * The skip list (v8-migration.skip_entities): which v8 content types a site leaves out.
 */
class V8SourceSkipTest extends TestCase
{
    public function test_the_env_value_is_a_trimmed_list_without_empty_keys(): void
    {
        putenv('LARA_V8_SKIP_ENTITIES= city, product ,,story ');

        try {
            $config = require config_path('v8-migration.php');
        } finally {
            putenv('LARA_V8_SKIP_ENTITIES');
        }

        $this->assertSame(['city', 'product', 'story'], $config['skip_entities']);
    }

    public function test_no_env_value_skips_nothing(): void
    {
        putenv('LARA_V8_SKIP_ENTITIES');

        $config = require config_path('v8-migration.php');

        $this->assertSame([], $config['skip_entities']);
    }

    public function test_core_content_types_and_empty_keys_are_never_skipped(): void
    {
        config(['v8-migration.skip_entities' => ['city', 'page', 'larawidget', 'cta', 'slider', '', 'story']]);
        $source = new V8Source;

        $this->assertSame(['city', 'story'], $source->skippedKeys());
        $this->assertTrue($source->isSkipped('city'));
        $this->assertFalse($source->isSkipped('page'));
        $this->assertFalse($source->isSkipped('blog'));
        $this->assertFalse($source->isSkipped(null));
    }
}
