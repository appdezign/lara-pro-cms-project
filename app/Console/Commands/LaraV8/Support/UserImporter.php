<?php

namespace App\Console\Commands\LaraV8\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Lara\Admin\Enums\LaraPermissions;
use Lara\Common\Models\Entity;
use Lara\Common\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * Imports the v8 users, roles, role permissions and role assignments.
 *
 * - Users keep their v8 id and password hash (written without the 'hashed' cast); v8
 *   username → name (the login name), v8 name → displayname, user_language → locale.
 *   The v8 users replace the essential superadmin of the seed (user decision 2026-11-16).
 * - A v8 admin (is_admin) gets only the v10 role superadmin, which passes every check.
 * - Roles are matched by name; a v8 role without users is skipped (user decision).
 * - Role permissions: for every subject that both v8 and v10 know, the role gets exactly
 *   the permissions of its v8 abilities (V8Map::permissions()); subjects only v10 knows
 *   keep the role's v10 permissions.
 *
 * Rerunnable: users are upserted by id; a v10 user that v8 does not have is reported,
 * and only removed with $prune (content of that user is removed with it).
 */
class UserImporter
{
    /**
     * v10 actions, longest first, to split a permission name into action and subject.
     */
    private const ACTIONS = ['view_any', 'delete_any', 'view', 'create', 'update', 'delete'];

    /** @var list<array{level: string, entity: string, message: string}> */
    private array $report = [];

    public function __construct(private readonly V8Source $source) {}

    /**
     * @return list<array{level: string, entity: string, message: string}>
     */
    public function run(bool $dryRun = false, bool $prune = false): array
    {
        $this->report = [];

        $v8Users = $this->source->connection()->table('lara_auth_users')->orderBy('id')->get()->keyBy('id');

        $this->importUsers($v8Users, $dryRun, $prune);
        $roles = $this->importRoles($v8Users, $dryRun);
        $this->importPermissions($roles, $dryRun);
        $this->importAssignments($v8Users, $roles, $dryRun);

        if (! $dryRun) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        return $this->report;
    }

    /**
     * The v10 lara_auth_users row of a v8 user.
     *
     * @return array<string, mixed>
     */
    public static function userRow(object $v8User, string $defaultLocale): array
    {
        return [
            'id' => $v8User->id,
            'name' => V8Source::isEmpty($v8User->username) ? $v8User->name : $v8User->username,
            'email' => (string) $v8User->email,
            'email_verified_at' => $v8User->email_verified_at,
            'firstname' => V8Source::isEmpty($v8User->firstname) ? null : $v8User->firstname,
            'middlename' => V8Source::isEmpty($v8User->middlename) ? null : $v8User->middlename,
            'lastname' => V8Source::isEmpty($v8User->lastname) ? null : $v8User->lastname,
            'displayname' => V8Source::isEmpty($v8User->name) ? null : $v8User->name,
            'locale' => V8Source::isEmpty($v8User->user_language) ? $defaultLocale : $v8User->user_language,
            'password' => $v8User->password,
            'api_token' => V8Source::isEmpty($v8User->api_token) ? null : $v8User->api_token,
            'created_at' => $v8User->created_at,
            'updated_at' => $v8User->updated_at,
            'deleted_at' => $v8User->deleted_at,
        ];
    }

    /**
     * @param  Collection<int, object>  $v8Users
     */
    private function importUsers(Collection $v8Users, bool $dryRun, bool $prune): void
    {
        $existing = DB::table('lara_auth_users')->get()->keyBy('id');
        $locale = (string) config('app.locale');

        foreach ($v8Users as $v8User) {
            $row = self::userRow($v8User, $locale);
            $label = "User {$row['id']} '{$row['name']}'";
            $current = $existing[$row['id']] ?? null;

            $emailTaken = $row['email'] !== '' && DB::table('lara_auth_users')
                ->where('email', $row['email'])
                ->where('id', '!=', $row['id'])
                ->exists();

            if ($emailTaken) {
                $this->add('error', 'user', "{$label}: the e-mail address {$row['email']} belongs to another v10 user; skipped.");

                continue;
            }

            if ($current === null) {
                if (! $dryRun) {
                    DB::table('lara_auth_users')->insert($row);
                }

                $this->add('create', 'user', $label.($dryRun ? ' would be created.' : ' created.'));

                continue;
            }

            $differences = array_keys(array_filter($row, fn ($value, string $column): bool => (string) $current->{$column} !== (string) $value, ARRAY_FILTER_USE_BOTH));

            if ($differences === []) {
                $this->add('same', 'user', "{$label} exists and matches.");

                continue;
            }

            $replaces = $current->name !== $row['name'] ? " (replaces v10 user '{$current->name}')" : '';

            if (! $dryRun) {
                DB::table('lara_auth_users')->where('id', $row['id'])->update($row);
            }

            $this->add('update', 'user', $label.($dryRun ? ' would be updated' : ' updated').$replaces.': '.implode(', ', $differences).'.');
        }

        foreach ($existing->keys()->diff($v8Users->keys()) as $id) {
            $name = $existing[$id]->name;

            if ($prune && ! $dryRun) {
                DB::table('lara_auth_users')->where('id', $id)->delete();
                $this->add('update', 'user', "v10 user {$id} '{$name}' is not in v8 and was removed (with its content).");
            } else {
                $this->add('differs', 'user', "v10 user {$id} '{$name}' is not in v8; kept (remove with --prune).");
            }
        }

        $profiles = $this->source->connection()->table('lara_auth_users_profiles')->count();

        if ($profiles > 0) {
            $this->add('info', 'user', "{$profiles} v8 user profile(s) (admin preferences such as dark mode) are not imported.");
        }
    }

