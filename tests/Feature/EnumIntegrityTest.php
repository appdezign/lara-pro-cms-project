<?php

namespace Tests\Feature;

use Lara\Admin\Enums\CustomFieldType;
use Lara\Admin\Enums\EntityHook;
use Lara\Admin\Enums\EntityOrder;
use Lara\Admin\Enums\EntityViewTags;
use Lara\Admin\Enums\FormFieldType;
use Lara\Admin\Enums\FormHook;
use Lara\Common\Models\Entity;
use Lara\Common\Models\EntityCustomField;
use Lara\Common\Models\EntityView;
use Tests\TestCase;

/**
 * Backed enum *values* are persisted in the database, so they are part of the
 * schema even though they live in PHP. Renaming a case is safe; changing its
 * value silently orphans every existing row.
 *
 * These tests assert that every value currently stored still maps to a case.
 */
class EnumIntegrityTest extends TestCase
{
    /**
     * @param  list<class-string<\BackedEnum>>  $enums
     * @return array{int, list<string>}
     */
    private function resolveAll(iterable $values, array $enums): array
    {
        $checked = 0;
        $unresolved = [];

        foreach (collect($values)->unique()->filter() as $value) {
            $checked++;

            foreach ($enums as $enum) {
                if ($enum::tryFrom((string) $value) !== null) {
                    continue 2;
                }
            }

            $unresolved[] = (string) $value;
        }

        return [$checked, $unresolved];
    }

    public function test_every_stored_custom_field_type_maps_to_a_case(): void
    {
        [$checked, $unresolved] = $this->resolveAll(
            EntityCustomField::pluck('field_type'),
            [CustomFieldType::class, FormFieldType::class]
        );

        $this->assertGreaterThan(0, $checked, 'No custom field types to check.');
        $this->assertSame([], $unresolved, 'Stored field_type values with no matching enum case.');
    }

    public function test_every_stored_field_hook_maps_to_a_case(): void
    {
        [$checked, $unresolved] = $this->resolveAll(
            EntityCustomField::pluck('field_hook'),
            [EntityHook::class, FormHook::class]
        );

        $this->assertGreaterThan(0, $checked, 'No field hooks to check.');
        $this->assertSame([], $unresolved, 'Stored field_hook values with no matching enum case.');
    }

    public function test_every_stored_sort_order_maps_to_a_case(): void
    {
        [, $unresolved] = $this->resolveAll(
            Entity::pluck('sort_primary_order')->merge(Entity::pluck('sort_secondary_order')),
            [EntityOrder::class]
        );

        $this->assertSame([], $unresolved, 'Stored sort order values with no matching enum case.');
    }

    public function test_every_stored_showtags_value_maps_to_a_case(): void
    {
        [, $unresolved] = $this->resolveAll(
            EntityView::pluck('showtags'),
            [EntityViewTags::class]
        );

        $this->assertSame([], $unresolved, 'Stored showtags values with no matching enum case.');
    }

    /**
     * Exercise every no-argument method on every case of every enum.
     *
     * Enum methods are dominated by match() arms over the cases. A stale case
     * reference in one of those arms is not a syntax error and is only reached
     * when that arm is evaluated, so it survives both linting and a normal test
     * run. Calling every method with every case is what actually catches it.
     */
    public function test_every_enum_method_works_for_every_case(): void
    {
        $failures = [];
        $calls = 0;

        foreach (glob(base_path('laracms/core/src/admin/Enums/*.php')) as $file) {
            $enum = 'Lara\\Admin\\Enums\\'.basename($file, '.php');

            if (! enum_exists($enum)) {
                continue;
            }

            $methods = array_filter(
                (new \ReflectionEnum($enum))->getMethods(\ReflectionMethod::IS_PUBLIC),
                fn (\ReflectionMethod $m) => ! $m->isStatic()
                    && $m->getNumberOfRequiredParameters() === 0
                    && ! in_array($m->getName(), ['cases', 'from', 'tryFrom'], true)
            );

            foreach ($enum::cases() as $case) {
                foreach ($methods as $method) {
                    try {
                        $method->invoke($case);
                        $calls++;
                    } catch (\Throwable $e) {
                        $failures[] = class_basename($enum).'::'.$case->name
                            .'->'.$method->getName().'() : '.$e->getMessage();
                    }
                }
            }
        }

        $this->assertGreaterThan(0, $calls, 'No enum methods were exercised.');
        $this->assertSame([], $failures, 'Enum methods failed for some cases.');
    }

    /**
     * Every enum case name must be TitleCase, per the project convention.
     */
    public function test_all_enum_cases_are_title_case(): void
    {
        $offenders = [];

        foreach (glob(base_path('laracms/core/src/admin/Enums/*.php')) as $file) {
            $enum = 'Lara\\Admin\\Enums\\'.basename($file, '.php');

            if (! enum_exists($enum)) {
                continue;
            }

            foreach ($enum::cases() as $case) {
                if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $case->name) !== 1) {
                    $offenders[] = class_basename($enum).'::'.$case->name;
                }
            }
        }

        $this->assertSame([], $offenders, 'Enum cases must be TitleCase.');
    }
}
