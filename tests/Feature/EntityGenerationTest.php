<?php

namespace Tests\Feature;

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Lara\Admin\Concerns\HasLaraBuilder;
use Lara\Admin\Livewire\BackupColumns;
use Lara\Admin\Resources\Entities\Pages\EditEntity;
use Lara\Common\Entities\EntityFieldName;
use Lara\Common\Entities\EntityLabel;
use Lara\Common\Entities\EntityRegistry;
use Lara\Common\Models\Entity;
use Lara\Common\Models\EntityCustomField;
use Lara\Common\Models\User;
use Livewire\Livewire;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Covers the code generator behind "create a new entity in the backend".
 *
 * This is the highest-risk operation in the CMS: it writes PHP classes to disk
 * and runs DDL against the live schema, from inside a web request. Nothing
 * protected it before.
 *
 * These tests generate a throwaway entity and clean up in tearDown. The label
 * is deliberately obscure so a leaked artefact is obvious.
 */
class EntityGenerationTest extends TestCase
{
    /** The label the generator derives every name from. */
    private const LABEL = 'zzfixture';

    /** Derived by the generator: ucfirst(label) / Str::plural(label). */
    private const MODEL = 'Zzfixture';

    private const PLURAL = 'zzfixtures';

    private const RESOURCE_DIR = 'Zzfixtures';

    private const TABLE = 'lara_content_zzfixtures';

    /**
     * Plural for the list-scoped pages, singular for the record-scoped ones.
     *
     * @var list<string>
     */
    private const PAGE_CLASSES = [
        'ListZzfixtures',
        'CreateZzfixture',
        'EditZzfixture',
        'ViewZzfixture',
        'ReorderZzfixtures',
    ];

    /** A second fixture with cgroup "form", which generates fewer pages. */
    private const FORM_LABEL = 'zzformfixture';

    private const FORM_MODEL = 'Zzformfixture';

    private const FORM_DIR = 'Zzformfixtures';

    private const FORM_TABLE = 'lara_form_zzformfixtures';

    /** The real schema builder, while a test has swapped in a failing one. */
    private ?object $realSchema = null;

    protected function tearDown(): void
    {
        if ($this->realSchema !== null) {
            Schema::swap($this->realSchema);
        }

        $this->cleanUpGeneratedEntity();

        parent::tearDown();
    }

    /**
     * A host for the generator, which lives in a trait as private static methods.
     */
    private function builder(): object
    {
        return new class
        {
            use HasLaraBuilder;

            public function build(Entity $entity): void
            {
                self::createEntity($entity);
            }

            public function buildTable(Entity $entity): void
            {
                self::checkDatabaseTable($entity);
            }

            public function buildEntitySafely(Entity $entity): bool
            {
                return self::buildEntity($entity);
            }

            /**
             * @param  array<string, mixed>|null  $previousValues
             */
            public function buildField(EntityCustomField $customField, ?array $previousValues = null): bool
            {
                return self::buildCustomField($customField, $previousValues);
            }

            /**
             * @param  array<string, mixed>  $previousValues
             */
            public function buildBodyColumns(Entity $entity, array $previousValues): bool
            {
                return self::buildExtraBodyColumns($entity, $previousValues);
            }

            public function archiveField(EntityCustomField $customField): void
            {
                self::archiveCustomFieldColumn($customField);
            }

            public function assertFieldCanBeArchived(EntityCustomField $customField): void
            {
                self::assertCustomFieldCanBeArchived($customField);
            }

            /**
             * @return array<string, array{key: string, columns: list<string>, column_type: string, filled_rows: int}>
             */
            public function backupColumns(Entity $entity): array
            {
                return self::getBackupColumns($entity);
            }

            /**
             * @param  array{key: string, column_type: string}  $backup
             * @return array<string, string>
             */
            public function restorableFieldTypes(Entity $entity, array $backup): array
            {
                return self::getRestorableFieldTypes($entity, $backup);
            }

            public function restoreBackup(Entity $entity, string $key, string $fieldType, string $title): bool
            {
                return self::restoreBackupColumn($entity, $key, $fieldType, $title);
            }

            public function dropBackup(Entity $entity, string $key): bool
            {
                return self::dropBackupColumn($entity, $key);
            }
        };
    }

