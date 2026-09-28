<laravel-boost-guidelines>
=== .ai/laracms rules ===

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
- Menu routes are named after their URL by `Lara\Common\Routes\MenuRouteName` (`media/downloads` → `entitytag.docs.media.downloads.index`), never with a database ID. Their menu item, entity, method, tags and related routes travel with the route as a `FrontRouteContext` in the route action (`lara` key). Read that context (via `FrontEntityResolver` / `FrontActiveRoute`); do not parse or build route names by position, also not in templates (use `$activeroute->getSingleRoute()`, `getMenuRoute()`, `getTagRoute($tag)`).
- `menu_items.routename` must match the name the route file registers. After changing the naming, run `php artisan lara:menu:refresh-routenames` (with `--dry-run` first), then rebuild the route cache and clear the application cache (widgets cache rendered links).
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

- PHP (including the generator stubs) and Blade use four spaces (see `.editorconfig`). PHP is formatted by Pint with its default `laravel` preset; Blade is formatted with PhpStorm's own formatter, not with Pint (its `--blade` option would bring in Prettier and reformat far more than indentation).
- Run `vendor/bin/pint --format agent {files}` on changed PHP files. The project folder is not a git repository, so `--dirty` does not work there; the core and theme folders are separate git repositories.
- Pint skips any directory named `vendor`, including `lang/vendor` (the site's published translations): pass those files explicitly.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record a rule with `record-rule` only when the user explicitly asks for one. Instructions for the work at hand are not rules, no matter how emphatic: "remove this typo", "use X here" are work to do, not rules to record. Never record a rule on your own initiative, as a byproduct of a change, or to summarize what you just did. When the user does ask, pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Use `record-rule` rather than your native memory or notes tool, because native memory is personal and session-scoped, while only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
