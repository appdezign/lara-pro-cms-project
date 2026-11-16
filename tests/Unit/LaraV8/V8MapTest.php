<?php

namespace Tests\Unit\LaraV8;

use App\Console\Commands\LaraV8\Support\V8Map;
use Lara\Admin\Enums\CustomFieldType;
use Lara\Admin\Enums\EntityHook;
use Lara\Admin\Enums\FormFieldType;
use Lara\Admin\Enums\NavGroup;
use PHPUnit\Framework\TestCase;

class V8MapTest extends TestCase
{
    /**
     * Every field type of the v8.2.46 core (lara-admin.php fieldTypes / fieldFormTypes).
     */
    private const V8_FIELD_TYPES = [
        'string', 'text', 'mcefull', 'mcemin', 'email', 'datetime', 'date', 'time', 'integer',
        'intunsigned', 'decimal101', 'decimal142', 'decimal164', 'decimal108', 'decimal118',
        'geolocation', 'boolean', 'yesno', 'video', 'selectone', 'selectone2one', 'radio', 'color', 'custom',
    ];

    private const V8_FORM_FIELD_TYPES = ['string', 'text', 'email', 'integer', 'date', 'yesno', 'boolean', 'selectone', 'radio'];

    public function test_every_v8_field_type_maps_to_an_existing_v10_type(): void
    {
        foreach (self::V8_FIELD_TYPES as $type) {
            $v10 = V8Map::fieldType($type);

            $this->assertNotNull($v10, "v8 field type '{$type}' has no mapping.");
            $this->assertNotNull(CustomFieldType::tryFrom($v10), "v8 '{$type}' maps to '{$v10}', which is not a v10 field type.");
        }
    }

    public function test_every_v8_form_field_type_maps_to_an_existing_v10_form_type(): void
    {
        foreach (self::V8_FORM_FIELD_TYPES as $type) {
            $v10 = V8Map::fieldType($type, isForm: true);

            $this->assertNotNull($v10, "v8 form field type '{$type}' has no mapping.");
            $this->assertNotNull(FormFieldType::tryFrom($v10), "v8 form '{$type}' maps to '{$v10}', which is not a v10 form field type.");
        }
    }

    public function test_an_unknown_field_type_has_no_mapping(): void
    {
        $this->assertNull(V8Map::fieldType('does-not-exist'));
        $this->assertNull(V8Map::fieldType('mcefull', isForm: true));
    }

    public function test_column_types_are_kept_where_v8_and_v10_differ_in_name(): void
    {
        $this->assertSame('textarea', V8Map::fieldType('text'));
        $this->assertSame('latitude_10_8', V8Map::fieldType('decimal108'));
        $this->assertSame('longitude_11_8', V8Map::fieldType('decimal118'));
        $this->assertSame('select', V8Map::fieldType('selectone'));
        $this->assertSame('toggle', V8Map::fieldType('boolean'));
        $this->assertSame('richeditormin', V8Map::fieldType('mcemin'));
    }

    public function test_field_types_that_change_behaviour_are_reported(): void
    {
        foreach (array_keys(V8Map::FIELD_TYPE_CHANGES) as $type) {
            $this->assertContains($type, self::V8_FIELD_TYPES);
        }

        $this->assertNotNull(V8Map::fieldTypeChange('selectone2one'));
        $this->assertNull(V8Map::fieldTypeChange('string'));
    }

    public function test_every_v8_hook_maps_to_an_existing_v10_hook_in_the_same_position(): void
    {
        foreach (V8Map::HOOKS as $v10) {
            $this->assertNotNull(EntityHook::tryFrom($v10));
        }

        $this->assertSame('before-title', V8Map::hook('before'));
        $this->assertSame('after-slug', V8Map::hook('between'));
        $this->assertSame('after-last', V8Map::hook('after'));
        $this->assertSame('default', V8Map::hook('NULL'));
        $this->assertNull(V8Map::hook('somewhere'));
    }

    public function test_every_v8_menu_parent_maps_to_an_existing_nav_group(): void
    {
        foreach (V8Map::NAV_GROUPS as $v10) {
            $this->assertNotNull(NavGroup::tryFrom($v10), $v10);
        }
    }

    public function test_options_become_a_list(): void
    {
        $this->assertSame(['one', 'two', 'three'], V8Map::options('{"0":"one","1":"two","2":"three"}'));
        $this->assertSame(['0', '10'], V8Map::options('{"0":"0","1":"10"}'));
        $this->assertSame(['a'], V8Map::options('["a"]'));
        $this->assertNull(V8Map::options(null));
        $this->assertNull(V8Map::options(''));
        $this->assertNull(V8Map::options('NULL'));
        $this->assertNull(V8Map::options('not json'));
    }