    private function makeEntityRow(string $label, string $cgroup = 'entity'): Entity
    {
        return Entity::create([
            'title' => $label,
            'label_single' => $label,
            'cgroup' => $cgroup,
            'nav_group' => 'modules',
        ]);
    }

    /**
     * Swap in a schema builder that passes every call through, except the nth call of one
     * method, which throws. Lets a test fail the builder at an exact step.
     */
    private function failSchemaCall(string $method, int $failOnCall = 1): void
    {
        $this->realSchema = $realSchema = Schema::getFacadeRoot();
        $calls = 0;

        $schema = Mockery::mock($realSchema);
        $schema->shouldReceive($method)->andReturnUsing(
            function (...$arguments) use ($realSchema, $method, $failOnCall, &$calls) {
                if (++$calls === $failOnCall) {
                    throw new RuntimeException('Forced failure of Schema::'.$method.'()');
                }

                return $realSchema->{$method}(...$arguments);
            }
        );

        Schema::swap($schema);
    }

    /**
     * A generated fixture entity with its table, ready for custom fields.
     */
    private function makeBuiltEntity(): Entity
    {
        $entity = $this->makeEntityRow(self::LABEL);

        $this->assertTrue($this->builder()->buildEntitySafely($entity), 'The fixture entity could not be built.');

        return $entity->refresh();
    }

    /**
     * Refreshed, so every column is in the original state, as for a record the admin loaded.
     */
    private function makeFieldRow(Entity $entity, string $fieldName, string $fieldType = 'string'): EntityCustomField
    {
        return EntityCustomField::create([
            'entity_id' => $entity->id,
            'title' => $fieldName,
            'field_name' => $fieldName,
            'field_type' => $fieldType,
            'field_hook' => 'after-last',
        ])->refresh();
    }

    /**
     * A built field with a column, and one content row that has a value in it.
     */
    private function makeFieldWithData(Entity $entity, string $fieldName, string $fieldType = 'string', string $value = 'kept'): EntityCustomField
    {
        $field = $this->makeFieldRow($entity, $fieldName, $fieldType);
        $this->assertTrue($this->builder()->buildField($field));

        DB::table(self::TABLE)->insert([
            'user_id' => User::role('superadmin')->value('id'),
            'title' => 'row with data',
            $fieldName => $value,
        ]);

        return $field;
    }

    /**
     * @return list<string> absolute paths the generator writes
     */
    private function generatedPaths(string $model = self::MODEL, string $dir = self::RESOURCE_DIR): array
    {
        $app = base_path('laracms/app/');

        return [
            $app.'Models/'.$model.'.php',
            $app.'Entities/'.$dir.'Entity.php',
            $app.'Policies/'.$model.'Policy.php',
            $app.'Http/Controllers/Front/Entity/'.$dir.'Controller.php',
            $app.'Database/Factories/'.$model.'Factory.php',
            $app.'Filament/Resources/'.$dir.'/'.$model.'Resource.php',
        ];
    }

    private function cleanUpGeneratedEntity(): void
    {
        foreach ([[self::MODEL, self::RESOURCE_DIR], [self::FORM_MODEL, self::FORM_DIR]] as [$model, $dir]) {
            foreach ($this->generatedPaths($model, $dir) as $path) {
                File::delete($path);
            }

            File::deleteDirectory(base_path('laracms/app/Filament/Resources/'.$dir));
        }

        Schema::dropIfExists(self::TABLE);
        Schema::dropIfExists(self::FORM_TABLE);

        Entity::whereIn('label_single', [self::LABEL, self::FORM_LABEL])->forceDelete();

        app(EntityRegistry::class)->flush();
    }