    /**
     * v8 role id => v10 role, for the v8 roles that have users.
     *
     * @param  Collection<int, object>  $v8Users
     * @return array<int, object>
     */
    private function importRoles(Collection $v8Users, bool $dryRun): array
    {
        $roleModel = config('permission.models.role');
        $assigned = $this->v8Assignments()->filter(fn (object $row): bool => $v8Users->has($row->entity_id))->pluck('role_id')->unique();
        $roles = [];

        foreach ($this->source->connection()->table('lara_auth_roles')->orderBy('level', 'desc')->get() as $v8Role) {
            if (! $assigned->contains($v8Role->id)) {
                $this->add('info', 'role', "Role '{$v8Role->name}' has no users in v8; skipped.");

                continue;
            }

            $role = $roleModel::where('name', $v8Role->name)->where('guard_name', 'web')->first();

            if ($role !== null) {
                $this->add('same', 'role', "Role '{$v8Role->name}' exists in v10.");
            } elseif ($dryRun) {
                $role = (object) ['id' => null, 'name' => $v8Role->name];
                $this->add('create', 'role', "Role '{$v8Role->name}' would be created.");
            } else {
                $role = $roleModel::create([
                    'name' => $v8Role->name,
                    'guard_name' => 'web',
                    'has_panel_access' => (int) $v8Role->has_backend_access,
                    'level' => $v8Role->level,
                ]);
                $this->add('create', 'role', "Role '{$v8Role->name}' created (panel access: ".($v8Role->has_backend_access ? 'yes' : 'no').').');
            }

            $roles[$v8Role->id] = $role;
        }

        return $roles;
    }

    /**
     * @param  array<int, object>  $roles  v8 role id => v10 role
     */
    private function importPermissions(array $roles, bool $dryRun): void
    {
        $subjects = $this->ensurePermissions($dryRun);
        $abilities = $this->source->connection()
            ->table('lara_auth_has_abilities as h')
            ->join('lara_auth_abilities as a', 'a.id', '=', 'h.ability_id')
            ->where('h.entity_type', 'like', '%role%')
            ->where('h.forbidden', 0)
            ->get(['h.entity_id as role_id', 'a.name', 'a.entity_key']);

        $v8Subjects = $abilities->pluck('entity_key')->unique();

        foreach ($v8Subjects->diff($subjects)->sort() as $subject) {
            $this->add('info', 'permission', "v8 subject '{$subject}' has no v10 policy; its abilities are not imported.");
        }

        $shared = $v8Subjects->intersect($subjects)->values()->all();

        foreach ($roles as $v8RoleId => $role) {
            if ($role->id === null || $role->name === 'superadmin') {
                continue;
            }

            $wanted = $abilities->where('role_id', $v8RoleId)
                ->filter(fn (object $ability): bool => in_array($ability->entity_key, $shared, true))
                ->flatMap(fn (object $ability): array => V8Map::permissions($ability->name, $ability->entity_key))
                ->unique()->sort()->values()->all();

            $current = $role->permissions()->pluck('name')
                ->filter(fn (string $permission): bool => in_array($this->subject($permission), $shared, true))
                ->sort()->values()->all();

            $give = array_values(array_diff($wanted, $current));
            $revoke = array_values(array_diff($current, $wanted));

            if ($give === [] && $revoke === []) {
                $this->add('same', 'role', "Role '{$role->name}': permissions match v8 for the ".count($shared).' shared subjects.');

                continue;
            }

            if (! $dryRun) {
                $role->givePermissionTo($give);
                $role->revokePermissionTo($revoke);
            }

            $this->add('update', 'role', "Role '{$role->name}': ".($dryRun ? 'would give ' : 'gave ').count($give).', '.($dryRun ? 'would revoke ' : 'revoked ').count($revoke)
                .' permission(s) to match v8'.($revoke !== [] ? ' (revoked: '.implode(', ', $revoke).')' : '').'.');
        }
    }

