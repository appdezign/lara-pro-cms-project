# Lara CMS v8 → v10 migration runbook

For sites on Lara CMS **v8.2.46**. Everything for the migration lives on the `v8-migration` branch of `lara-pro-cms-project`:

- `app/Console/Commands/LaraV8/`: the `lara:v8:*` import commands
- `tests/Feature/LaraV8/`: their tests (skipped on a site without a v8 source)
- `config/v8-migration.php` and the `lara_v8` connection in `config/database.php`
- this runbook

Fixes found while migrating a site are committed to this branch, so the next site starts with them. Merge `main` into the branch after each v10 release.

## Local setup per site

| What | Example (anpak) |
| --- | --- |
| v8 reference site (Herd, PHP 8.1) | `__migrate/anpak8246`, db `anpak8246` |
| v8 source snapshot (production dump, read-only) | db `v8_anpak` |
| v10 target (Herd, PHP 8.4) | `__migrate/anpak1077`, db `anpak1077` |

The v8 reference site is for clicking through the old site and admin. Its database changes as soon as it is used, so the import never reads from it; it reads from the snapshot.

### 1. v10 target

```bash
git clone -b v8-migration https://github.com/appdezign/lara-pro-cms-project.git {site}
cd {site}
mysql -h127.0.0.1 -uroot -e "CREATE DATABASE {db} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
cp .env.example .env    # then fill in APP_*, DB_*, LARA_* (see Gotchas)
composer install
php artisan key:generate
php artisan storage:link

# demo install (the same steps as the setup wizard, HasSetup)
php artisan migrate:fresh --force --path=laracms/core/src/common/Database/Migrations
php artisan migrate --force --path=laracms/app/Database/Migrations
php artisan db:seed --force --class='\Lara\App\Database\Seeders\DatabaseDemoSeeder'
php artisan optimize:clear

npm install
npx vite build --config vite.admin.config.js
npx vite build --config vite.front.base.config.js
npx vite build --config vite.front.demo.config.js

herd link {site} && herd secure {site}
composer test
```

Keep `LARA_CLIENT_THEME=demo` until the client theme exists.

### 2. v8 source snapshot

```bash
mysql -h127.0.0.1 -uroot -e "CREATE DATABASE v8_{site} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -h127.0.0.1 -uroot v8_{site} < {production-dump}.sql

# read-only user (once per machine; add a GRANT per site)
mysql -h127.0.0.1 -uroot -e "CREATE USER 'lara_v8_ro'@'127.0.0.1' IDENTIFIED BY '{password}'; CREATE USER 'lara_v8_ro'@'localhost' IDENTIFIED BY '{password}'"
mysql -h127.0.0.1 -uroot -e "GRANT SELECT ON \`v8_{site}\`.* TO 'lara_v8_ro'@'127.0.0.1'; GRANT SELECT ON \`v8_{site}\`.* TO 'lara_v8_ro'@'localhost'; FLUSH PRIVILEGES"
```

In `.env` of the v10 target:

```dotenv
LARA_V8_DB_DATABASE=v8_{site}
LARA_V8_DB_USERNAME=lara_v8_ro
LARA_V8_DB_PASSWORD={password}
LARA_V8_MEDIA_PATH=/absolute/path/to/v8/public/assets/media
```

Check: `php artisan test --compact tests/Feature/LaraV8` (the connection reads, cannot write, and the media path exists).

### 3. Inventory

```bash
php artisan lara:v8:inventory              # console tables
php artisan lara:v8:inventory --markdown   # save as docs/migrate-v8/sites/{site}-inventory.md
```

It lists the content types (rows per language, trashed, fields, relations), the system screens, custom field types, hooks and states, relations, object relations and media settings, front views, media rows, media files on disk and other data. It ends with the **findings**: v8 values without a v10 mapping, mappings that change behaviour, and inconsistent v8 data. Compare the findings with the previous sites: a new kind of finding probably needs a new mapping in `V8Map` or a fix in the import.

### 4. Essential base

```bash
php artisan lara:v8:prepare-target    # asks for confirmation; --force to skip
```

Removes the demo content types from `laracms/app` (`Models`, `Entities`, `Policies`, `Filament/Resources`, `Http/Controllers/Front/{Entity,Form}`, `Database/{Factories,Migrations,Seeders}`), runs `composer dump-autoload`, then `migrate:fresh` on the core migrations, `DatabaseCommonSeeder` and `optimize:clear`. Legacy, providers, routes, config, custom blocks and the other front controllers stay. It is also the way to start over: it removes generated site code as well.

After this the full v10 test suite (`composer test`) no longer applies to the site (it targets the demo entities); run `php artisan test --compact tests/Unit/LaraV8 tests/Feature/LaraV8`.

The migration tests in short: tests marked "skipped without a v8 source" run against the site's own `lara_v8` snapshot and only read. `ImportEntitiesBuildTest` does the real build on two throwaway databases, `lara_v8_fixture` (loaded from `tests/Fixtures/LaraV8/v8_fixture.sql`: three content types `v8fxbook`, `v8fxevent`, `v8fxform` that cover geolocation, duplicates, conditions, a relation and a form) and `lara_v8_build_test` (an essential base); it removes the generated `v8fx…` code afterwards and is skipped when the MySQL user cannot create databases. Extend the fixture when a site shows a new case.