    /**
     * The generator is the last point at which a bad label can be rejected
     * cheaply. Past it, the label has become PHP files on disk.
     *
     * "class" matters here: it satisfies the naming pattern but ucfirst()s to a
     * reserved word, so the pattern alone is not enough.
     */
    public function test_it_rejects_a_label_that_cannot_become_a_class_name(): void
    {
        $badLabels = [
            'zz fixture' => 'contains a space',
            'ZzFixture' => 'is not lowercase',
            'class' => 'is a reserved word',
            'match' => 'is a reserved word',
            '9fixture' => 'starts with a digit',
            'zz-fixture' => 'contains a hyphen',
            '' => 'is empty',
        ];

        foreach ($badLabels as $badLabel => $why) {
            $entity = $this->makeEntityRow($badLabel);
            $rejected = false;

            try {
                $this->builder()->build($entity);
            } catch (InvalidArgumentException $e) {
                $rejected = true;
                $this->assertStringContainsString($badLabel === '' ? 'cannot be used' : $badLabel, $e->getMessage());
            } finally {
                $entity->forceDelete();
            }

            $this->assertTrue($rejected, 'Label "'.$badLabel.'" must be rejected: it '.$why.'.');
        }
    }

    public function test_a_valid_label_is_accepted_by_the_naming_rules(): void
    {
        foreach (['product', 'recipe', 'vacancy', 'zzfixture', 'item2'] as $label) {
            $this->assertNull(
                EntityLabel::reject($label),
                'Label "'.$label.'" should be acceptable.'
            );
        }
    }

    public function test_a_rejected_label_writes_nothing_to_disk(): void
    {
        $entity = $this->makeEntityRow('Zz Fixture');

        try {
            $this->builder()->build($entity);
        } catch (InvalidArgumentException) {
            // expected
        } finally {
            $entity->forceDelete();
        }

        foreach ($this->generatedPaths() as $path) {
            $this->assertFileDoesNotExist($path);
        }
    }

    public function test_it_generates_every_expected_file(): void
    {
        $entity = $this->makeEntityRow(self::LABEL);

        $this->builder()->build($entity);

        foreach ($this->generatedPaths() as $path) {
            $this->assertFileExists($path);
        }

        foreach (self::PAGE_CLASSES as $page) {
            $this->assertFileExists(
                base_path('laracms/app/Filament/Resources/'.self::RESOURCE_DIR.'/Pages/'.$page.'.php')
            );
        }
    }

    /**
     * Pages are named the way Filament's own generator names them, rather than
     * the generic ListRecords/CreateRecord/... which shadowed the Filament base
     * classes they extend.
     */
    public function test_generated_pages_do_not_use_the_generic_names(): void
    {
        $entity = $this->makeEntityRow(self::LABEL);

        $this->builder()->build($entity);

        $pagesDir = base_path('laracms/app/Filament/Resources/'.self::RESOURCE_DIR.'/Pages/');

        foreach (['ListRecords', 'CreateRecord', 'EditRecord', 'ViewRecord', 'ReorderRecords'] as $generic) {
            $this->assertFileDoesNotExist($pagesDir.$generic.'.php');
        }

        // and the resource points at the new names
        $resource = File::get(
            base_path('laracms/app/Filament/Resources/'.self::RESOURCE_DIR.'/'.self::MODEL.'Resource.php')
        );

        foreach (self::PAGE_CLASSES as $page) {
            $this->assertStringContainsString('Pages\\'.$page.'::route', $resource);
        }
    }

