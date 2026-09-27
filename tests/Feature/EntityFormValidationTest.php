<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Validator;
use Lara\Admin\Resources\Entities\Schemas\EntityForm;
use Lara\Admin\Resources\Forms\Schemas\FormForm;
use Lara\App\Filament\Resources\Blogs\Pages\CreateBlog;
use Lara\Common\Models\Entity;
use Lara\Common\Models\User;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The entity label rule, exercised the way Filament actually uses it.
 *
 * Filament evaluates anything passed to ->rules() as one of its own closures,
 * injecting parameters by name. A bare Laravel rule - function ($attribute,
 * $value, $fail) - therefore dies with "[$attribute] was unresolvable" the
 * first time a form is submitted. The rule has to be returned from a
 * parameterless closure instead.
 *
 * Calling Validator::make() with the rule array does not catch this, because
 * it skips Filament's evaluation entirely. These tests go through
 * getValidationRules(), which is the step that broke.
 */
class EntityFormValidationTest extends TestCase
{
    /**
     * @param  class-string  $schemaClass
     * @param  non-empty-string  $method
     */
    private function privateStatic(string $schemaClass, string $method): mixed
    {
        $reflection = new ReflectionMethod($schemaClass, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke(null);
    }

    /**
     * The label rules as Filament resolves them for a given operation.
     *
     * The field has to sit in a real Schema, because the rule condition is
     * evaluated against the schema's operation.
     *
     * @param  class-string  $schemaClass
     * @return array<int, mixed>
     */
    private function resolvedLabelRules(string $schemaClass, string $operation = 'create'): array
    {
        $user = User::where('name', 'admin')->first();

        if (! $user) {
            $this->markTestSkipped('No "admin" user in the current database.');
        }

        $this->actingAs($user);
        Filament::setCurrentPanel('admin');

        // any mountable resource page will do as a schema container
        $livewire = Livewire::test(CreateBlog::class)->instance();

        $schema = Schema::make($livewire)
            ->operation($operation)
            ->components([
                TextInput::make('label_single')->rules(
                    $this->privateStatic($schemaClass, 'getEntityLabelRules'),
                    $this->privateStatic($schemaClass, 'getEntityLabelRuleCondition'),
                ),
            ]);

        return $schema->getFlatFields(withHidden: true)['label_single']->getValidationRules();
    }

    /**
     * The helper text as Filament resolves it for a given operation.
     *
     * Mirrors what helperText() does internally: evaluate the value against
     * the component. A blank result renders no helper text at all.
     */
    private function resolvedHelperText(string $schemaClass, string $operation): ?string
    {
        $user = User::where('name', 'admin')->first();

        if (! $user) {
            $this->markTestSkipped('No "admin" user in the current database.');
        }

        $this->actingAs($user);
        Filament::setCurrentPanel('admin');

        $livewire = Livewire::test(CreateBlog::class)->instance();

        $field = Schema::make($livewire)
            ->operation($operation)
            ->components([TextInput::make('label_single')])
            ->getFlatFields(withHidden: true)['label_single'];

        return $field->evaluate($this->privateStatic($schemaClass, 'getEntityLabelHelperText'));
    }

    /**
     * The helper text explains what to name a new entity. On edit the field is
     * disabled and the value cannot change, so the guidance is just noise.
     */
    public function test_the_label_helper_text_is_only_shown_when_creating(): void
    {
        foreach ([EntityForm::class, FormForm::class] as $schemaClass) {
            $onCreate = $this->resolvedHelperText($schemaClass, 'create');
            $onEdit = $this->resolvedHelperText($schemaClass, 'edit');

            $this->assertNotEmpty($onCreate, class_basename($schemaClass).' should show helper text when creating.');
            $this->assertStringContainsString('lowercase', (string) $onCreate);

            $this->assertTrue(
                blank($onEdit),
                class_basename($schemaClass).' should show no helper text when editing, got: '.var_export($onEdit, true)
            );
        }
    }

    public function test_entity_form_label_rules_survive_filament_evaluation(): void
    {
        $rules = $this->resolvedLabelRules(EntityForm::class);

        $this->assertNotEmpty($rules);
    }

    public function test_form_form_label_rules_survive_filament_evaluation(): void
    {
        $rules = $this->resolvedLabelRules(FormForm::class);

        $this->assertNotEmpty($rules);
    }

    public function test_the_resolved_rules_still_reject_unusable_labels(): void
    {
        $rules = $this->resolvedLabelRules(EntityForm::class);

        $rejected = [
            'Nieuws bericht' => 'contains a space',
            'Product' => 'is not lowercase',
            'class' => 'is a reserved word',
            'blog' => 'is already taken',
        ];

        foreach ($rejected as $label => $why) {
            $this->assertTrue(
                Validator::make(['label_single' => $label], ['label_single' => $rules])->fails(),
                'Label "'.$label.'" must be rejected: it '.$why.'.'
            );
        }
    }

    public function test_the_resolved_rules_accept_a_usable_label(): void
    {
        $rules = $this->resolvedLabelRules(EntityForm::class);

        $this->assertTrue(
            Validator::make(['label_single' => 'zzvalidfixture'], ['label_single' => $rules])->passes(),
            'A valid, unused, lowercase label must be accepted.'
        );
    }

    /**
     * On edit the label is disabled and cannot change, but it is still
     * submitted and validated. The uniqueness checks would match the record
     * being edited and reject its own unchanged value, making every existing
     * entity impossible to save.
     */
    public function test_editing_accepts_the_entitys_own_existing_label(): void
    {
        $existing = Entity::whereNotNull('label_single')->value('label_single');

        $this->assertNotNull($existing, 'No entities to check.');

        $editRules = $this->resolvedLabelRules(EntityForm::class, 'edit');

        $this->assertTrue(
            Validator::make(['label_single' => $existing], ['label_single' => $editRules])->passes(),
            'Editing an entity must accept its own label "'.$existing.'".'
        );

        // ...while creating a new one with that label is still rejected
        $createRules = $this->resolvedLabelRules(EntityForm::class, 'create');

        $this->assertTrue(
            Validator::make(['label_single' => $existing], ['label_single' => $createRules])->fails(),
            'Creating a duplicate of "'.$existing.'" must still be rejected.'
        );
    }

    /**
     * An entity created before these naming rules existed may have a label that
     * no longer satisfies them. It must still be editable.
     */
    public function test_editing_accepts_a_label_that_would_fail_the_naming_rules(): void
    {
        $editRules = $this->resolvedLabelRules(EntityForm::class, 'edit');

        foreach (['Legacy Label', 'OldStyle', 'class'] as $legacy) {
            $this->assertTrue(
                Validator::make(['label_single' => $legacy], ['label_single' => $editRules])->passes(),
                'Editing must not reject the pre-existing label "'.$legacy.'".'
            );
        }
    }
}