**Never commit** the changes in `laracms/app` (removed demo code, generated site code) to the `v8-migration` branch; they belong to the site, not to the migration tooling.

### 5. Content types

```bash
php artisan lara:v8:import-entities --dry-run   # plan
php artisan lara:v8:import-entities             # build; -v shows every step
php artisan lara:v8:import-entities --update    # apply differences to existing ones
```

Builds every **site** content type through the admin builder (`HasLaraBuilder`): code, table, custom fields, front views and relations (`HasRelations`, which adds the `belongsTo` side and the foreign key). The v8 title is kept (`createEntity` would rename it after the model). Rerunnable: what exists and matches is reported as `same`; a second run must give only `same`, `info`, `warning` and `todo`. Core content types are not touched.

For the **core** content types the import changes only one thing: values that live v8 records (published, not trashed) use in an option field but the v10 options lack are added to those options (`CoreFieldValues`). Values only used by unpublished or trashed records are not added; their records keep the value. The inventory section "Core field values" shows both.

What the builder leaves to a developer, the import does or reports:
- **Relation methods**: added to the generated site models when missing (`RelationMethodWriter`, standard Eloquent `hasMany`/`hasOne`/`belongsTo` on the foreign key, marked with a comment), e.g. `Location::events()` and `Event::location()`. A relation on a core model is reported as `todo`.
- **Form front controllers**: generated by the builder since v10.7.18 (`Front/Form/{Name}sController`, extends `AppFormController`). On older versions the import reports them as `todo`.

### 6. Custom v8 code

Most of `laracms/app` in a v8 site is generated or stock v8 code, which v10 replaces. To find the real customizations, compare every file with the same file in a few other v8.2.46 sites: a file identical in another site is stock. Per-entity code of an entity only this site has (e.g. `Models/Client.php`) is generated when it only differs from another entity's file (e.g. `Blog`) by its name.

```bash
# in the v8 site's laracms/app: list the files no other site has identically
for f in $(find . -name '*.php' -not -path './Resources/Lang/*'); do
  same=0; for s in {other v8 sites}; do cmp -s "$f" "$s/laracms/app/$f" && same=1; done
  [ $same -eq 0 ] && echo "$f"
done
```

For each customization, check whether the site still uses it (menu item published, recent submissions or records) before porting it. Port only what is used, preferably as a Legacy module (see `CustomBlog`).

## v8 → v10 mapping (`V8Map`)

`app/Console/Commands/LaraV8/Support/V8Map.php` holds every translation of v8 configuration; it covers all values the v8.2.46 core knows, so it should not need changes per site. Unit tests: `tests/Unit/LaraV8/V8MapTest.php` (they also fail when a v10 release renames a field type, hook or nav group).

- **Core content types** (page, larawidget, cta, slider) keep their v10 configuration (fields, views, code). Only their data is imported, with column renames: larawidget → `widgets` / `lara_blocks_widgets`, `relentkey` → `resource_slug`, `term` → `resource_term`, `imgreq` → `image_required`, `maxitems` → `max_items`, `isglobal` → `is_global`; `customtags` and page `show_in_app` are dropped.
- **Core field values**: v8 Bootstrap colour `primary` → v10 `brand` (cta) / `brand1` (slider) (`V8Map::CORE_VALUES`). Dynamic options (`get_widget_templates`, `get_entity_resources`, `get_entity_terms`) come from the theme: check that the client theme has the widget templates the v8 data uses.
- **Site content types** are rebuilt from the v8 configuration: `resource_slug` = plural key, model `Lara\App\Models\{Key}`.
- **Field types**: `text` → `textarea` (v10 `text` is a single line), `mcefull`/`mcemin` → `richeditor`/`richeditormin`, `integer`/`intunsigned` → `number`, `decimal108`/`decimal118` → `latitude_10_8`/`longitude_11_8`, `boolean`/`yesno` → `toggle`, `selectone`/`selectone2one` → `select`, `color` → `colorpicker`, `video` → `string`, `custom` → `textarea`; same name for the rest. Forms use the v10 form field types. Changes in behaviour are reported (`FIELD_TYPE_CHANGES`).
- **Hooks**: `before` → `before-title`, `between` → `after-slug`, `after` → `after-last`, `default` → `default` (same position in the form). States and operators keep their names. v8 options `{"0":"a","1":"b"}` become `["a","b"]`.
- A v8 condition with several values (`feature || featuresmall`) is not supported by v10, which compares one value; reported.
- **Entity settings**: `lara_ent_entity_columns`, `_panels` and `_object_relations` map to the `col_has_*`, `sort_*`, `show_*`, `objrel_*`, `filter_*` and `media_*` columns of `lara_resource_entities`; `max_images > 1` means a gallery; disk `localdisk` → `public`. Settings without a v8 source keep their v10 value or default.
- **Permissions**: v8 ability `{action}` on `{entity_key}` → v10 `{action}_{entity_key}`; `view` also gives `view_any`, `delete` also `delete_any`. v8 subjects without a v10 permission (headertag, language, redirect, seo, tag) are skipped.