    /**
     * A form resource only lists and views its submissions, so it must not get
     * create, edit or reorder pages.
     */
    public function test_a_form_entity_generates_only_list_and_view_pages(): void
    {
        $entity = $this->makeEntityRow(self::FORM_LABEL, 'form');

        $this->builder()->build($entity);

        $pagesDir = base_path('laracms/app/Filament/Resources/'.self::FORM_DIR.'/Pages/');

        $this->assertFileExists($pagesDir.'List'.self::FORM_DIR.'.php');
        $this->assertFileExists($pagesDir.'View'.self::FORM_MODEL.'.php');

        foreach (['Create', 'Edit', 'Reorder'] as $verb) {
            $this->assertCount(
                0,
                glob($pagesDir.$verb.'*.php'),
                'A form resource must not get a '.$verb.' page.'
            );
        }

        $resource = File::get(
            base_path('laracms/app/Filament/Resources/'.self::FORM_DIR.'/'.self::FORM_MODEL.'Resource.php')
        );

        $this->assertStringContainsString('Pages\\List'.self::FORM_DIR.'::route', $resource);
        $this->assertStringNotContainsString('CREATEPAGE', $resource, 'An unreplaced stub placeholder was left behind.');
    }

    /**
     * .editorconfig declares tabs for this project, and pint.json is configured
     * not to fight that. Generated code has to follow the same rule, or every
     * new entity reintroduces the mixed indentation.
     */
    public function test_generated_files_are_tab_indented(): void
    {
        $entity = $this->makeEntityRow(self::LABEL);

        $this->builder()->build($entity);

        $pagesDir = base_path('laracms/app/Filament/Resources/'.self::RESOURCE_DIR.'/Pages/');

        $files = [
            ...$this->generatedPaths(),
            ...array_map(fn (string $page): string => $pagesDir.$page.'.php', self::PAGE_CLASSES),
        ];

        foreach ($files as $path) {
            $indented = preg_grep('/^[ \t]+\S/', file($path) ?: []);

            $this->assertNotEmpty($indented, basename($path).' has no indented lines to check.');

            $spaceIndented = preg_grep('/^ /', $indented);

            $this->assertSame(
                [],
                array_values($spaceIndented),
                basename($path).' is space-indented; the stub should emit tabs.'
            );
        }
    }

    public function test_it_records_the_derived_names_on_the_entity_row(): void
    {
        $entity = $this->makeEntityRow(self::LABEL);

        $this->builder()->build($entity);

        $entity->refresh();

        $this->assertSame(self::PLURAL, $entity->resource_slug);
        $this->assertSame('Lara\App\Models\\'.self::MODEL, $entity->model_class);
        $this->assertSame('Lara\App\Policies\\'.self::MODEL.'Policy', $entity->policy);
        $this->assertSame(self::RESOURCE_DIR.'Controller', $entity->controller);
        $this->assertSame(
            'Lara\App\Filament\Resources\\'.self::RESOURCE_DIR.'\\'.self::MODEL.'Resource',
            $entity->resource
        );
    }

    public function test_the_generated_entity_class_uses_the_new_namespace(): void
    {
        $entity = $this->makeEntityRow(self::LABEL);

        $this->builder()->build($entity);

        $contents = File::get(base_path('laracms/app/Entities/'.self::RESOURCE_DIR.'Entity.php'));

        $this->assertStringContainsString('namespace Lara\App\Entities;', $contents);
        $this->assertStringContainsString('use Lara\Common\Entities\LaraEntity;', $contents);
        $this->assertStringContainsString("resource_slug = '".self::PLURAL."'", $contents);
    }

    public function test_it_creates_the_content_table_with_the_base_columns(): void
    {
        $entity = $this->makeEntityRow(self::LABEL);

        $this->builder()->build($entity);
        $this->builder()->buildTable($entity->refresh());

        $this->assertTrue(Schema::hasTable(self::TABLE));

        $this->assertTrue(Schema::hasColumns(self::TABLE, [
            'id', 'user_id', 'language', 'title', 'slug',
            'lead', 'body', 'publish', 'publish_from', 'publish_to',
            'position', 'cgroup', 'deleted_at',
        ]));
    }

