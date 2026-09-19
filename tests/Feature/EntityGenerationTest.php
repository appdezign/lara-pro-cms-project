<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Lara\Admin\Concerns\HasLaraBuilder;
use Lara\Common\Entities\EntityLabel;
use Lara\Common\Entities\EntityRegistry;
use Lara\Common\Models\Entity;
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

    protected function tearDown(): void
    {
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
}
