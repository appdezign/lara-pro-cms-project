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
LARA_V8_SKIP_ENTITIES=          # optional, see step 3
```

Check: `php artisan test --compact tests/Feature/LaraV8` (the connection reads, cannot write, and the media path exists).

### 3. Inventory

```bash
php artisan lara:v8:inventory              # console tables
php artisan lara:v8:inventory --markdown   # save as docs/migrate-v8/sites/{site}-inventory.md
```

It lists the content types (rows per language, trashed, fields, relations), the system screens, custom field types, hooks and states, relations, object relations and media settings, front views, media rows, media files on disk and other data. It ends with the **findings**: v8 values without a v10 mapping, mappings that change behaviour, and inconsistent v8 data. Compare the findings with the previous sites: a new kind of finding probably needs a new mapping in `V8Map` or a fix in the import.

**Leftover content types.** A v8 site often still has demo content types nobody uses (seerden: city, product, sponsor, story: not in the v8 admin menu, `menu_position` NULL, no or only "Lorem ipsum" records). Check each candidate (rows, menu items, admin menu), then list the ones to leave out:

```dotenv
LARA_V8_SKIP_ENTITIES=city,product,sponsor,story
```

Every step then treats them as absent (`V8Source::entities()` leaves them out): `import-entities` does not build them (a relation to one is reported, not built), and the content, object, media and user imports skip their records, tags, taggables, SEO, layout, media and abilities, with one `info` line per step saying what was dropped. `verify` does not count them. The inventory lists them in "Skipped content types" with what each drops; a core content type (page, larawidget, cta, slider) can never be skipped. A skipped type that v10 already has (built before it was skipped) is reported by `import-entities`, not removed.

### 4. Essential base

```bash
php artisan lara:v8:prepare-target    # asks for confirmation; --force to skip
```

Removes the demo content types from `laracms/app` (`Models`, `Entities`, `Policies`, `Filament/Resources`, `Http/Controllers/Front/{Entity,Form}`, `Database/{Factories,Migrations,Seeders}`), runs `composer dump-autoload`, then `migrate:fresh` on the core migrations, `DatabaseCommonSeeder` and `optimize:clear`. Legacy, providers, routes, config, custom blocks and the other front controllers stay. It is also the way to start over: it removes generated site code as well.

After this the full v10 test suite (`composer test`) no longer applies to the site (it targets the demo entities); run `php artisan test --compact tests/Unit/LaraV8 tests/Feature/LaraV8`.

The migration tests in short: tests marked "skipped without a v8 source" run against the site's own `lara_v8` snapshot and only read. `FixtureImportTest` runs the real imports on two throwaway databases, `lara_v8_fixture` (loaded from `tests/Fixtures/LaraV8/v8_fixture.sql`: three content types `v8fxbook`, `v8fxevent`, `v8fxform` that cover geolocation, duplicates, conditions, a relation and a form) and `lara_v8_build_test` (an essential base); it removes the generated `v8fx…` code afterwards and is skipped when the MySQL user cannot create databases. Extend the fixture when a site shows a new case.

**Never commit** the changes in `laracms/app` and `lang/vendor` (removed demo code, generated site code) to the `v8-migration` branch; they belong to the site, not to the migration tooling.

### 5. Content types

```bash
php artisan lara:v8:import-entities --dry-run   # plan
php artisan lara:v8:import-entities             # build; -v shows every step
php artisan lara:v8:import-entities --update    # apply differences to existing ones
```

Builds every **site** content type through the admin builder (`HasLaraBuilder`): code, table, custom fields, front views and relations (`HasRelations`, which adds the `belongsTo` side and the foreign key). The v8 title is kept (`createEntity` would rename it after the model). Rerunnable: what exists and matches is reported as `same`; a second run must give only `same`, `info`, `warning` and `todo`. Core content types are not touched.

For the **core** content types the import changes only one thing: values that live v8 records (published, not trashed) use in an option field but the v10 options lack are added to those options (`CoreFieldValues`). Values only used by unpublished or trashed records are not added; their records keep the value. The inventory section "Core field values" shows both.

A site can also have added **its own custom field to a core content type** in v8 (seerden: `page.subtitle`, the home page `<h1>` and a heading on 7 pages). v10 lacks it, so the import builds it on the v10 core entity like a site field (`CoreCustomFields`, user decision 2026-11-18), when live records use it; the content import then fills it. Not used by live records: reported, not added, values not imported. Once built, the field has a v10 column and the import leaves it alone; v10's own core fields are never changed. The inventory lists such fields as findings (until they are built).

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

### 7. Users and roles

```bash
php artisan lara:v8:import-users --dry-run
php artisan lara:v8:import-users            # -v shows every step; --prune removes v10 users v8 does not have
```

Run it after `import-entities`: the permissions of the site content types need their policies.

- **Users** keep their v8 id and password hash (written directly, past the `hashed` cast; Laravel rehashes on the next login). v8 `username` → `name` (the login name), v8 `name` → `displayname`, `user_language` → `locale` (empty: the site locale). The v8 users replace the essential `superadmin` of the seed.
- **The v8 admin** (`is_admin`) gets **only** the v10 role `superadmin`, which passes every check (`Gate::before`).
- **Roles** are matched by name; a v8 role without users is skipped; a used v8 role that v10 lacks is created (panel access and level from v8).
- **Permissions**: the import first creates the permissions of every policy, as the Role screen does. For every subject both versions know, a role gets exactly its v8 abilities (`view` → `view` + `view_any`, `delete` → `delete` + `delete_any`); subjects only v10 knows keep the role's v10 permissions. v8 subjects without a v10 policy (headertag, language, redirect, seo, tag) are reported.
- Site policies are only registered for web requests (`LaraAppServiceProvider`); the import registers them itself. The same is why a console or test check of the admin as a non-superadmin can show 302/403 for site content types while the web is fine.

### 8. Content

```bash
php artisan lara:v8:import-content --dry-run
php artisan lara:v8:import-content --fresh   # first time: also removes v10 rows v8 does not have (the pages of the essential seed)
php artisan lara:v8:import-content           # later runs only write what differs
```

Run it after `import-entities` and `import-users`. Every v8 content type (pages, entities, blocks, forms) goes into the table of its v10 entity, with the v8 ids.

- **Columns**: core renames (widget `relentkey` → `resource_slug`, …), page `lead` → `body2` (user decision; the import warns when the page's template shows no extra body fields — switch it to e.g. the landing template), and for a content type with a geolocation field `address`/`pcode`/`city`/`country`/`latitude`/`longitude` → `geo_*`, `geolocation` (auto/manual) → `geo_location`. Columns without a v10 counterpart are reported with the number of values lost (v8 form `title`, a copy of the sender's name; page `show_in_app`; widget `customtags`).
- **Values**: core values translated (`primary` → `brand`, widget content type key → resource slug), `''` → `null` in custom fields, a v8 `NULL` in a v10 column that is `NOT NULL` with a default gets the default, module page slugs get the resource slug, record locks are dropped, a row of a user that no longer exists goes to the v8 admin.
- Foreign keys are checked after the import (the import runs with foreign key checks off, because the order of the tables is free); broken references are reported.
- Until the menus are imported, the admin Pages list gives a 500: the essential menu still points to pages that `--fresh` removed (`ListPages::syncPagesWithMenu()`).

### 9. Tags, SEO, layout and other objects

```bash
php artisan lara:v8:import-objects --dry-run
php artisan lara:v8:import-objects --fresh
```

Run it after `import-content` (links to records that do not exist in v10 are skipped).

- **Taxonomies, tags, taggables, pageables** keep their v8 ids. Tag `entity_key` → `resource_slug` (plural; larawidget → widgets); v8 class names (`Eve\Models\Blog`, `Lara\Common\Models\Larawidget`) → the v10 model classes. Tag `publish_from`/`publish_to` have no v10 column (reported).
- **Tag trees without the v8 root**: v8 keeps every tag tree (per resource, language and taxonomy) under a root tag titled `root` (`[EN] root` in another language; slugs `root-N`, `en-root-N`). v10 has no root: the admin creates tags at the top level. The import drops the roots, moves their children up one level and rebuilds `lft`/`rgt`/`depth` in v8 order, as for the menus. A root that taggables or menu items point to is kept as a top-level tag and reported. A theme partial that hid the root by depth (`$node->depth > 0`) must show depth 0 now. `lara:v8:verify` checks that no root is left and that every tree is a valid nested set (`--only=tags`).
- **SEO**: v8 `lara_object_seo` and `lara_object_opengraph` become one filarank row per record (`model_type`/`model_id`): title (without a trailing site name such as "| Anpak": v10 appends " — {site name}" itself; user decision), description (max. 500), focus keyword, keywords as a JSON list, `locale` from the record's language, `og_image`. v10 has no og title or description (it uses the SEO ones); those are reported. Empty v8 rows are not imported.
- **Layout**: v8 keeps one row per setting (`layout_key`/`layout_value`); v10 one row per record with a column per section (header, hero, pagetitle, content, share, cta, footer). Values the active theme's `_layout.xml` does not offer are imported and reported; check them when the client theme is built.
- **Related objects** (first used by seerden): v8 keeps one row per link (`entity_key`/`object_id` → `related_entity_key`/`related_object_id`), v10 one `lara_object_related` row per record with three JSON lists, as the admin's related section writes them: `related_page_objects` (`page_object_id`), `related_entity_objects` (`object_id`, `resource_slug`) and `related_entities` (`module_page_menu_id`: the v8 `menuitem` targets, i.e. module pages). Ids are kept. A link whose source or target does not exist in v10, or that points to a v8 menu root, is reported; a duplicate link is written once; every row has all three lists (the front loops over them). v10 writes a row with three empty lists whenever a record is saved in the admin: such a row is kept and not reported. The front only shows them for content types with `objrel_has_related` (mapped from v8 `has_related`).
- **Remote sync** settings are stored very differently in v10 and are reported, not converted (no site so far). Implement the conversion when a site uses them; the inventory shows the counts.

### 10. Menus and redirects

```bash
php artisan lara:v8:import-menus --dry-run
php artisan lara:v8:import-menus --fresh     # also rebuilds the route names and the route cache
```

Run it after `import-content` and `import-objects`.

- v8 builds every menu (per language) as a tree under one root: the home page in the main menu, a `root` item in the others. v10 has no single root: the home page (`is_home`) and the other top-level items are siblings at depth 0, with `position` 1001, 1002, … in tree order. The import flattens the trees: `root` items are dropped, the home root becomes the first top-level item, their children move up one level, and `lft`/`rgt`/`depth`/`position` are rebuilt.
- Menu items keep their v8 id; `entity_id` and `entity_view_id` (v8 ids) become the v10 ids; a page item gets the show view of the page's template. A page item whose page does not exist is skipped when nothing hangs below it (the v10 Pages list crashes on it), otherwise kept with a warning.
- Route names: the import writes the v8 names for new items, then runs `lara:menu:refresh-routenames` and `lara:route:cache` (`--no-routes` to skip). A rerun never overwrites a route name v10 rebuilt.
- Redirects: `lara_menu_redirects` → `seo_redirects` (from_path, to_path, status 301/302/307/308, active = publish).
- After this the front works for every page and every content type that has a view in the theme; content types without one give a 500 until the client theme has their views (see the theme steps).

### Reruns and v10 runtime changes

Using the v10 site changes rows the imports wrote; a rerun must not fight that:
- `updated_at` alone is never a difference (v10 touches it on every save, e.g. the Pages list saves all pages).
- Page `ishome`/`position`/`menuroute` and menu item `routename` are derived by v10; they are imported for new rows only. With `import-content --fresh` (first import, final sync) the page columns are written from v8 as well: a v8 page can have the id of an essential seed page (seerden: v8 page 19 "Team" kept the seed's `ishome = 1` and was a second home page until the admin Pages list re-derived it).
- Opening an edit form locks the record (`locked_at`/`locked_by`); a content rerun clears the locks, which is right during a migration.
- Opening a v10 index or form page creates its "module page" when it cannot find it. v8 module pages are named `{entity key}-{method}-module-{language}`, v10 ones `{resource slug}-…`: the content import renames them (`V8Map::modulePageSlug()`); `--fresh` removes module pages v10 created before that.

### 11. Settings, languages, blacklist and translations

```bash
php artisan lara:v8:import-settings --dry-run
php artisan lara:v8:import-settings          # also exports the translations to lang/vendor
```

- **Settings** (matched on `cgroup` + `key`): the site's own `company` and `general` values replace the essential seed (address, phone, GA4 id, coordinates); a key v8 holds twice takes the row with a value. `system` (versions, sync timestamps) belongs to v10 and `opengraph` is handled by filarank: both are skipped. v10-only settings are never removed.
- **Languages** and **blacklist** are copied by id.
- **Header tags** (v8 heading levels per template) have no v10 counterpart; reported.
- **Translations**: the site's labels (v8 module `lara-eve`, per entity key) become `lara-app` labels of the v10 resource: `column` → `column` (with the column renames), `formfield` → `column` (a v8 `column` label wins), `entity_title` → `entity.title` + `navigation.label`, `entity_single`/`entity_plural` → `model.label_single`/`label_plural`, other tags as they are. v8 `lara-front` texts replace the v10 ones with the same key, and are added for keys the v10 code uses (`lara-front::group.tag.key` anywhere under `laracms/`) but the v10 seed lacks (anpak: `default.form.error_recaptcha_not_checked`, the reCAPTCHA hint of the form views); the report names the added keys and the skipped v8-only ones. v8 `lara-admin`/`lara-common` are the v8 admin's own texts and are skipped. v8 placeholders (`_key`) never overwrite. Afterwards the translations are exported to `lang/vendor`, as the admin does.
- The database is the source of the translations: the admin export empties `lang/vendor/{module}/{locale}` and rewrites it from `lara_sys_translations`. Never edit `lang/vendor` directly (see Gotchas).
- The exported language files in `lang/vendor` are tracked in the project repo: like `laracms/app`, **never commit** them to the `v8-migration` branch.

### 12. Media

```bash
php artisan lara:v8:import-media --dry-run
php artisan lara:v8:import-media --fresh
```

Run it after `import-content`. Source: `LARA_V8_MEDIA_PATH` (for the final sync: an rsync of the production media folder).

- Files keep their **v8 file name** (user decision): images go to `{resource slug}/` on the entity's v10 image disk (`public`), files to `{resource slug}/file/`, video files to `{resource slug}/video/` — the folders the v10 admin uploads to. A file missing in the entity folder is taken from its `_archive`; `_trash` is never used. Unreferenced files on disk are not imported.
- **Images** become Curator items (`curator`: disk + path, dimensions, size, MIME type, alt/title/caption from v8) and links in `lara_object_images` (`mediable_type`/`mediable_id`, `type`, `order`): v8 `featured`/`ishero`/`isicon` → `featured`/`hero`/`icon`; an image without a flag, or one v8 did not hide from the gallery, also gets `gallery`; the order follows the v8 `position`. v8 `herosize` and `prevent_cropping` have no v10 counterpart (reported).
- **Files, videos, video files**: v8 keeps one row per file; v10 one row per record with a JSON list (`entity_files`: `doc_date`, `doc_filename`, `doc_original`; `entity_videos`: `video_title`, `youtubecode`; `entity_videofiles`: `vidfile_filename`, `vidfile_original`, `vidfile_date`). Dates are `Y-m-d H:i`. Video files in a cloud file service (`cfs_uid`) are reported.
- Rerunnable: a file is copied when it is missing on the v10 disk or its size differs. Curator items are never removed; `--fresh` removes links and lists v8 does not have.
- The images are served through Glide (`/glide/{path}?…`), the files from `/storage/{path}`.

### 13. URLs in the content

```bash
php artisan lara:v8:rewrite-urls --dry-run
php artisan lara:v8:rewrite-urls
```

Set `LARA_V8_SITE_HOSTS` (e.g. `anpak.eu`) so absolute URLs to the site itself count as internal.

- **Media URLs** in the HTML (`src`/`href` in every text column): `/assets/media/{entity key}/{file}` → `/storage/{the path the media import copied it to}`; other v8 media folders (editor uploads such as `images/shares`) are copied to the same path on the public disk → `/storage/images/shares/…` (file names URL-encoded). The **content import applies the same rewrite** while it imports, so a content rerun does not bring the v8 URLs back; `rewrite-urls` is then a check that also catches what the content import could not resolve. A URL whose file is missing is left as it is and reported.
- **Internal links** (`/{locale}/…`, and absolute URLs to the site hosts) are matched against the v10 routes (without the locale prefix, as v10 registers them per locale) and reported when they only reach the 404 route or a detail page whose slug does not exist; other root paths are checked as files in `public/`. Links are reported, not changed: v8 and v10 menu URLs are the same, so they mostly keep working.
- An HTTP status check does not work for this: v10 answers an unknown URL with the home page and status 200.

### 14. Client theme

A v10 client theme is a folder `laracms/themes/{client}` with `_assets` and `views` only (no provider or composer package), a child of `base`. The theme name is the plain client name (no year suffix).

1. **Check for earlier v10 work first.** If the client already has a v10 theme (anpak: `__lara10_cloudways/_local/anpak10`, repo `appdezign/anpak-eu`), start from that; take the working copy and check it against the current `demo`/`base` (`diff -rq`). Otherwise copy `laracms/themes/demo` to `laracms/themes/{client}`.
2. `vite.front.client.config.js`: `const THEME_NAME = '{client}';` — and `.env`: `LARA_CLIENT_THEME={client}`.
3. Fonts: the theme loads Google fonts by name (`@googlefonts('lato')`); register each in `config/google-fonts.php`.
4. Build and refresh:
   ```bash
   npx vite build --config vite.front.client.config.js
   php artisan optimize:clear && php artisan responsecache:clear && php artisan lara:route:cache
   ```
5. Request every menu URL and read the log for the first error of each page.

All of this is site code (theme, Vite config, `.env`, `config/google-fonts.php`): never commit it to the `v8-migration` branch.

### 15. Verify

```bash
php artisan lara:v8:verify            # problems only
php artisan lara:v8:verify -v         # every check
php artisan lara:v8:verify --only=counts --only=media   # counts, sync, media, links, tags, related
```

Changes nothing. Exits with an error code when a check finds an error.

- **counts:** rows and published rows per content type and language, v8 against v10. "Published" is what the front shows: for an entity without a status every non-trashed row (v10 ignores its `publish` column then; v8 had none, e.g. clients). Fewer rows than v8 is an error, more is a warning.
- **sync:** every import step in dry-run mode. Anything it would still create, update or remove is a warning: v10 was changed after the import (e.g. the site overrides), or v8 changed since.
- **media:** every Curator file, image link (media item and record exist), and file / video-file entry exists on its disk. Missing is an error.
- **links:** v8 media URLs left in the HTML, and internal links that do not reach a page (the `rewrite-urls` check, dry run).
- **tags:** no v8 root tag left, and every tag tree is a valid nested set.
- **related:** every related object points to a page, record or menu item that exists in v10 (menu items are imported after the objects, so module page targets are first checked here; missing is an error), and a content type with related objects has `objrel_has_related` on (otherwise the front does not show them; a warning).

Run it after the import and again after the final sync. Every warning should be one you can explain (a site override, a content issue to fix in v10).

### 16. Site overrides

Content you change in v10 before go-live is overwritten by the next import, unless it is a site override. Record every deliberate difference from the v8 data in `v8-overrides.php` in the project root (copy `v8-overrides.example.php`; another path: `LARA_V8_OVERRIDES`):

```php
return [
    ['table' => 'lara_blocks_widgets', 'where' => ['id' => 591], 'set' => ['hook' => 'home2'], 'reason' => 'ATEX second on the home'],
    ['table' => 'lara_sys_languages', 'where' => ['code' => 'en'], 'set' => ['publish' => 0], 'reason' => 'English not in use'],
];
```

`set` replaces column values; `replace` replaces text inside a column, e.g. one old link in a body: `'replace' => ['body' => ['/nl/old' => '/nl/new']]`.

Every import applies them while it builds the rows (`SiteOverrides`, in `SyncsRows` and the content import), so a rerun keeps them and `verify` counts them as in sync. `verify` also reports an override whose row no longer exists, or whose values are not in v10 yet. The file is site data: it belongs in the site repository, not on the migration branch.

### 17. Final sync

1. Announce a content freeze on the v8 site (no edits until go-live).
2. Fresh copy of production, into the same database and media path:
   ```bash
   mysql -h127.0.0.1 -uroot -e "DROP DATABASE v8_{site}; CREATE DATABASE v8_{site} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   mysql -h127.0.0.1 -uroot v8_{site} < {fresh-production-dump}.sql
   rsync -av --delete {user}@{v8-host}:{v8-root}/public/assets/media/ {LARA_V8_MEDIA_PATH}/
   ```
   The read-only grant survives the drop (`GRANT` is per database name).
3. Dry run, then the sync:
   ```bash
   php artisan lara:v8:sync --dry-run
   php artisan lara:v8:sync
   ```

`lara:v8:sync` shows the v8 database and its latest change (check that it is the fresh dump), the number of site overrides, then runs in order: `import-entities`, `import-users`, `import-content --fresh`, `import-objects --fresh`, `import-menus --fresh` (route names and route cache), `import-settings --fresh`, `import-media --fresh`, `rewrite-urls` and `verify`. It exits with an error code when a step does. In production it asks first (`--force` skips that).

A step that reports an error **stops the sync**: the later steps run with `--fresh` and would remove v10 rows (for example the media links of a content type whose records were not imported). The output names the step and the ones not run; fix the error and run the sync again. A dry run always runs every step.

Not included on purpose: `import-entities --update` (regenerates code: run it by hand when the sync reports an entity difference) and `import-users --prune` (would remove users created in v10).

### 18. Go-live

Per site, copy this list into `docs/migrate-v8/sites/{site}-go-live.md` and fill in the site's facts.

**Before (staging)**

1. Server: PHP 8.4 (`composer.json` requires `^8.4`); **enable `tmpfile`, `highlight_file` and `fpassthru`**, which Cloudways disables by default (Application Settings → PHP: remove them from `disable_functions`; an existing v8 application has them disabled too); a database, the cron for the Laravel scheduler (`* * * * * php artisan schedule:run`: sitemap daily, media sync hourly, Google Analytics), Apache serving `public/` (a folder with `index.html` such as `public/flyer` is served directly; Herd's nginx does not, so check it here).
2. Site code: the project with the generated `laracms/app`, the client theme, `v8-overrides.php` and the site config (fonts, title separator). This is site code, not the migration branch.
3. `.env`: `APP_NAME` (the site name in every page title), `APP_ENV=production` only on production (see 7), `APP_DEBUG=false`, `APP_URL`, `LARA_CLIENT_THEME={client}`, the mail settings, the site's Google Maps, reCAPTCHA and GA4 keys, and `CONSENT_GA4_ID` (GA4 for the cookie-consent plugin). `LARA_V8_*` only if the import runs on the server (see 6).
4. **Google Analytics credentials, by hand:** upload the service-account file to `storage/app/analytics/service-account-lara-dash.json` (`config/analytics.php`). It holds a private key, so it is in no repo (`storage/app/analytics/*` is git-ignored); without it the admin dashboard gives an error on the first deploy.
5. Build: the servers have no Node, so **the built assets are committed in the site repo** (see Built assets in git below); on the server only `composer install --no-dev -o` and `php artisan storage:link` (replace a committed `public/storage` symlink).
6. Data: run the import locally (steps 2 and 4–13, or the sync of step 17), `lara:v8:verify` (no errors, every warning explained), then move the v10 database (`mysqldump`) and `storage/app/public` to the server, without `storage/app/public/_fonts` (`tar -czf media.tgz --exclude public/_fonts -C storage/app public`). On the server run `php artisan google-fonts:fetch` after `APP_URL` is set: the fonts stylesheet holds absolute URLs of the site that fetched it. The server then needs no v8 database. (Running the import on the server works too, with the `LARA_V8_*` settings and a v8 database there.) Generate the sitemap once: `php artisan tinker --execute '(new \Lara\App\Sitemap\GoogleSitemapGenerator)(["{locale}"]);'`.
7. Forms: v10 sends form mail with the `dev` mailer (`MAIL_DEV_*`) unless `APP_ENV=production`, and then to `company_email` with the `smtp` mailer; reCAPTCHA is only checked in production. On staging, submit every published form and check the mail on the dev mailer; check reCAPTCHA right after the switch.
8. Compare with live once more (step 22 scripts: titles, headings, text; desktop and mobile).

**Switch**

9. Content freeze on the v8 admin (tell the editors; disable logins if possible).
10. Final sync (step 17) from a fresh production dump and media: `lara:v8:sync --dry-run`, then `lara:v8:sync`; `lara:v8:verify`.
11. Set the production `APP_URL`, then `rm -rf storage/app/public/_fonts && php artisan google-fonts:fetch` (the fonts stylesheet was written with the staging URL), then `php artisan optimize:clear && php artisan responsecache:clear && php artisan lara:route:cache` (or `composer cache`). When deploying over an earlier install, also clear its sessions (`storage/framework/sessions/*`, or the `sessions` table): the imported users keep their v8 ids, so an old session can point to another user (anpak: `/admin` gave a 404 in a browser that was still logged in).
12. DNS / vhost switch to the v10 server; SSL certificate for the domain (and `www`).
13. Right after: home, every menu item, a detail page per content type, the downloads (PDF), the 404, a form with reCAPTCHA and its mail (`company_email` and the confirmation to the sender), the cookie consent, `robots.txt`, `sitemap.xml` (`schedule:run` once, or wait a day), old URLs that Google has indexed.
14. Search Console: submit the sitemap; watch the coverage for 404s in the first weeks and add redirects for them in v10.

**Fallback**

15. Keep the v8 site and its database read-only for a few weeks (another vhost, no public DNS). Switching back is a DNS change; content edited in v10 after the switch would have to be redone in v8.

### Built assets in git

The site repo tracks `public/assets/admin` and `public/assets/themes/{base,client}`, so a pull always brings code and assets that belong together (anpak since 2026-11-17; before that, uploading `public/assets` by hand led to a 500 when a branch switch removed it).

- `.gitignore`: instead of `/public/assets/`, ignore only `/public/assets/filament/` (composer's `filament:upgrade` regenerates it on the server; tracked, it would dirty the server's working tree and block the next pull) and `/public/assets/themes/demo/` (unused).
- Build in the **release branch**, never in feature branches (the hashed names would conflict): `git flow release start {version}`, then
  ```bash
  npx vite build --config vite.admin.config.js && npx vite build --config vite.front.base.config.js && npx vite build --config vite.front.client.config.js
  git add public/assets && git commit -m "Build assets"
  ```
  then `git flow release finish`. The builds empty their output folder, so removed files show as deletions. A conflict in `public/assets` is resolved by building again, not by merging.
- Deploy: pull `main`, `composer install --no-dev -o`, `composer cache`.
- **The first pull that adds the tracked assets** meets the same files untracked on the server; git refuses to overwrite them. Move the uploaded copies aside first (`mv public/assets/admin public/assets/themes/{base,client} ~/assets-before-tracking/`), pull, check the site, then delete the copies.

## v8 → v10 mapping (`V8Map`)

`app/Console/Commands/LaraV8/Support/V8Map.php` holds every translation of v8 configuration; it covers all values the v8.2.46 core knows, so it should not need changes per site. Unit tests: `tests/Unit/LaraV8/V8MapTest.php` (they also fail when a v10 release renames a field type, hook or nav group).

- **Core content types** (page, larawidget, cta, slider) keep their v10 configuration (fields, views, code). Only their data is imported, with column renames: larawidget → `widgets` / `lara_blocks_widgets`, `relentkey` → `resource_slug`, `term` → `resource_term`, `imgreq` → `image_required`, `maxitems` → `max_items`, `isglobal` → `is_global`; `customtags` and page `show_in_app` are dropped.
- **Core custom fields**: a v8 custom field of a core content type that v10 lacks (not a v10 column, not renamed or dropped above) is added to the v10 core entity when live records use it (`CoreCustomFields`; seerden `page.subtitle`).
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
- Sites carry **demo leftover content types** from the v8 demo (seerden: city, product, sponsor, story; anpak had their language groups only): `menu_position` NULL, so not in the v8 admin menu, with no or only "Lorem ipsum" records. Leave them out with `LARA_V8_SKIP_ENTITIES` (step 3).
- `information_schema.tables.table_rows` is an estimate (InnoDB): it said 0 for seerden's story and city, which have 1 row each. Count with `COUNT(*)` or read the inventory.

## Gotchas

- **Never copy the laracms10 development folder** to start a site. Its `laracms/core` and `laracms/themes/{base,demo}` are symlinks into the git-controlled `laracms10pack`, and `composer install` would write through them. Always clone the project repo.
- Artisan cannot boot without an existing database (`HasSetup`), so create the database before `key:generate`.
- Plain `php artisan migrate` finds nothing: the migrations live in `laracms/core/src/common/Database/Migrations` and `laracms/app/Database/Migrations` and must be run with `--path` (see step 1).
- `.env.example` on `main` is the stock Laravel file (sqlite, no `LARA_*` keys). Base the `.env` on a working v10 site and replace name, URL, database, key and third-party keys (Google Maps, reCAPTCHA, GA4).
- `public/storage` on `main` is a committed symlink to a developer folder; `storage:link` replaces it locally. Do not commit that change.
- `storage/app/public` is not git-ignored in the project repo (standard Laravel has a `.gitignore` there): the imported media show up as untracked files. Never add them to the `v8-migration` branch; a `storage/app/public/.gitignore` on `main` would prevent it.
- `herd link` runs Laravel Boost, which regenerates `CLAUDE.md`, `AGENTS.md` and `.claude/skills`. Revert with `git checkout -- CLAUDE.md AGENTS.md .claude`.
- `/config/lara-*` is git-ignored in the project, so site-wide migration settings live in `config/v8-migration.php`.
- `composer test` needs the front assets (Vite manifests) and a `tests/Unit` folder.
- The project autoloader is optimized (class map): after removing classes run `composer dump-autoload`, or `class_exists()` includes a missing file and throws.
- A bare essential install (10.7.15) has a front 500 (`getDefaultSeo()` on a missing home page: the essential seed has only an English home page while `nl` is the default) and a 500 on the Pages list (`ListPages::syncPagesWithMenu()` with a menu item whose page is missing). Both disappear once the v8 pages and menus are imported.
- **Keep the site and the tooling in separate working trees.** The site repo's `v10` branch tracks the generated `laracms/app`, the client theme and `v8-overrides.php`; `v8-migration` does not. `git checkout v8-migration` in the site's working tree removes the site code (and puts the demo `laracms/app` back). Use a git worktree for `v8-migration`, commit tooling there, then merge it into the site branch.
- **Clear the response cache after every deploy.** Front pages are cached whole for anonymous visitors (`spatie/laravel-responsecache`, a day on anpak: `RESPONSE_CACHE_LIFETIME`; header `X-Cache-Status: HIT`). `optimize:clear` only empties it when it shares the cache store with the app, so run `php artisan responsecache:clear` (part of `composer cache`) too. Otherwise a fixed page keeps serving the old version (anpak: the untranslated reCAPTCHA hint after the fix was live). When checking a deploy with curl, look at `X-Cache-Status` or add a query string.
- Widgets cache their HTML (slider, entity widgets): after changing a widget view run `php artisan cache:clear`, not only `view:clear`.
- Tailwind 4 orders arbitrary breakpoints by value only within one unit: `min-[1200px]:` ends up before `md:` (rem), so `md:` wins. Write arbitrary breakpoints in rem (`min-[75rem]:`) like the theme's own.
- A client theme can redefine the type scale (`--text-4xl: 2rem` in anpak): use exact sizes (`text-[36px]`) when matching v8.
- Core looks for form mail views at `email.{resource slug}.webmaster|confirm`; v8-style folders (`email/contactform`) are not found and there is no fallback (base theme item).
- Outside `APP_ENV=production` form mail goes with the `dev` mailer to `config('lara.admin_company_email')`, and reCAPTCHA is not checked.
- The imported users keep their v8 ids: a session of an earlier install can point to another user (`/admin` gave a 404 in a browser still logged in). Clear the sessions when deploying over an earlier install.
- `spatie/laravel-google-fonts` writes `storage/app/public/_fonts/{hash}/fonts.css` with absolute font URLs of the site that fetched it (`APP_URL`). Copied to another server it still loads the fonts from there; with the local `*.test` URL Chrome asks "wants to access other apps and services on this device" (anpak, after go-live). Never copy `_fonts`; run `php artisan google-fonts:fetch` on each server after setting `APP_URL`.
- **Never edit `lang/vendor` directly.** Translations live in `lara_sys_translations`; the admin export (and `lara:v8:import-settings`) empties `lang/vendor/{module}/{locale}` and rewrites it from the database, so a hand edit is lost on the next export. Add or change the translation in the admin (or `Translation::updateOrCreate()`), export, and commit the exported files; on a live site the production database needs the same row. Keys missing from the v10 seed show as `_key` (`_error_recaptcha_not_checked`), and only where the view renders: the reCAPTCHA hint appears only with `APP_ENV=production`, so staging never shows it.
- `APP_NAME` is the site name in every page title and `og:site_name`; `CONSENT_GA4_ID` is needed by the cookie-consent plugin.
- **Deploying over an earlier install leaves untracked files behind.** A pull never removes files git does not track: old sessions (see above), uploaded assets, `config/lara-*.php` and generated code in `laracms/app` (models, entities, Filament resources and controllers of content types this site does not have). Filament loads every resource in `laracms/app/Filament/Resources`; a leftover whose content type is not in the database (anpak dev: the demo `Products`) makes the admin menu fail with `ModelNotFoundException`, so every logged-in `/admin` request answers 404, also in a private window and with `APP_DEBUG=true` (Laravel shows its plain 404 page for it). The server has no `git` (Cloudways keeps it outside `public_html`), so check the content-type folders against the site's content types, in the app folder:
  ```bash
  cd laracms/app
  ok='Blog|Doc|Service|Team'   # the site's content types, singular and plural prefixes (anpak: 15)
  for d in Filament/Resources Models Entities Policies Database/Factories Database/Migrations Database/Seeders Http/Controllers/Front/Entity Http/Controllers/Front/Form; do ls -1 "$d" 2>/dev/null | grep -Ev "^($ok)" | sed "s#^#$d/#"; done
  cd ../..
  ```
  Move what it lists to `storage/app/old-app/`, then `composer dump-autoload -o` (the class map still points to them), `php artisan optimize:clear` and `composer cache`. Old `config/lara-*.php` files override core's defaults: compare them with core and remove them when they hold nothing site-specific.
- **A 404 that debug mode does not explain:** send the request through the app as a user and print the exception. Laravel does not log 404s, and tinker may print nothing on Cloudways (it cannot write `~/.config/psysh`), so use a small script in the app folder, `storage/app/check-admin.php`:
  ```php
  <?php
  require __DIR__.'/../../vendor/autoload.php';
  $app = require __DIR__.'/../../bootstrap/app.php';
  $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
  $user = isset($argv[1]) ? Lara\Common\Models\User::where('email', $argv[1])->first() : Lara\Common\Models\User::role('superadmin')->orderBy('id')->first();
  $app['auth']->guard('web')->setUser($user);
  $response = $app->make(Illuminate\Contracts\Http\Kernel::class)->handle(Illuminate\Http\Request::create('/admin', 'GET'));
  echo 'user '.$user->id.' status '.$response->getStatusCode().PHP_EOL;
  if ($e = $response->exception ?? null) {
      echo get_class($e).': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine().PHP_EOL;
      foreach (array_slice($e->getTrace(), 0, 15) as $f) { echo '  '.($f['file'] ?? '?').':'.($f['line'] ?? '?').' '.($f['class'] ?? '').($f['type'] ?? '').$f['function'].PHP_EOL; }
  }
  ```
  `php storage/app/check-admin.php [email]` (default: the first superadmin); remove the script afterwards.
- **Switching the server to an unrelated branch deletes files the old branch tracked.** If the server's git holds an earlier attempt whose branch tracked `public/assets` (or `public/storage/*`, demo files in `storage/app/public`), moving the deploy to the v10 branch removes those paths, including the built assets uploaded over them: every Laravel page then answers 500 (no Vite manifest), static files still 200 (anpak: after resetting `main` to the v10 branch and switching Cloudways to `main`). Switch the server's branch first and upload `public/assets` after, or unpack the assets archive again after the switch; then `optimize:clear` and `lara:route:cache`. Before the switch, list what would go: `git ls-tree -r --name-only {old branch} | sort > old; git ls-tree -r --name-only {new branch} | sort > new; comm -23 old new | git check-ignore --no-index --stdin`.
- **Two manual server steps, every site** (they are in no repo): enable `tmpfile`, `highlight_file` and `fpassthru` in the Cloudways PHP settings next to PHP 8.4, and upload the Google Analytics service-account file to `storage/app/analytics/service-account-lara-dash.json` (otherwise the admin dashboard errors on the first deploy).
- Cloudways "Deployment via Git" keeps the git data outside `public_html` (`git status` there: "not a git repository") and runs no composer, build or artisan command; the servers have no Node, so the built assets are committed (see Built assets in git).
- An HTTP status check cannot find broken links: v10 answers unknown URLs with the home page and status 200. Use route matching (`rewrite-urls`, `verify`).
- **After go-live, make the site repo the only remote.** A site starts as a clone of `lara-pro-cms-project`, so `origin` is the project repo and the site repo is added next to it, and every project tag (`v10.*`) comes along. A branch that still tracks `origin`, or a `git push --tags`/`--follow-tags`, then sends the site's commits or release tags to the project repo (anpak: tags `v2.0.3` and `v2.0.4` landed in `lara-pro-cms-project` and had to be deleted there). Once the site repo has its own `main`/`develop`, in every clone of the site:
  ```bash
  git remote remove site                                  # if the site repo was added as `site`
  git remote set-url origin https://github.com/appdezign/{site-repo}.git
  git fetch origin --prune
  git branch -u origin/develop develop && git branch -u origin/main main
  git config gitflow.origin origin                        # git flow pushes to this remote
  git tag -l 'v10.*' | xargs git tag -d                   # project tags; release versions come from composer.json
  ```
  A `v8-migration` branch in the clone then tracks a branch the site repo does not have: unset its upstream (`git branch --unset-upstream v8-migration`) and commit tooling fixes from a clone of the project repo. A worktree record can outlive its folder's link: if `git branch -d` says a branch is checked out in a folder that belongs to another clone (its `.git` file points elsewhere), remove only the stale record (`.git/worktrees/{name}`), then `git worktree prune`.

## Sites

### anpak.eu (first site, 2026-11)

v8.2.46 (`anpak8246`, Bootstrap theme `anpak2024`) → Lara CMS 10.7.20 (`anpak1077`, theme `anpak`). Site repository: `appdezign/anpak-eu`, branch `v10`; staging https://dev.anpak.eu. Plan with every decision and detail: `anpak8246/.ai/plans/2026-11-14-migrate-v8-to-v10.md`; go-live list: `sites/anpak-go-live.md`; inventory: `sites/anpak-inventory.md`.

**Time.** Elapsed time between the commits of each step, including waiting for decisions and core releases; the tooling was written during this first site, so the next sites should be much faster.

| Steps | What | Time |
|---|---|---|
| 1–3 | v10 target, v8 snapshot, inventory | ½ day (two broken core releases: 10.7.13 fresh install, 10.7.14 test config) |
| 4–5 | essential base, content types (`prepare-target`, `import-entities`) | ½ day (form regression in 10.7.17, fixed in 10.7.18) |
| 6–9 | custom code, users, content, objects | 45 min |
| 10–13 | menus, settings, media, URLs | 1 h |
| 14 | client theme: from the earlier v10 attempt, gaps, tag lists, page-by-page comparison | 1½ h (core fix 10.7.19, language URLs 10.7.20) |
| 15–17 | verify, site overrides, sync | 30 min |
| 18 | staging on dev.anpak.eu | 1½ h with the user deploying (session, `APP_NAME`, mail views) |

For a next site with the tooling in place, plan roughly: 1–3 h for the import (steps 1–13, mostly reading the reports and deciding), and most of the time for the theme (step 14), depending on how far the v8 theme differs from the base.

**Results.**

- Content: 19 content types (4 core, 12 entities, 3 forms), 277 rows with their v8 ids; 9 users (`admin` → superadmin only, `helpdesk` replaced the essential superadmin, role `editor` skipped: no users); 2 taxonomies, 82 tags, 37 taggables, 4 pageables, 27 SEO rows; 3 menus, 29 items, 13 module page slugs renamed; settings, 18 blacklisted IPs, 214 + 48 translations; 92 images, 17 files, 1 video; 18 media URLs rewritten in the HTML (editor uploads in `images/shares`, one doc PDF).
- Compared with the live site: 58 pages (every menu item, every Dutch detail page, `/en`, an unknown URL) on titles, headings, text and images, desktop and mobile. Content pages, news, links and vacancies: 99–100 % text match.
- `lara:v8:verify`: ok 36, no warnings. Tests: 120.

**Mappings and options added for anpak** (now part of `V8Map` / the tooling):

- CTA overlay `primary` → `brand`; slider overlay `primary` → `brand1`; `ctas.hook` option `homeparallax1` (only values used by live records are added).
- Page `lead` → `body2` (1 page; the template must show extra body fields).
- Widget `relentkey` → resource slug; widget template `service`.
- Module page slugs `{entity key}-…-module-{lang}` → `{resource slug}-…`.
- SEO titles: a trailing site name is stripped (v10 adds it).
- Media: v8 file names kept; files only in `_archive` found.
- Status: v8 clients have no `publish` column; v10 stores 0 and ignores it (verify counts every row as published for entities without a status).

**Decisions** (see the plan for the reasoning):

- Not ported: the Applications form (2-step job application, switched off on live); its data comes over as a standard form. Dropped: Classicforms' custom controller, Invallerforms (no entity), the form titles (copies of the sender name).
- English unpublished (live `/en` is a 500 and nothing links to it).
- Layout always matches live, also where the earlier v10 theme differed.
- Site overrides (`v8-overrides.php`, 5): the four home services in a 2×2 grid in live order (widgets #591, #2, #3 → `home2`–`home4`), English unpublished, the old documents link in news #517.
- Base/core fixes found here are listed in the plan; the core ones are in 10.7.14–10.7.20, the base theme items (tag-list partial, `brand` overlays, services `index_sort`, contact demo text, mail view folders) follow after the migration.

**Theme work** (`laracms/themes/anpak`, beyond the earlier v10 attempt):

- Home: parallax CTA (`_widgets/cta/paralax`), slider sizes and heights as v8 (`hero-hN` = `Nvh`; caption 24/36/48/60 px), 3 news items with links, services in live order.
- Lists sorted by tag (services, downloads): v8's tag-list partial (no root, no empty tags, uses core's `childobjectcount`), services `index_sort` and the alternating service layout.
- Detail pages: documents (description and download as v8), team (full name), contact (v8 layout, no demo text).
- Mail views in `email/contactforms` and `email/classicforms`; `brand` overlay classes; menu right-aligned; title separator ` | `; Lato.

**Gotchas met on anpak** (the general ones are in Gotchas above):

- The earlier v10 theme was built on 10.7.13; its demo/base were identical to 10.7.18, so it could be taken over as it was.
- `/flyer/` (a static folder in `public/`) is redirected to `/nl/flyer` by Herd's nginx but served by Apache on Cloudways.
- The live site has no sitemap and answers unknown URLs with the home page (status 200); v10 does the same for unknown URLs.