    public function test_the_new_entity_becomes_visible_through_the_registry(): void
    {
        $registry = app(EntityRegistry::class);

        $this->assertNull($registry->find(self::PLURAL));

        $entity = $this->makeEntityRow(self::LABEL);
        $this->builder()->build($entity);

        // creating and saving the row fires the observer, so no manual flush
        $config = app(EntityRegistry::class)->find(self::PLURAL);

        $this->assertNotNull($config, 'A generated entity must be resolvable immediately.');
        $this->assertSame(self::PLURAL, $config->resourceSlug);
        $this->assertSame('entity', $config->cgroup);
    }

    public function test_a_field_name_that_would_clash_with_a_base_column_is_rejected(): void
    {
        foreach (['title', 'body', 'body3', 'geo_city', 'publish_from', '_backup', 'My Field', '9lives', str_repeat('a', 64)] as $badName) {
            $this->assertNotNull(EntityFieldName::reject($badName), 'Field name "'.$badName.'" must be rejected.');
        }

        foreach (['first_name', 'myselect_2', 'startdate', 'hook', 'resource_slug'] as $goodName) {
            $this->assertNull(EntityFieldName::reject($goodName), 'Field name "'.$goodName.'" should be acceptable.');
        }
    }

    public function test_building_an_entity_creates_its_files_and_table(): void
    {
        $this->makeBuiltEntity();

        foreach ($this->generatedPaths() as $path) {
            $this->assertFileExists($path);
        }

        $this->assertTrue(Schema::hasTable(self::TABLE));
    }

    public function test_an_entity_that_fails_to_build_leaves_nothing_behind(): void
    {
        $entity = $this->makeEntityRow(self::LABEL);

        $this->failSchemaCall('create');

        $this->assertFalse($this->builder()->buildEntitySafely($entity));

        $this->assertNull(Entity::find($entity->id), 'The entity row must be removed again.');
        $this->assertFalse(Schema::hasTable(self::TABLE));

        foreach ($this->generatedPaths() as $path) {
            $this->assertFileDoesNotExist($path);
        }

        $this->assertDirectoryDoesNotExist(base_path('laracms/app/Filament/Resources/'.self::RESOURCE_DIR));
    }

    public function test_an_entity_with_a_taken_resource_slug_is_not_built(): void
    {
        $this->makeBuiltEntity();
        $modelFile = base_path('laracms/app/Models/'.self::MODEL.'.php');
        $modelContents = File::get($modelFile);

        $duplicate = $this->makeEntityRow(self::LABEL);

        $this->assertFalse($this->builder()->buildEntitySafely($duplicate));

        $this->assertNull(Entity::find($duplicate->id));
        $this->assertSame($modelContents, File::get($modelFile), 'The existing entity must not be touched.');
        $this->assertTrue(Schema::hasTable(self::TABLE));
    }

    public function test_a_new_field_gets_its_column(): void
    {
        $entity = $this->makeBuiltEntity();
        $field = $this->makeFieldRow($entity, 'zzcolor');

        $this->assertTrue($this->builder()->buildField($field));

        $this->assertSame('varchar', Schema::getColumnType(self::TABLE, 'zzcolor'));
        $this->assertNotNull(EntityCustomField::find($field->id));
    }

    public function test_a_new_field_with_a_base_column_name_is_removed_and_the_base_column_is_untouched(): void
    {
        $entity = $this->makeBuiltEntity();
        $field = $this->makeFieldRow($entity, 'title', 'textarea');

        $this->assertFalse($this->builder()->buildField($field));

        $this->assertNull(EntityCustomField::find($field->id));
        $this->assertSame('varchar', Schema::getColumnType(self::TABLE, 'title'));
        $this->assertFalse(Schema::hasColumn(self::TABLE, '_title'));
    }

    public function test_a_new_field_whose_column_cannot_be_added_is_removed(): void
    {
        $entity = $this->makeBuiltEntity();
        $field = $this->makeFieldRow($entity, 'zzcolor');

        $this->failSchemaCall('table');

        $this->assertFalse($this->builder()->buildField($field));

        $this->assertNull(EntityCustomField::find($field->id));
        $this->assertFalse(Schema::hasColumn(self::TABLE, 'zzcolor'));
    }

