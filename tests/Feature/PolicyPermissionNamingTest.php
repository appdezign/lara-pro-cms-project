<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Lara\Admin\Enums\LaraPermissions;
use Lara\Admin\Resources\Base\Concerns\HasBasePolicy;
use Lara\Common\Models\Entity;
use Lara\Common\Models\User;
use Tests\TestCase;

/**
 * Every permission a policy checks must be one the Role screen can grant.
 *
 * The Role screen (RoleForm / EditRole) derives permission names from the model
 * class: `{action}_{lowercase class basename}`, e.g. `view_any_larawidget` for
 * LaraWidget. WidgetPolicy used to check `view_any_widget` instead, so ticking
 * the widget boxes did nothing and only the superadmin could manage widgets.
 * The admin resources had the same mismatch in HasBasePolicy, which is what actually
 * blocked the widget screens. Nothing failed, because the admin tests count a 403 as
 * an acceptable outcome.
 */
class PolicyPermissionNamingTest extends TestCase
{
    /**
     * Policy method => the LaraPermissions action the Role screen grants for it.
     */
    private const METHOD_ACTIONS = [
        'viewAny' => LaraPermissions::ViewAny,
        'view' => LaraPermissions::View,
        'create' => LaraPermissions::Create,
        'update' => LaraPermissions::Update,
        'delete' => LaraPermissions::Delete,
        'deleteAny' => LaraPermissions::DeleteAny,
    ];

    public function test_every_policy_only_checks_permissions_the_role_screen_can_grant(): void
    {
        $checked = 0;
        $mismatches = [];

        foreach ($this->policies() as $modelClass => $policyClass) {
            $modelName = strtolower(class_basename($modelClass));
            $policy = app($policyClass);

            foreach (self::METHOD_ACTIONS as $method => $action) {
                if (! method_exists($policy, $method)) {
                    continue;
                }

                $user = $this->spyUser();
                $arguments = in_array($method, ['viewAny', 'create', 'deleteAny'], true) ? [] : [new $modelClass];

                $policy->{$method}($user, ...$arguments);

                foreach ($user->checkedAbilities as $ability) {
                    $checked++;
                    $expected = $action->value.'_'.$modelName;

                    if ($ability !== $expected) {
                        $mismatches[] = class_basename($policyClass).'::'.$method.'() checks "'.$ability.'", the Role screen grants "'.$expected.'"';
                    }
                }
            }
        }

        $this->assertGreaterThan(0, $checked, 'No policy checked any permission.');
        $this->assertSame([], $mismatches);
    }

    /**
     * The admin resources do not use the policies: HasBasePolicy checks the permissions itself.
     * It used to derive the names from the resource slug, which gave "widget" for WidgetResource.
     */
    public function test_every_admin_resource_only_checks_permissions_the_role_screen_can_grant(): void
    {
        $checked = 0;
        $mismatches = [];

        foreach (Filament::getPanel('admin')->getResources() as $resourceClass) {
            if (! in_array(HasBasePolicy::class, class_uses_recursive($resourceClass), true)) {
                continue;
            }

            $modelClass = $resourceClass::getModel();

            // the abstract base resources have no pages of their own
            if (! class_exists($modelClass) || $resourceClass::getPages() === []) {
                continue;
            }

            $modelName = strtolower(class_basename($modelClass));
            $allowed = array_map(fn (LaraPermissions $action): string => $action->value.'_'.$modelName, LaraPermissions::cases());

            $user = $this->spyUser();
            $this->actingAs($user);

            $resourceClass::canViewAny();
            $resourceClass::canCreate();
            $resourceClass::canEdit(new $modelClass);
            $resourceClass::canDelete(new $modelClass);

            foreach ($user->checkedAbilities as $ability) {
                $checked++;

                if (! in_array($ability, $allowed, true)) {
                    $mismatches[] = class_basename($resourceClass).' checks "'.$ability.'", the Role screen grants *_'.$modelName;
                }
            }
        }

        $this->assertGreaterThan(0, $checked, 'No resource checked any permission.');
        $this->assertSame([], $mismatches);
    }

    /**
     * The policies registered in code, and the ones entities register from their `policy` column.
     * The latter are only registered for web requests, so they are read from the table here.
     *
     * @return array<class-string, class-string> model class => policy class
     */
    private function policies(): array
    {
        $policies = Gate::policies();

        foreach (Entity::whereNotNull('policy')->whereNotNull('model_class')->get() as $entity) {
            $policies[$entity->model_class] ??= $entity->policy;
        }

        return array_filter(
            $policies,
            fn (string $policyClass, string $modelClass): bool => class_exists($policyClass) && class_exists($modelClass),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * A user that records which permissions a policy asks for, and has none of them.
     */
    private function spyUser(): User
    {
        return new class extends User
        {
            /** @var list<string> */
            public array $checkedAbilities = [];

            public function can($abilities, $arguments = [])
            {
                array_push($this->checkedAbilities, ...(array) $abilities);

                return false;
            }
        };
    }
}
