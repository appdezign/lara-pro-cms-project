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
        $this->assertSame(['city', 'product', 'story'], $this->configWith(' city, product ,,story ')['skip_entities']);
    }

    public function test_no_env_value_skips_nothing(): void
    {
        $this->assertSame([], $this->configWith(null)['skip_entities']);
        $this->assertSame([], $this->configWith('')['skip_entities']);
    }

    /**
     * The config file read with LARA_V8_SKIP_ENTITIES set to $value (null: not set). Laravel
     * reads $_SERVER and $_ENV before getenv(), and the site .env may set the key.
     *
     * @return array<string, mixed>
     */
    private function configWith(?string $value): array
    {
        $key = 'LARA_V8_SKIP_ENTITIES';
        $saved = [$_SERVER[$key] ?? null, $_ENV[$key] ?? null, getenv($key)];

        foreach ([&$_SERVER, &$_ENV] as &$vars) {
            if ($value === null) {
                unset($vars[$key]);
            } else {
                $vars[$key] = $value;
            }
        }
        unset($vars);
        $value === null ? putenv($key) : putenv("{$key}={$value}");

        try {
            return require config_path('v8-migration.php');
        } finally {
            foreach ([[&$_SERVER, $saved[0]], [&$_ENV, $saved[1]]] as [&$vars, $old]) {
                if ($old === null) {
                    unset($vars[$key]);
                } else {
                    $vars[$key] = $old;
                }
            }
            unset($vars);
            $saved[2] === false ? putenv($key) : putenv("{$key}={$saved[2]}");
        }
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

    public function test_the_standard_content_types_are_never_skipped(): void
    {
        config(['v8-migration.skip_entities' => ['blog', 'city', 'doc', 'gallery', 'video']]);
        $source = new V8Source;

        $this->assertSame(['city', 'video'], $source->skippedKeys());
        $this->assertFalse($source->isSkipped('blog'));
        $this->assertFalse($source->isSkipped('doc'));
        $this->assertFalse($source->isSkipped('gallery'));
        $this->assertTrue($source->isSkipped('video'));
    }
}