    public function test_a_renamed_field_keeps_the_old_column_as_backup(): void
    {
        $entity = $this->makeBuiltEntity();
        $field = $this->makeFieldRow($entity, 'zzold');
        $this->builder()->buildField($field);

        $field->update(['field_name_temp' => 'zznew']);

        $this->assertTrue($this->builder()->buildField($field, $field->getPrevious()));

        $field->refresh();
        $this->assertSame('zznew', $field->field_name);
        $this->assertNull($field->field_name_temp);
        $this->assertTrue(Schema::hasColumn(self::TABLE, 'zznew'));
        $this->assertTrue(Schema::hasColumn(self::TABLE, '_zzold'));
        $this->assertFalse(Schema::hasColumn(self::TABLE, 'zzold'));
    }

    public function test_a_rename_that_fails_halfway_restores_the_column_and_the_field(): void
    {
        $entity = $this->makeBuiltEntity();
        $field = $this->makeFieldRow($entity, 'zzold');
        $this->builder()->buildField($field);

        $field->update(['field_name_temp' => 'zznew']);

        // call 1 renames zzold to _zzold, call 2 adds zznew
        $this->failSchemaCall('table', 2);

        $this->assertFalse($this->builder()->buildField($field, $field->getPrevious()));

        $field->refresh();
        $this->assertSame('zzold', $field->field_name);
        $this->assertNull($field->field_name_temp, 'A pending rename must not be retried on the next save.');
        $this->assertTrue(Schema::hasColumn(self::TABLE, 'zzold'));
        $this->assertFalse(Schema::hasColumn(self::TABLE, '_zzold'));
        $this->assertFalse(Schema::hasColumn(self::TABLE, 'zznew'));
    }

    public function test_a_type_change_that_fails_halfway_restores_the_column_and_the_field(): void
    {
        $entity = $this->makeBuiltEntity();
        $field = $this->makeFieldRow($entity, 'zzcolor');
        $this->builder()->buildField($field);

        $field->update(['field_type' => 'textarea']);

        // call 1 renames zzcolor to _zzcolor, call 2 adds the text column
        $this->failSchemaCall('table', 2);

        $this->assertFalse($this->builder()->buildField($field, $field->getPrevious()));

        $this->assertSame('string', $field->refresh()->field_type);
        $this->assertSame('varchar', Schema::getColumnType(self::TABLE, 'zzcolor'));
        $this->assertFalse(Schema::hasColumn(self::TABLE, '_zzcolor'));
    }

    public function test_a_geolocation_field_that_fails_halfway_leaves_no_geo_columns(): void
    {
        $entity = $this->makeBuiltEntity();
        $field = $this->makeFieldRow($entity, 'zzgeo', 'geolocation');

        // the first three geo columns are added, the fourth fails
        $this->failSchemaCall('table', 4);

        $this->assertFalse($this->builder()->buildField($field));

        $this->assertNull(EntityCustomField::find($field->id));
        foreach (['geo_address', 'geo_pcode', 'geo_city', 'geo_country'] as $column) {
            $this->assertFalse(Schema::hasColumn(self::TABLE, $column), $column.' must be dropped again.');
        }
    }

    public function test_extra_body_columns_that_fail_halfway_are_dropped_and_the_count_restored(): void
    {
        $entity = $this->makeBuiltEntity();

        $entity->update(['col_extra_body_fields' => 3]);

        // body2 is added, body3 fails
        $this->failSchemaCall('table', 2);

        $this->assertFalse($this->builder()->buildBodyColumns($entity, $entity->getPrevious()));

        $this->assertEquals(0, $entity->refresh()->col_extra_body_fields);
        $this->assertFalse(Schema::hasColumn(self::TABLE, 'body2'));
    }

