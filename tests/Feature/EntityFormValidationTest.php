<?php

namespace Tests\Feature;

use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Validator;
use Lara\Admin\Resources\Entities\Schemas\EntityForm;
use Lara\Admin\Resources\Forms\Schemas\FormForm;
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
     * @param class-string $schemaClass
     * @return array<int, mixed> rules as Filament resolves them
     */
    private function resolvedLabelRules(string $schemaClass): array
    {
        $method = new ReflectionMethod($schemaClass, 'getEntityLabelRules');
        $method->setAccessible(true);

        return TextInput::make('label_single')
            ->rules($method->invoke(null))
            ->getValidationRules();
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
            'Product'        => 'is not lowercase',
            'class'          => 'is a reserved word',
            'blog'           => 'is already taken',
        ];

        foreach ($rejected as $label => $why) {
            $this->assertTrue(
                Validator::make(['label_single' => $label], ['label_single' => $rules])->fails(),
                'Label "' . $label . '" must be rejected: it ' . $why . '.'
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
}
