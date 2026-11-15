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

## v8 facts (v8.2.46)

- Media are on disk `localdisk` = `public/assets/media`, one folder per entity key, each with `_trash` and `_archive` subfolders (skip these) and an `.htaccess`.
- `lara_object_images.entity_type` holds full v8 class names (`Eve\Models\Blog`, `Lara\Common\Models\Page`); `filename` is relative to the entity folder.
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

## Sites

### anpak.eu (first site, 2026-11)

- Target `anpak1077`, theme `anpak`; plan with all details and decisions: `anpak8246/.ai/plans/2026-11-14-migrate-v8-to-v10.md`.
- The fresh demo install failed on 10.7.13 (an orphan `cities` migration, migrations behind the schema); fixed in 10.7.14. The test config was fixed in 10.7.15.