## v8 facts (v8.2.46)

- Media are on disk `localdisk` = `public/assets/media`, one folder per entity key, each with `_trash` and `_archive` subfolders and an `.htaccess`.
- **`_archive` is not a bin**: live records can refer to files that only exist in `{entity}/_archive` (anpak: 5 docs). Look there when a file is not in the entity folder (`V8Source::mediaFile()`). `_trash` is never used.
- Media folders also hold files no media row refers to (old uploads, deleted media); they are not imported.
- `lara_object_images.entity_type` holds full v8 class names (`Eve\Models\Blog`, `Lara\Common\Models\Page`); `filename` is relative to the entity folder.
- v8 stores "no value" as SQL `NULL`, an empty string or the **string `'NULL'`** (e.g. `condition_field`); treat all three as empty (`V8Source::isEmpty()`).
- A custom field can be defined twice in `lara_ent_entity_custom_columns` (anpak: `location.latitude`, `location.longitude`); import it once.
- Content tables: pages and entities `lara_content_{plural key}`, blocks `lara_blocks_{plural key}`, forms `lara_form_{plural key}` (`V8Source::contentTable()`). The other entity groups (menu, tag, auth, builder, tools, seo) are system screens, not content types.
- Tables are `utf8mb3` / `utf8_unicode_ci`; they import fine into MySQL 5.7.

## Gotchas

- **Never copy the laracms10 development folder** to start a site. Its `laracms/core` and `laracms/themes/{base,demo}` are symlinks into the git-controlled `laracms10pack`, and `composer install` would write through them. Always clone the project repo.
- Artisan cannot boot without an existing database (`HasSetup`), so create the database before `key:generate`.
- Plain `php artisan migrate` finds nothing: the migrations live in `laracms/core/src/common/Database/Migrations` and `laracms/app/Database/Migrations` and must be run with `--path` (see step 1).
- `.env.example` on `main` is the stock Laravel file (sqlite, no `LARA_*` keys). Base the `.env` on a working v10 site and replace name, URL, database, key and third-party keys (Google Maps, reCAPTCHA, GA4).
- `public/storage` on `main` is a committed symlink to a developer folder; `storage:link` replaces it locally. Do not commit that change.
- `herd link` runs Laravel Boost, which regenerates `CLAUDE.md`, `AGENTS.md` and `.claude/skills`. Revert with `git checkout -- CLAUDE.md AGENTS.md .claude`.
- `/config/lara-*` is git-ignored in the project, so site-wide migration settings live in `config/v8-migration.php`.
- `composer test` needs the front assets (Vite manifests) and a `tests/Unit` folder.
- The project autoloader is optimized (class map): after removing classes run `composer dump-autoload`, or `class_exists()` includes a missing file and throws.
- A bare essential install (10.7.15) has a front 500 (`getDefaultSeo()` on a missing home page: the essential seed has only an English home page while `nl` is the default) and a 500 on the Pages list (`ListPages::syncPagesWithMenu()` with a menu item whose page is missing). Both disappear once the v8 pages and menus are imported.

## Sites

### anpak.eu (first site, 2026-11)

- Target `anpak1077`, theme `anpak`; plan with all details and decisions: `anpak8246/.ai/plans/2026-11-14-migrate-v8-to-v10.md`.
- Core field values: cta `overlaycolor` `primary` → `brand` (live CTA #99); `ctas.hook` got the option `homeparallax1` (the only hook the anpak theme uses, on the home page); `homeparalax1`, `homeparalax2`, `homeparallax2` are only on unpublished or trashed CTAs and were not added. The theme needs widget template `service` (4 widgets).
- Content types: 15 site content types built (12 entities, 3 forms) plus the relation location → events; relation methods `Location::events()` and `Event::location()` added by the import (the form controllers were todos until v10.7.18).
- Custom v8 code: only the Applications form (2-step job application with CV upload, mails, `StoreApplicationRequest`, admin views) is real custom code, and it is switched off on the live site (menu item unpublished, 4 test submissions in Jan 2024): **not ported** (user decision); the form and its data come over as a standard v10 form. Classicforms (demo leftover, custom controller) and Invallerforms (no entity) dropped. Traits, Special controllers and routes are stock v8.
- Inventory: `sites/anpak-inventory.md`. 19 content types (4 core). With `V8Map`, 4 findings remain: `yesno` becomes a toggle (classicform), 5 docs whose file is only in `_archive`, a duplicate latitude/longitude definition. One page has a `lead`, which v10 pages do not have (decide in the content import). The v8 role `editor` has no v10 counterpart and no users.
- The fresh demo install failed on 10.7.13 (an orphan `cities` migration, migrations behind the schema); fixed in 10.7.14. The test config was fixed in 10.7.15.
