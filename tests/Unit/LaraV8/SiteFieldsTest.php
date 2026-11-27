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
        File::put($this->file, "<?php\n\nreturn [\n    'interview.paragraph1' => ['type' => 'richeditor'],\n    'interview.par1pos' => ['type' => 'select', 'options' => ['left', 'right']],\n    'interview.par1img' => ['type' => 'select', 'options' => ['get_record_images'], 'image' => true],\n    'interview.wrong' => ['type' => 'nosuchtype'],\n    'interview.nooptions' => ['type' => 'select'],\n    'docpro.secretkey' => ['skip' => true],\n];\n");
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

        $problems = SiteFields::problems(['interview' => ['paragraph1', 'par1pos', 'par1img', 'wrong', 'nooptions'], 'docpro' => []]);
        $this->assertCount(1, $problems);
        $this->assertStringContainsString("'docpro.secretkey' is not a v8 custom field", $problems[0]);
    }
}