    public function test_multiple_rule_values_are_detected(): void
    {
        $this->assertTrue(V8Map::hasMultipleRuleValues('feature || featuresmall'));
        $this->assertFalse(V8Map::hasMultipleRuleValues('feature'));
        $this->assertFalse(V8Map::hasMultipleRuleValues(null));
    }

    public function test_a_conditional_custom_field(): void
    {
        $field = V8Map::customField((object) [
            'fieldtitle' => 'Icon align',
            'fieldname' => 'iconalign',
            'fieldtype' => 'selectone',
            'fieldhook' => 'after',
            'fielddata' => '{"0":"top","1":"left"}',
            'fieldstate' => 'enabledif',
            'condition_field' => 'type',
            'condition_operator' => 'isequal',
            'condition_value' => 'icon',
            'required' => 0,
            'primary' => 1,
            'position' => 7,
        ]);

        $this->assertSame([
            'title' => 'Icon align',
            'field_name' => 'iconalign',
            'field_type' => 'select',
            'field_hook' => 'after-last',
            'field_options' => ['top', 'left'],
            'is_required' => 0,
            'is_filter' => 0,
            'show_in_list' => 1,
            'conditional' => 1,
            'rule_state' => 'enabledif',
            'rule_field' => 'type',
            'rule_operator' => 'isequal',
            'rule_value' => 'icon',
            'sort_order' => 7,
        ], $field);
    }

    public function test_a_plain_field_has_no_rule_and_a_null_condition_string_is_ignored(): void
    {
        $field = V8Map::customField((object) [
            'fieldtitle' => '',
            'fieldname' => 'sortorder',
            'fieldtype' => 'intunsigned',
            'fieldhook' => 'between',
            'fielddata' => '',
            'fieldstate' => 'enabled',
            'condition_field' => 'NULL',
            'condition_operator' => 'isequal',
            'condition_value' => '',
            'required' => 1,
            'primary' => 0,
            'position' => 3,
        ]);

        $this->assertSame('Sortorder', $field['title']);
        $this->assertSame('number', $field['field_type']);
        $this->assertSame('after-slug', $field['field_hook']);
        $this->assertNull($field['field_options']);
        $this->assertSame(1, $field['is_required']);
        $this->assertSame(0, $field['conditional']);
        $this->assertNull($field['rule_field']);
        $this->assertNull($field['rule_operator']);
        $this->assertNull($field['rule_value']);
    }

    public function test_a_form_field_uses_form_types_and_the_default_hook(): void
    {
        $field = V8Map::customField((object) [
            'fieldtitle' => 'Comment',
            'fieldname' => 'comment',
            'fieldtype' => 'text',
            'fieldhook' => 'after',
            'fielddata' => '',
            'fieldstate' => 'enabled',
            'condition_field' => null,
            'condition_operator' => null,
            'condition_value' => null,
            'required' => 0,
            'primary' => 0,
            'position' => 1,
        ], isForm: true);

        $this->assertSame('textarea', $field['field_type']);
        $this->assertSame('default', $field['field_hook']);
    }

    public function test_core_and_site_entities(): void
    {
        $this->assertTrue(V8Map::isCoreEntity('larawidget'));
        $this->assertFalse(V8Map::isCoreEntity('blog'));

        $this->assertSame('widgets', V8Map::resourceSlug('larawidget'));
        $this->assertSame('pages', V8Map::resourceSlug('page'));
        $this->assertSame('galleries', V8Map::resourceSlug('gallery'));

        $this->assertSame('Lara\\Common\\Models\\LaraWidget', V8Map::modelClass('larawidget'));
        $this->assertSame('Lara\\App\\Models\\Blog', V8Map::modelClass('blog'));
        $this->assertSame('Lara\\App\\Models\\Contactform', V8Map::modelClass('contactform'));
    }

    public function test_core_columns_are_renamed_or_dropped(): void
    {
        $this->assertSame('resource_slug', V8Map::column('larawidget', 'relentkey'));
        $this->assertSame('is_global', V8Map::column('larawidget', 'isglobal'));
        $this->assertNull(V8Map::column('larawidget', 'customtags'));
        $this->assertSame('title', V8Map::column('larawidget', 'title'));
        $this->assertSame('location', V8Map::column('blog', 'location'));
    }

