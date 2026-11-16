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
- **SEO**: v8 `lara_object_seo` and `lara_object_opengraph` become one filarank row per record (`model_type`/`model_id`): title (without a trailing site name such as "| Anpak": v10 appends " — {site name}" itself; user decision), description (max. 500), focus keyword, keywords as a JSON list, `locale` from the record's language, `og_image`. v10 has no og title or description (it uses the SEO ones); those are reported. Empty v8 rows are not imported.
- **Layout**: v8 keeps one row per setting (`layout_key`/`layout_value`); v10 one row per record with a column per section (header, hero, pagetitle, content, share, cta, footer). Values the active theme's `_layout.xml` does not offer are imported and reported; check them when the client theme is built.
- **Related objects and remote sync** are stored very differently in v10 and are reported, not converted (anpak has none). Implement the conversion when a site uses them; the inventory shows the counts.

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
- Page `ishome`/`position`/`menuroute` and menu item `routename` are derived by v10; they are imported for new rows only.
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
- **Translations**: the site's labels (v8 module `lara-eve`, per entity key) become `lara-app` labels of the v10 resource: `column` → `column` (with the column renames), `formfield` → `column` (a v8 `column` label wins), `entity_title` → `entity.title` + `navigation.label`, `entity_single`/`entity_plural` → `model.label_single`/`label_plural`, other tags as they are. v8 `lara-front` texts replace the v10 ones with the same key; v8 `lara-admin`/`lara-common` are the v8 admin's own texts and are skipped. v8 placeholders (`_key`) never overwrite. Afterwards the translations are exported to `lang/vendor`, as the admin does.
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
   php artisan optimize:clear && php artisan lara:route:cache
   ```
5. Request every menu URL and read the log for the first error of each page.

All of this is site code (theme, Vite config, `.env`, `config/google-fonts.php`): never commit it to the `v8-migration` branch.

### 15. Verify

```bash
php artisan lara:v8:verify            # problems only
php artisan lara:v8:verify -v         # every check
php artisan lara:v8:verify --only=counts --only=media
```

Changes nothing. Exits with an error code when a check finds an error.

- **counts:** rows and published rows per content type and language, v8 against v10. "Published" is what the front shows: for an entity without a status every non-trashed row (v10 ignores its `publish` column then; v8 had none, e.g. clients). Fewer rows than v8 is an error, more is a warning.
- **sync:** every import step in dry-run mode. Anything it would still create, update or remove is a warning: v10 was changed after the import (e.g. the site overrides), or v8 changed since.
- **media:** every Curator file, image link (media item and record exist), and file / video-file entry exists on its disk. Missing is an error.
- **links:** v8 media URLs left in the HTML, and internal links that do not reach a page (the `rewrite-urls` check, dry run).

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

Not included on purpose: `import-entities --update` (regenerates code: run it by hand when the sync reports an entity difference) and `import-users --prune` (would remove users created in v10).

### 18. Go-live

Per site, copy this list into `docs/migrate-v8/sites/{site}-go-live.md` and fill in the site's facts.

**Before (staging)**

1. Server: PHP 8.4 (`composer.json` requires `^8.4`), a database, the cron for the Laravel scheduler (`* * * * * php artisan schedule:run`: sitemap daily, media sync hourly, Google Analytics), Apache serving `public/` (a folder with `index.html` such as `public/flyer` is served directly; Herd's nginx does not, so check it here).
2. Site code: the project with the generated `laracms/app`, the client theme, `v8-overrides.php` and the site config (fonts, title separator). This is site code, not the migration branch.
3. `.env`: `APP_ENV=production` only on production (see 6), `APP_DEBUG=false`, `APP_URL`, `LARA_CLIENT_THEME={client}`, the mail settings, the site's **own** keys (Google Maps, reCAPTCHA, GA4: never the dev keys), the `LARA_V8_*` source connection (read-only user) and media path.
4. Build: `composer install --no-dev -o`, `npm ci`, the admin and client Vite builds, `php artisan storage:link` (replace a committed `public/storage` symlink).
5. Data: import from a fresh production dump + media rsync (steps 2 and 4–13, or the sync of step 17 on a copy of the local v10 database), then `lara:v8:verify`: no errors, every warning explained.
6. Forms: v10 sends form mail with the `dev` mailer (`MAIL_DEV_*`) unless `APP_ENV=production`, and then to `company_email` with the `smtp` mailer; reCAPTCHA is only checked in production. On staging, submit every published form and check the mail on the dev mailer; check reCAPTCHA right after the switch.
7. Compare with live once more (step 22 scripts: titles, headings, text; desktop and mobile).

**Switch**

8. Content freeze on the v8 admin (tell the editors; disable logins if possible).
9. Final sync (step 17) from a fresh production dump and media: `lara:v8:sync --dry-run`, then `lara:v8:sync`; `lara:v8:verify`.
10. `php artisan optimize:clear && php artisan lara:route:cache`.
11. DNS / vhost switch to the v10 server; SSL certificate for the domain (and `www`).
12. Right after: home, every menu item, a detail page per content type, the downloads (PDF), the 404, a form with reCAPTCHA and its mail (`company_email` and the confirmation to the sender), the cookie consent, `robots.txt`, `sitemap.xml` (`schedule:run` once, or wait a day), old URLs that Google has indexed.
13. Search Console: submit the sitemap; watch the coverage for 404s in the first weeks and add redirects for them in v10.

**Fallback**

14. Keep the v8 site and its database read-only for a few weeks (another vhost, no public DNS). Switching back is a DNS change; content edited in v10 after the switch would have to be redone in v8.

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
- `storage/app/public` is not git-ignored in the project repo (standard Laravel has a `.gitignore` there): the imported media show up as untracked files. Never add them to the `v8-migration` branch; a `storage/app/public/.gitignore` on `main` would prevent it.
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
- Content: all 19 content types imported (277 rows incl. 43 contact submissions); `--fresh` removed the essential page 5; page 1125 "Arbeidshygiëne" got its placeholder lead `blabla` in `body2` (template standard, warning). Lost, as decided: the form titles (copies of the sender name).
- Theme: started from the earlier v10 work in `anpak10` (repo `appdezign/anpak-eu`, built on 10.7.13, identical demo/base; working copy incl. the uncommitted consent changes), 166 files; Lato registered in `config/google-fonts.php`. Home, team, pages, news, vacancies, contact render; services, downloads and the English home give a 500 (tag lists / index_sort, task 21).
- URLs: 18 v8 media URLs rewritten in 18 rows (editor uploads in images/shares → /storage/images/shares, 5 files copied; one doc PDF → /storage/docs/file/…). 2 internal links reported: https://www.anpak.eu/nl/diensten/documenten (old URL, now diensten/downloads) and /flyer/?page=1 (public/flyer not yet copied, theme step).
- Media: 92 images (all featured; Curator items with dimensions) and 17 files in 16 lists (incl. the 5 docs whose file is only in `_archive`), 1 YouTube video; 109 files (169 MB incl. other site data on the public disk) copied; 1 image with prevent_cropping reported. News page images verified through Glide, a doc PDF through /storage.
- Settings: company/general from v8 (ANPAK, Heerenveen, GA4 id, phone); 16 system and 5 opengraph settings skipped; 18 blacklisted IPs; 31 header tags reported. Translations: 214 created, 48 updated (site labels such as services → "diensten"/"dienst"), 1272 admin texts skipped, 101 v8 front texts without a v10 key, 96 placeholders skipped.
- Menus: 3 menus, 29 menu items (34 v8 items minus 4 root items minus item 48 "About" (en, unpublished), whose page 1078 no longer exists); 24 route names rebuilt. 13 module page slugs renamed (team-index-module-nl → teams-index-module-nl …). Front: every page works; services (`_sortbytaxonomy`), downloads (`_sortbytaxonomy`) and jobs give a 500 until the anpak theme has their views.
- Objects: 2 taxonomies, 82 tags, 37 taggables, 4 pageables (widgets on page 1), 27 SEO rows (of 57 v8 rows; the rest were empty), layout of page 1 (all values in the demo theme). Reported: 9 tags with a publish_from date, 1 team member with an og title/description.
- Users: 9 users imported with their v8 ids; `admin` (id 2) → superadmin only; `helpdesk` (id 3) replaced the essential superadmin; webmasters Lampe and kapma; role `editor` skipped (no users); 5 orphan role assignments (users 6, 12–15). The webmaster lost menu, setting and translation permissions it did not have in v8 either.
- Custom v8 code: only the Applications form (2-step job application with CV upload, mails, `StoreApplicationRequest`, admin views) is real custom code, and it is switched off on the live site (menu item unpublished, 4 test submissions in Jan 2024): **not ported** (user decision); the form and its data come over as a standard v10 form. Classicforms (demo leftover, custom controller) and Invallerforms (no entity) dropped. Traits, Special controllers and routes are stock v8.
- Inventory: `sites/anpak-inventory.md`. 19 content types (4 core). With `V8Map`, 4 findings remain: `yesno` becomes a toggle (classicform), 5 docs whose file is only in `_archive`, a duplicate latitude/longitude definition. One page has a `lead`, which v10 pages do not have (decide in the content import). The v8 role `editor` has no v10 counterpart and no users.
- The fresh demo install failed on 10.7.13 (an orphan `cities` migration, migrations behind the schema); fixed in 10.7.14. The test config was fixed in 10.7.15.