    public function test_a_multi_value_field_gets_its_array_cast_without_editing_the_model(): void
    {
        $entity = $this->makeBuiltEntity();
        $field = $this->makeFieldRow($entity, 'zzchoices', 'multiselect');

        $this->assertTrue($this->builder()->buildField($field));

        $modelClass = $entity->model_class;
        $this->assertSame('array', (new $modelClass)->getCasts()['zzchoices'] ?? null);
    }

    public function test_a_deleted_field_becomes_a_backup_column_with_its_data(): void
    {
        $entity = $this->makeBuiltEntity();
        $field = $this->makeFieldWithData($entity, 'zzcolor');

        $field->delete();
        $this->builder()->archiveField($field);

        $backups = $this->builder()->backupColumns($entity);

        $this->assertSame(['_zzcolor'], $backups['zzcolor']['columns']);
        $this->assertSame('varchar', $backups['zzcolor']['column_type']);
        $this->assertSame(1, $backups['zzcolor']['filled_rows']);
    }

    public function test_a_backup_column_can_be_restored_as_a_field_with_its_data(): void
    {
        $entity = $this->makeBuiltEntity();
        $field = $this->makeFieldWithData($entity, 'zzcolor', value: 'blue');
        $field->delete();
        $this->builder()->archiveField($field);

        $this->assertTrue($this->builder()->restoreBackup($entity, 'zzcolor', 'string', 'Colour'));

        $restored = $entity->customfields()->where('field_name', 'zzcolor')->first();
        $this->assertNotNull($restored);
        $this->assertSame('Colour', $restored->title);
        $this->assertSame('blue', DB::table(self::TABLE)->value('zzcolor'));
        $this->assertSame([], $this->builder()->backupColumns($entity));
    }

    public function test_a_backup_column_only_restores_as_a_field_type_that_fits_its_column(): void
    {
        $entity = $this->makeBuiltEntity();
        $field = $this->makeFieldWithData($entity, 'zzcolor');
        $field->delete();
        $this->builder()->archiveField($field);

        $backup = $this->builder()->backupColumns($entity)['zzcolor'];
        $fieldTypes = array_keys($this->builder()->restorableFieldTypes($entity, $backup));

        $this->assertContains('string', $fieldTypes);
        $this->assertNotContains('number', $fieldTypes);

        $this->assertFalse($this->builder()->restoreBackup($entity, 'zzcolor', 'number', 'Colour'));
        $this->assertTrue(Schema::hasColumn(self::TABLE, '_zzcolor'));
        $this->assertFalse($entity->customfields()->where('field_name', 'zzcolor')->exists());
    }

    public function test_a_restore_that_fails_halfway_keeps_the_backup_column(): void
    {
        $entity = $this->makeBuiltEntity();
        $field = $this->makeFieldWithData($entity, 'zzcolor');
        $field->delete();
        $this->builder()->archiveField($field);

        // the column is renamed and the field row created, then the verification fails
        $this->failSchemaCall('getColumnType');

        $this->assertFalse($this->builder()->restoreBackup($entity, 'zzcolor', 'string', 'Colour'));

        $this->assertTrue(Schema::hasColumn(self::TABLE, '_zzcolor'));
        $this->assertFalse(Schema::hasColumn(self::TABLE, 'zzcolor'));
        $this->assertFalse($entity->customfields()->where('field_name', 'zzcolor')->exists());
    }

    public function test_a_backup_column_can_be_deleted_permanently(): void
    {
        $entity = $this->makeBuiltEntity();
        $field = $this->makeFieldWithData($entity, 'zzcolor');
        $field->delete();
        $this->builder()->archiveField($field);

        $this->assertTrue($this->builder()->dropBackup($entity, 'zzcolor'));

        $this->assertFalse(Schema::hasColumn(self::TABLE, '_zzcolor'));
        $this->assertSame([], $this->builder()->backupColumns($entity));
    }