    public function test_entity_configuration(): void
    {
        $attributes = V8Map::entity(
            (object) ['entity_key' => 'blog', 'title' => 'News', 'group_key' => 'entity', 'entity_controller' => 'BlogsController', 'menu_parent' => 'modules', 'menu_position' => 200, 'has_front_auth' => 0],
            (object) ['has_lead' => 1, 'has_body' => 1, 'has_status' => 1, 'has_expiration' => 1, 'has_hideinlist' => 1, 'has_groups' => 0, 'group_values' => null, 'is_sortable' => 0, 'sort_field' => 'created_at', 'sort_order' => 'desc', 'sort2_field' => 'NULL', 'sort2_order' => null],
            (object) ['has_search' => 1, 'has_batch' => 1, 'has_filters' => 1, 'show_author' => 1, 'show_status' => 1, 'has_tiny_lead' => 1, 'has_tiny_body' => 0],
            (object) ['has_seo' => 1, 'has_opengraph' => 1, 'has_layout' => 0, 'has_related' => 1, 'is_relatable' => 1, 'has_tags' => 1, 'has_sync' => 0, 'has_images' => 1, 'has_videos' => 1, 'has_videofiles' => 0, 'has_files' => 1, 'max_images' => 24, 'max_videos' => 1, 'max_videofiles' => 1, 'max_files' => 24, 'disk_images' => 'localdisk', 'disk_videos' => 'localdisk', 'disk_files' => 'localdisk'],
        );

        $this->assertSame('blogs', $attributes['resource_slug']);
        $this->assertSame('blog', $attributes['label_single']);
        $this->assertSame('Lara\\App\\Models\\Blog', $attributes['model_class']);
        $this->assertSame('entity', $attributes['cgroup']);
        $this->assertSame('modules', $attributes['nav_group']);
        $this->assertSame(200, $attributes['position']);
        $this->assertSame('created_at', $attributes['sort_primary_field']);
        $this->assertNull($attributes['sort_secondary_field']);
        $this->assertSame(1, $attributes['show_rich_lead']);
        $this->assertSame(0, $attributes['show_rich_body']);
        $this->assertSame(1, $attributes['filter_by_status']);
        $this->assertSame(1, $attributes['filter_by_tag']);
        $this->assertSame(1, $attributes['media_has_gallery']);
        $this->assertSame(24, $attributes['media_max_gallery']);
        $this->assertSame(0, $attributes['media_has_videofiles']);
        $this->assertSame(0, $attributes['media_max_videofiles']);
        $this->assertSame('public', $attributes['media_disk_images']);
    }

    public function test_a_single_image_is_a_featured_image_without_gallery(): void
    {
        $attributes = V8Map::entity(
            (object) ['entity_key' => 'client', 'title' => 'Clients', 'group_key' => 'entity', 'entity_controller' => 'ClientsController', 'menu_parent' => 'modules', 'menu_position' => 210, 'has_front_auth' => 0],
            null,
            null,
            (object) ['has_seo' => 0, 'has_opengraph' => 0, 'has_layout' => 0, 'has_related' => 0, 'is_relatable' => 0, 'has_tags' => 0, 'has_sync' => 0, 'has_images' => 1, 'has_videos' => 0, 'has_videofiles' => 0, 'has_files' => 0, 'max_images' => 1, 'max_videos' => 1, 'max_videofiles' => 1, 'max_files' => 1, 'disk_images' => 'localdisk', 'disk_videos' => 'localdisk', 'disk_files' => 'localdisk'],
        );

        $this->assertSame(1, $attributes['media_has_featured']);
        $this->assertSame(0, $attributes['media_has_gallery']);
        $this->assertSame(0, $attributes['media_max_gallery']);
        $this->assertArrayNotHasKey('col_has_lead', $attributes);
    }

    public function test_abilities_become_permissions(): void
    {
        $this->assertSame(['view_blog', 'view_any_blog'], V8Map::permissions('view', 'blog'));
        $this->assertSame(['create_blog'], V8Map::permissions('create', 'blog'));
        $this->assertSame(['delete_larawidget', 'delete_any_larawidget'], V8Map::permissions('delete', 'larawidget'));
        $this->assertSame([], V8Map::permissions('publish', 'blog'));
    }

    public function test_v8_admin_actions_are_switched_on(): void
    {
        $entity = V8Map::entity((object) ['entity_key' => 'blog', 'title' => 'News', 'group_key' => 'entity', 'entity_controller' => 'BlogsController', 'menu_parent' => 'modules'], null, null, null);
        $form = V8Map::entity((object) ['entity_key' => 'contactform', 'title' => 'Contact', 'group_key' => 'form', 'entity_controller' => 'ContactformsController', 'menu_parent' => 'forms'], null, null, null);

        foreach (['show_view_action', 'show_edit_action', 'show_delete_action', 'show_restore_action', 'filter_by_trashed'] as $attribute) {
            $this->assertSame(1, $entity[$attribute], $attribute);
        }

        $this->assertSame(1, $form['show_view_action']);
        $this->assertSame(1, $form['show_delete_action']);
        $this->assertSame(0, $form['show_edit_action']);
        $this->assertSame(0, $form['filter_by_trashed']);
    }

