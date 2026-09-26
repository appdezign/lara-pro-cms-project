# Lara CMS

Lara CMS 10 is a content management system built on Laravel and Filament (admin panel). Content types ("entities") are configured in the database and code is generated from that configuration, so the database is as much a part of the application as the PHP code.

## Structure

- `Lara\Admin` → `laracms/core/src/admin`: Filament resources, pages and the code generator.
- `Lara\Common` → `laracms/core/src/common`: models, entity configuration, routes, casts, factories.
- `Lara\Front` → `laracms/core/src/front`: front controllers, widgets, theme engine.
- `Lara\App` → `laracms/app`: the site-specific entities (Blog, Team, Event, …), with their models, entities, policies, Filament resources, front controllers and migrations.
- `Laratheme\Base` / `Laratheme\Demo` → `laracms/themes/*`: front-end themes. The active theme is `config('lara.client_theme')`.
- `laracms/core` is a **symlink** to the separate `laracms10pack` package. Changes there change the shared core package, not only this site.
- Keep the existing directory structure. Traits live in `Concerns/` directories.

## Entities

- Every content type is a row in `lara_resource_entities` (`resource_slug`, `model_class`, `cgroup`, `col_has_*` flags), with custom fields in `lara_resource_entity_custom_fields` and relations in `lara_resource_entity_relations`.
- `cgroup` separates the kinds: `entity` (content), `page`, `block`, `form`, `taxonomy`.
- Content tables are named `lara_content_{resource_slug}`, forms `lara_form_*`, blocks `lara_blocks_*`. Every content record has `language` (`nl` / `en`), `publish`, `publish_from`, `slug` and `user_id`.
- Creating an entity in the admin runs `HasLaraBuilder` (`Lara\Admin\Concerns`), which writes PHP classes into `laracms/app` and alters the database schema. Treat generated classes as generated: fix the stubs (`laracms/core/src/admin/Stubs`) or the builder, not only the output.
- Runtime schema changes are intended (webmasters have no server or database access), but must go through the `build*` methods of `HasLaraBuilder` (`buildEntity`, `buildCustomField`, `buildExtraBodyColumns`): check first, change the schema in steps registered with an undo (`runSchemaSteps`), verify, and only then keep the new entity or field values. On failure, restore the row and notify; never leave config and schema out of sync. Field names are checked by `Lara\Common\Entities\EntityFieldName`, entity labels by `EntityLabel`.
- When a field is deleted, renamed or changes type, its old column is kept as a backup column (`_fieldname`). Webmasters restore or delete these in the admin (Custom fields tab → Backup columns, `Lara\Admin\Livewire\BackupColumns`). Never drop or overwrite a backup column silently; the builder refuses a change while an older backup is in the way.
- Array casts for json custom fields (multiselect, checkbox list, tags input, …) are derived by `BaseModel::getCustomFieldCasts()` from the entity config. Do not ask the webmaster to add casts to a model.
- Custom field types are defined in `Lara\Admin\Enums\CustomFieldType` (form component, column type, whether it has options). Admin form rendering lives in `HasContentSection`.
- Read entity configuration through `Lara\Common\Entities\EntityRegistry` (a singleton with one cache key, invalidated by `EntityConfigObserver`). Do not add new caches of entity configuration.
- Every model extends `Lara\Common\Models\BaseModel`.

## Translations

- Labels use `_q('module::group.tag.key')`, e.g. `_q('lara-app::blogs.column.title')`. Translations are stored in `lara_sys_translations`, not in lang files. A key must have exactly three dot-separated parts after `::`.

## Routing

- Front routes are built from the database (menu items, entities, tags) and are per locale (`mcamara/laravel-localization`).
- Cache routes with `php artisan lara:route:cache` only. **Never run `php artisan route:cache`**: it does not produce working per-locale routes.

## Testing

- The suite runs against a separate MySQL database, `d10_laracms_test` (set in `phpunit.xml`). It contains essential seed data (users, roles, entities, custom fields, settings, translations, menus, a few pages) and nearly empty content tables.
- Tests that write data must `use DatabaseTransactions`. **Never use `RefreshDatabase`**: it drops every table and would wipe the seed data.
- Run the suite with `composer test`, which clears the config cache first. A cached config makes `phpunit.xml` settings be ignored, and `Tests\TestCase` then fails every test on purpose.
- Content factories exist for `Page` and every model in `Lara\App\Models`. They all use `Lara\Common\Database\Factories\Concerns\HasLaraFactory`, which generates the standard columns, lead/body, every custom field type (option fields pick from `field_options`) and belongsTo relations. A belongsTo relation reuses a recycled or existing record in the same language before creating one. A new entity needs its own factory and a `newFactory()` on its model.
- `BaseResourceStructureTest` compares every resource's form and table with `tests/Feature/__snapshots__/base_resource_structure.json`. After an intended change, rebuild it with `LARA_REBUILD_BASELINE=1 php artisan test --filter=shared_form` and check the diff.
- Front controllers cannot be feature-tested yet: they set up their state inside `if (!App::runningInConsole())`.

## Databases

- Development database: `d10_laracms` (from `.env`). Test database: `d10_laracms_test`.
- Back up and restore with `php artisan database:backup --database=…` and `php artisan database:import {database}_baseline.sql --database=…`. Backups are stored in `storage/app/backups`. Always pass `--database` when working with the test database.

## Code style

- PHP files in `laracms/` use tabs for indentation; Pint is configured not to change indentation. Run `vendor/bin/pint --format agent {files}` on changed files (this is not a git repository, so `--dirty` does not work).