    public function test_an_older_backup_column_is_never_overwritten(): void
    {
        $entity = $this->makeBuiltEntity();
        $oldField = $this->makeFieldWithData($entity, 'zzcolor', value: 'old data');
        $oldField->delete();
        $this->builder()->archiveField($oldField);

        $newField = $this->makeFieldRow($entity, 'zzcolor');
        $this->assertTrue($this->builder()->buildField($newField));

        // a type change would need the backup slot, so it is refused and the field restored
        $newField->update(['field_type' => 'textarea']);
        $this->assertFalse($this->builder()->buildField($newField, $newField->getPrevious()));
        $this->assertSame('string', $newField->refresh()->field_type);

        // and so is a delete, before the row is gone
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->builder()->assertFieldCanBeArchived($newField);
        } finally {
            $this->assertSame('old data', DB::table(self::TABLE)->value('_zzcolor'));
        }
    }

    public function test_the_backups_of_a_geolocation_field_are_one_record_and_restore_together(): void
    {
        $entity = $this->makeBuiltEntity();
        $field = $this->makeFieldRow($entity, 'zzgeo', 'geolocation');
        $this->assertTrue($this->builder()->buildField($field));
        $field->delete();
        $this->builder()->archiveField($field);

        $backups = $this->builder()->backupColumns($entity);

        $this->assertSame(['geolocation'], array_keys($backups));
        $this->assertCount(7, $backups['geolocation']['columns']);
        $this->assertSame(['geolocation' => 'Geolocation'], $this->builder()->restorableFieldTypes($entity, $backups['geolocation']));

        $this->assertTrue($this->builder()->restoreBackup($entity, 'geolocation', 'geolocation', 'Location'));

        $this->assertTrue(Schema::hasColumns(self::TABLE, ['geo_address', 'geo_latitude', 'geo_longitude']));
        $this->assertTrue($entity->customfields()->where('field_type', 'geolocation')->exists());
    }

    public function test_the_backup_columns_table_restores_a_backup_for_the_webmaster(): void
    {
        $entity = $this->makeBuiltEntity();
        $field = $this->makeFieldWithData($entity, 'zzcolor', value: 'blue');
        $field->delete();
        $this->builder()->archiveField($field);

        $this->actingAs(User::role('superadmin')->firstOrFail());
        Filament::setCurrentPanel('admin');

        Livewire::test(BackupColumns::class, ['entity' => $entity])
            ->assertSee('_zzcolor')
            ->callAction(TestAction::make('restore')->table('zzcolor'), [
                'title' => 'Colour',
                'field_type' => 'string',
            ])
            ->assertHasNoFormErrors()
            ->assertDispatched('lara-custom-fields-changed')
            ->assertSee('No backup columns');

        $this->assertSame('blue', DB::table(self::TABLE)->value('zzcolor'));
    }

    public function test_the_backup_columns_table_deletes_a_backup_for_the_webmaster(): void
    {
        $entity = $this->makeBuiltEntity();
        $field = $this->makeFieldWithData($entity, 'zzcolor');
        $field->delete();
        $this->builder()->archiveField($field);

        $this->actingAs(User::role('superadmin')->firstOrFail());
        Filament::setCurrentPanel('admin');

        Livewire::test(BackupColumns::class, ['entity' => $entity])
            ->callAction(TestAction::make('delete')->table('zzcolor'))
            ->assertSee('No backup columns');

        $this->assertFalse(Schema::hasColumn(self::TABLE, '_zzcolor'));
    }

    public function test_the_entity_edit_page_shows_the_backup_columns_table(): void
    {
        $entity = $this->makeBuiltEntity();

        $this->actingAs(User::role('superadmin')->firstOrFail());
        Filament::setCurrentPanel('admin');

        Livewire::test(EditEntity::class, ['record' => $entity->id])
            ->assertOk()
            ->assertSeeLivewire(BackupColumns::class);
    }
}
