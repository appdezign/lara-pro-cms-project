<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use Lara\Common\Models\Translation;
use Tests\TestCase;

/**
 * _q() resolves a label ("module::group.tag.key") through the translator. The
 * translations are kept in lara_sys_translations and exported by the admin to
 * lang/vendor/{module}/{locale}/{resource}.php, which the translator reads; the
 * tests give the translator its lines directly. A missing key falls back to
 * "_key" and is added to lara_sys_translations for every language, so a
 * webmaster can translate it; a malformed key is logged and never stored.
 */
class TranslationHelperTest extends TestCase
{
    use DatabaseTransactions;

    private const KEY = 'lara-admin::zztest.label.greeting';

    public function test_it_returns_the_stored_translation(): void
    {
        $this->translation('nl', 'hallo');

        $this->assertSame('hallo', _q(self::KEY));
    }

    public function test_it_can_capitalise_the_translation(): void
    {
        $this->translation('nl', 'hallo');

        $this->assertSame('Hallo', _q(self::KEY, true));
    }

    public function test_it_fills_in_replacements(): void
    {
        $this->translation('nl', 'hallo :name');

        $this->assertSame('hallo Jan', _q(self::KEY, false, ['name' => 'Jan']));
    }

    public function test_it_can_use_another_locale(): void
    {
        $this->translation('nl', 'hallo');
        $this->translation('en', 'hello');

        $this->assertSame('hello', _q(self::KEY, false, [], 'en'));
    }

    public function test_a_missing_key_falls_back_to_the_key_and_is_added_for_every_language(): void
    {
        $label = _q(self::KEY);

        $this->assertSame('_greeting', $label);
        foreach (array_keys(config('laravellocalization.supportedLocales')) as $locale) {
            $this->assertDatabaseHas('lara_sys_translations', [
                'language' => $locale, 'module' => 'lara-admin', 'resource' => 'zztest', 'tag' => 'label', 'key' => 'greeting', 'value' => '_greeting',
            ]);
        }
    }

    public function test_a_malformed_key_falls_back_to_its_last_segment_and_is_only_logged(): void
    {
        Log::spy();
        $countBefore = Translation::count();

        $label = _q('lara-admin::zztest.greeting');

        $this->assertSame('_greeting', $label);
        $this->assertSame($countBefore, Translation::count());
        Log::shouldHaveReceived('warning')->once();
    }

    /**
     * A translation as the exported lang file provides it.
     */
    private function translation(string $language, string $value): void
    {
        app('translator')->addLines(['zztest.label.greeting' => $value], $language, 'lara-admin');
    }
}