    public function test_geolocation_parts_only_with_a_geolocation_field(): void
    {
        $fields = [
            (object) ['fieldname' => 'address', 'fieldtype' => 'string'],
            (object) ['fieldname' => 'latitude', 'fieldtype' => 'decimal108'],
            (object) ['fieldname' => 'latitude', 'fieldtype' => 'decimal108'],
            (object) ['fieldname' => 'opening', 'fieldtype' => 'string'],
        ];

        $this->assertSame([], V8Map::geolocationParts($fields));

        $fields[] = (object) ['fieldname' => 'geolocation', 'fieldtype' => 'geolocation'];

        $this->assertSame(['address', 'latitude'], V8Map::geolocationParts($fields));
    }

    public function test_views(): void
    {
        $index = V8Map::view((object) ['title' => null, 'method' => 'index', 'filename' => 'index', 'type' => 'grid3', 'showtags' => '_sortbytaxonomy', 'paginate' => 12, 'infinite' => 0, 'prevnext' => 0, 'publish' => 1], 'Galleries');
        $show = V8Map::view((object) ['title' => 'Detail', 'method' => 'show', 'filename' => 'show', 'type' => '_single', 'showtags' => 'none', 'paginate' => 0, 'infinite' => 0, 'prevnext' => 1, 'publish' => 1], 'Galleries');
        $form = V8Map::view((object) ['title' => '', 'method' => 'form', 'filename' => 'form', 'type' => '_form', 'showtags' => 'none', 'paginate' => 0, 'infinite' => 0, 'prevnext' => 0, 'publish' => 1], 'Contact');

        $this->assertSame(['Galleries Index', 0, 'grid3', '_sortbytaxonomy', 12], [$index['title'], $index['is_single'], $index['list_type'], $index['showtags'], $index['paginate']]);
        $this->assertSame(['Detail', 1, '_single', null, null, 1], [$show['title'], $show['is_single'], $show['list_type'], $show['showtags'], $show['paginate'], $show['prevnext']]);
        $this->assertSame(['Contact Form', 1, null], [$form['title'], $form['is_single'], $form['list_type']]);
    }

    public function test_core_values_are_translated_where_v10_renamed_them(): void
    {
        $this->assertSame('brand', V8Map::coreValue('cta', 'overlaycolor', 'primary'));
        $this->assertSame('brand1', V8Map::coreValue('slider', 'overlaycolor', 'primary'));
        $this->assertSame('black', V8Map::coreValue('cta', 'overlaycolor', 'black'));
        $this->assertSame('primary', V8Map::coreValue('cta', 'type', 'primary'));
        $this->assertSame('primary', V8Map::coreValue('larawidget', 'overlaycolor', 'primary'));
    }

    public function test_content_columns(): void
    {
        $this->assertSame('body2', V8Map::contentColumn('page', 'lead', false));
        $this->assertNull(V8Map::contentColumn('page', 'show_in_app', false));
        $this->assertSame('resource_slug', V8Map::contentColumn('larawidget', 'relentkey', false));

        $this->assertSame('geo_address', V8Map::contentColumn('location', 'address', true));
        $this->assertSame('geo_latitude', V8Map::contentColumn('location', 'latitude', true));
        $this->assertSame('geo_location', V8Map::contentColumn('location', 'geolocation', true));
        $this->assertSame('address', V8Map::contentColumn('application', 'address', false), 'Without a geolocation field an address is an ordinary field.');
        $this->assertSame('lead', V8Map::contentColumn('blog', 'lead', false));
    }

    public function test_a_widget_names_its_content_type_by_resource_slug(): void
    {
        $this->assertSame('blogs', V8Map::coreValue('larawidget', 'relentkey', 'blog'));
        $this->assertSame('galleries', V8Map::coreValue('larawidget', 'relentkey', 'gallery'));
        $this->assertSame('', V8Map::coreValue('larawidget', 'relentkey', ''));
        $this->assertNull(V8Map::coreValue('larawidget', 'relentkey', null));
    }

    public function test_group_values_become_a_list(): void
    {
        $this->assertSame(['news', 'events'], V8Map::groupValues('["news","events"]'));
        $this->assertSame(['news', 'events'], V8Map::groupValues('news, events,'));
        $this->assertNull(V8Map::groupValues(null));
        $this->assertNull(V8Map::groupValues('NULL'));
    }
}