    /**
     * Create the permissions of every registered policy, as the Role screen does
     * (RoleForm::checkEntityPermissions()), and return every known subject.
     *
     * @return list<string>
     */
    private function ensurePermissions(bool $dryRun): array
    {
        $permissionModel = config('permission.models.permission');

        // the site policies are only registered for web requests (LaraAppServiceProvider);
        // register them here too, so the console sees what the Role screen sees
        foreach (Entity::whereNotNull('policy')->get() as $entity) {
            if (class_exists($entity->model_class) && class_exists($entity->policy)) {
                Gate::policy($entity->model_class, $entity->policy);
            }
        }

        $subjects = array_values(array_unique(array_map(
            fn (string $model): string => strtolower(class_basename($model)),
            array_keys(Gate::policies()),
        )));

        $created = 0;

        foreach ($subjects as $subject) {
            foreach (LaraPermissions::cases() as $action) {
                $name = $action->value.'_'.$subject;

                if (! $permissionModel::where('name', $name)->where('guard_name', 'web')->exists()) {
                    $created++;

                    if (! $dryRun) {
                        $permissionModel::create(['name' => $name, 'guard_name' => 'web']);
                    }
                }
            }
        }

        if ($created > 0) {
            $this->add('create', 'permission', "{$created} permission(s) ".($dryRun ? 'would be' : 'were').' created for the registered policies.');
        }

        // policies found by Laravel's auto-discovery (e.g. UserPolicy) are not in Gate::policies(),
        // but their permissions exist (seeded); those subjects count as known too
        $seeded = $permissionModel::where('guard_name', 'web')->pluck('name')
            ->map(fn (string $permission): ?string => $this->subject($permission))
            ->filter()->unique()->values()->all();

        return array_values(array_unique([...$subjects, ...$seeded]));
    }

    /**
     * @param  Collection<int, object>  $v8Users
     * @param  array<int, object>  $roles  v8 role id => v10 role
     */
    private function importAssignments(Collection $v8Users, array $roles, bool $dryRun): void
    {
        $assignments = $this->v8Assignments();

        foreach ($assignments->reject(fn (object $row): bool => $v8Users->has($row->entity_id))->groupBy('entity_id') as $userId => $rows) {
            $this->add('warning', 'user', "v8 role assignment(s) for user {$userId}, who no longer exists; skipped.");
        }

        foreach ($v8Users as $v8User) {
            $wanted = $v8User->is_admin
                ? ['superadmin']
                : $assignments->where('entity_id', $v8User->id)
                    ->map(fn (object $row): ?string => $roles[$row->role_id]->name ?? null)
                    ->filter()->unique()->sort()->values()->all();

            $user = User::withTrashed()->find($v8User->id);
            $current = $user ? $user->roles()->pluck('name')->sort()->values()->all() : [];
            $label = "User {$v8User->id} '".(V8Source::isEmpty($v8User->username) ? $v8User->name : $v8User->username)."'";

            if ($current === $wanted) {
                $this->add('same', 'user', "{$label}: roles ".($wanted === [] ? '(none)' : implode(', ', $wanted)).'.');

                continue;
            }

            if (! $dryRun && $user !== null) {
                $user->syncRoles($wanted);
            }

            $this->add('update', 'user', "{$label}: roles ".(implode(', ', $current) ?: '(none)').' → '.(implode(', ', $wanted) ?: '(none)').($v8User->is_admin ? ' (v8 admin)' : '').'.');
        }
    }

    /**
     * @return Collection<int, object>
     */
    private function v8Assignments(): Collection
    {
        return $this->source->connection()->table('lara_auth_has_roles')
            ->where('entity_type', 'like', '%User')
            ->get(['role_id', 'entity_id']);
    }

    private function subject(string $permission): ?string
    {
        foreach (self::ACTIONS as $action) {
            if (str_starts_with($permission, $action.'_')) {
                return substr($permission, strlen($action) + 1);
            }
        }

        return null;
    }

    private function add(string $level, string $entity, string $message): void
    {
        $this->report[] = ['level' => $level, 'entity' => $entity, 'message' => $message];
    }
}
