<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Lara\App\Models\Blog;
use Lara\App\Models\Product;
use Lara\Common\Models\Entity;
use Lara\Common\Models\EntityCustomField;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Custom fields stored in a json column need an array cast. BaseModel derives
 * those casts from the entity configuration, so a field a webmaster adds in the
 * admin works without anyone editing the model.
 *
 * These tests only add configuration rows: getCasts() reads the configuration,
 * not the schema, so no column (and no DDL inside the transaction) is needed.
 */
class CustomFieldCastsTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return array<string, array{string}>
     */
    public static function jsonFieldTypes(): array
    {
        return [
            'multiselect' => ['multiselect'],
            'checkbox list' => ['checkboxlist'],
            'multi toggle buttons' => ['multitogglebuttons'],
            'tags input' => ['tagsinput'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function scalarFieldTypes(): array
    {
        return [
            'string' => ['string'],
            'select' => ['select'],
            'radio' => ['radio'],
            'toggle' => ['toggle'],
        ];
    }

    #[DataProvider('jsonFieldTypes')]
    public function test_a_json_custom_field_is_cast_to_an_array(string $fieldType): void
    {
        $this->addBlogField('casttest_field', $fieldType);

        $casts = (new Blog)->getCasts();

        $this->assertSame('array', $casts['casttest_field'] ?? null);
    }

    #[DataProvider('scalarFieldTypes')]
    public function test_a_custom_field_not_stored_as_json_gets_no_cast(string $fieldType): void
    {
        $this->addBlogField('casttest_field', $fieldType);

        $casts = (new Blog)->getCasts();

        $this->assertArrayNotHasKey('casttest_field', $casts);
    }

    public function test_the_cast_follows_a_change_of_field_type(): void
    {
        $field = $this->addBlogField('casttest_field', 'multiselect');
        $this->assertSame('array', (new Blog)->getCasts()['casttest_field'] ?? null);

        $field->update(['field_type' => 'string']);

        $this->assertArrayNotHasKey('casttest_field', (new Blog)->getCasts());
    }

    public function test_the_casts_of_one_entity_do_not_leak_into_another(): void
    {
        $this->addBlogField('casttest_field', 'multiselect');

        $productCasts = (new Product)->getCasts();

        $this->assertArrayNotHasKey('casttest_field', $productCasts);
    }

    private function addBlogField(string $fieldName, string $fieldType): EntityCustomField
    {
        return EntityCustomField::create([
            'entity_id' => Entity::where('resource_slug', 'blogs')->value('id'),
            'title' => $fieldName,
            'field_name' => $fieldName,
            'field_type' => $fieldType,
            'field_hook' => 'after-last',
        ])->refresh();
    }
}
