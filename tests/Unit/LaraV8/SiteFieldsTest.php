<?php

namespace Tests\Unit\LaraV8;

use App\Console\Commands\LaraV8\Support\SiteFields;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SiteFieldsTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = sys_get_temp_dir().'/v8-fields-'.uniqid().'.php';
        File::put($this->file, "<?php\n\nreturn [\n    'interview.paragraph1' => ['type' => 'richeditor'],\n    'interview.par1pos' => ['type' => 'select', 'options' => ['left', 'right']],\n    'interview.par1img' => ['type' => 'select', 'options' => ['get_record_images'], 'image' => true],\n    'interview.wrong' => ['type' => 'nosuchtype'],\n    'interview.nooptions' => ['type' => 'select'],\n    'docpro.secretkey' => ['skip' => true],\n    'page.lead' => ['field' => 'intro', 'type' => 'richeditor', 'hook' => 'after-slug'],\n    'interview.par1quote' => ['type' => 'select', 'options' => ['quote1', 'quote2'], 'rule' => ['field' => 'par1img', 'operator' => 'isequal', 'value' => '']],\n    'interview.par1quotepos' => ['rule' => ['field' => 'par1quote', 'operator' => 'isnotequal', 'value' => '']],\n    'interview.badrule' => ['rule' => ['field' => 'par1img', 'operator' => 'contains', 'value' => '']],\n];\n");
        config(['v8-migration.fields' => $this->file]);
        SiteFields::flush();
    }

    protected function tearDown(): void
    {
        File::delete($this->file);
        SiteFields::flush();

        parent::tearDown();
    }

    public function test_a_setting_replaces_the_mapped_type_and_options(): void
    {
        [$attributes] = SiteFields::applyTo('interview', ['field_name' => 'paragraph1', 'field_type' => 'textarea', 'field_options' => null]);
        $this->assertSame(['field_name' => 'paragraph1', 'field_type' => 'richeditor', 'field_options' => null], $attributes);

        [$attributes] = SiteFields::applyTo('interview', ['field_name' => 'par1pos', 'field_type' => 'textarea', 'field_options' => null]);
        $this->assertSame(['select', ['left', 'right']], [$attributes['field_type'], $attributes['field_options']]);
    }

    public function test_a_rule_makes_the_field_conditional(): void
    {
        $plain = ['field_name' => 'par1quotepos', 'field_type' => 'select', 'field_options' => ['left'], 'conditional' => 0, 'rule_state' => 'enabled', 'rule_field' => null, 'rule_operator' => null, 'rule_value' => null];

        [$attributes] = SiteFields::applyTo('interview', $plain);
        $this->assertSame([1, 'enabledif', 'par1quote', 'isnotequal', ''], [$attributes['conditional'], $attributes['rule_state'], $attributes['rule_field'], $attributes['rule_operator'], $attributes['rule_value']]);
        $this->assertSame(['select', ['left']], [$attributes['field_type'], $attributes['field_options']], 'A rule alone leaves the type.');

        [$attributes] = SiteFields::applyTo('interview', ['field_name' => 'par1quote'] + $plain);
        $this->assertSame(['select', ['quote1', 'quote2'], 'enabledif', 'par1img', 'isequal', ''], [$attributes['field_type'], $attributes['field_options'], $attributes['rule_state'], $attributes['rule_field'], $attributes['rule_operator'], $attributes['rule_value']]);

        [$attributes, $problem] = SiteFields::applyTo('interview', ['field_name' => 'badrule'] + $plain);
        $this->assertNull($attributes);
        $this->assertStringContainsString('needs a field, an operator', $problem);
    }

    public function test_a_field_without_a_setting_is_left_alone(): void
    {
        $this->assertSame([['field_name' => 'title2', 'field_type' => 'string'], null], SiteFields::applyTo('interview', ['field_name' => 'title2', 'field_type' => 'string']));
    }

    public function test_an_unknown_type_or_missing_options_are_problems(): void
    {
        [$attributes, $problem] = SiteFields::applyTo('interview', ['field_name' => 'wrong', 'field_type' => 'textarea', 'field_options' => null]);
        $this->assertNull($attributes);
        $this->assertStringContainsString("'nosuchtype'", $problem);

        [$attributes, $problem] = SiteFields::applyTo('interview', ['field_name' => 'nooptions', 'field_type' => 'textarea', 'field_options' => null]);
        $this->assertNull($attributes);
        $this->assertStringContainsString('needs options', $problem);
    }

    public function test_skip_and_image_settings_and_unknown_keys(): void
    {
        $this->assertTrue(SiteFields::isSkipped('docpro', 'secretkey'));
        $this->assertFalse(SiteFields::isSkipped('docpro', 'title'));
        $this->assertSame(['par1img'], SiteFields::imageFields('interview'));
        $this->assertSame([], SiteFields::imageFields('docpro'));

        $problems = SiteFields::problems(['interview' => ['paragraph1', 'par1pos', 'par1img', 'wrong', 'nooptions', 'par1quote', 'par1quotepos', 'badrule'], 'docpro' => []], ['page' => ['title', 'lead']]);
        $this->assertCount(1, $problems);
        $this->assertStringContainsString("'docpro.secretkey' is not a v8 custom field", $problems[0]);

        $problems = SiteFields::problems(['interview' => ['paragraph1', 'par1pos', 'par1img', 'wrong', 'nooptions', 'par1quote', 'par1quotepos', 'badrule'], 'docpro' => ['secretkey']], ['page' => ['title']]);
        $this->assertCount(1, $problems);
        $this->assertStringContainsString("'page.lead' is not a v8 content column", $problems[0]);
    }

    public function test_a_v8_column_with_a_field_setting_becomes_a_custom_field(): void
    {
        $this->assertSame('intro', SiteFields::columnTarget('page', 'lead'));
        $this->assertNull(SiteFields::columnTarget('page', 'body'));
        $this->assertNull(SiteFields::columnTarget('interview', 'paragraph1'), 'A custom field setting is no column setting.');
        $this->assertSame(['lead'], array_keys(SiteFields::columnFields('page')));

        [$attributes] = SiteFields::columnFieldAttributes(SiteFields::columnField('page', 'lead'));
        $this->assertSame(['Intro', 'intro', 'richeditor', 'after-slug', null, 'enabled'], [$attributes['title'], $attributes['field_name'], $attributes['field_type'], $attributes['field_hook'], $attributes['field_options'], $attributes['rule_state']]);

        [$attributes, $problem] = SiteFields::columnFieldAttributes(['field' => 'intro', 'hook' => 'nowhere']);
        $this->assertNull($attributes);
        $this->assertStringContainsString("'nowhere'", $problem);
    }
}
