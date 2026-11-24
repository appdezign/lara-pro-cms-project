# anpak.eu: go-live checklist

v8.2.46 (`anpak8246`, production db `zhgkvpwmcx`, **since the in-place upgrade also the v10 database**) → v10 (`anpak1077`, Lara CMS 10.7.20). General steps: runbook step 18. Status 2026-11-16: staging (https://dev.anpak.eu) done and checked; next: the switch.

## Repositories

- **`appdezign/anpak-eu`, branch `v10`** (pushed 2026-11-16, `71b0ad2`): the site. The project at 10.7.20, the migration tooling, the generated `laracms/app` (19 content types), `laracms/themes/anpak`, `lang/vendor`, `v8-overrides.php` (5 overrides), `public/flyer`, Lato (`config/google-fonts.php`), title separator ` | ` (`config/filarank.php`), `THEME_NAME = 'anpak'`. Not in git: `storage/app/public` (media) and `public/storage` (run `storage:link`). `main` keeps the earlier attempt until go-live; `v10` and `main` have unrelated histories, so at go-live make `v10` the default branch (or reset `main` to it) instead of merging.
- **`appdezign/lara-pro-cms-project`, branch `v8-migration`**: the tooling and the runbook. Tooling changes go there first, then `git merge v8-migration` into `v10`.
- Locally `anpak1077` stays on `v10` (switching it to `v8-migration` removes the site code from the working tree); `../anpak1077-v8-migration` is a git worktree of `v8-migration`.

## Staging: dev.anpak.eu

Approach: the migration runs **locally** (where it is tested); the server gets the v10 database and the media. The server needs no v8 database and no `LARA_V8_*`. The final sync works the same way.

Locally (in `anpak1077`, on `v10`):

```bash
php artisan lara:v8:verify                       # no errors, no warnings
mysqldump -h127.0.0.1 -uroot --single-transaction --routines anpak1077 > ~/anpak1077.sql
tar -czf ~/anpak1077-media.tgz -C storage/app public
npx vite build --config vite.admin.config.js && npx vite build --config vite.front.base.config.js && npx vite build --config vite.front.client.config.js
tar -czf ~/anpak1077-assets.tgz -C public assets   # only needed if the server has no Node
```

On the server (SSH as the application user, in the app root `public_html`):

```bash
# 0. backup of the earlier attempt
mysqldump {db} > ~/backup-dev-anpak-$(date +%F).sql && tar -czf ~/backup-dev-anpak-files-$(date +%F).tgz .env storage/app/public

# 1. code
git fetch origin && git status                    # commit nothing; note local changes first
git checkout -B v10 origin/v10
php -v                                            # 8.4 (Cloudways: Application Settings → PHP version)
composer install --no-dev -o
npm ci && npx vite build --config vite.admin.config.js && npx vite build --config vite.front.base.config.js && npx vite build --config vite.front.client.config.js
#   no Node on the server: upload anpak1077-assets.tgz and run  tar -xzf ~/anpak1077-assets.tgz -C public

# 2. .env (keep APP_KEY and the DB settings of the server)
#    APP_NAME=Anpak  (the site name in every page title and og:site_name; the earlier attempt had Laracms10)
#    APP_ENV=staging  APP_DEBUG=false  APP_URL=https://dev.anpak.eu  LARA_CLIENT_THEME=anpak
#    MAIL_DEV_* (staging sends form mail with the dev mailer)
#    GOOGLE_MAPS_API_KEY, GOOGLE_MAPS_GEOCODING_API_KEY, GOOGLE_RECAPTCHA_SITE_KEY, GOOGLE_RECAPTCHA_SECRET_KEY: as the local .env
#    CONSENT_GA4_ID: as the local .env (new in v10: the cookie-consent plugin loads GA4 with it)

# 3. data (upload ~/anpak1077.sql and ~/anpak1077-media.tgz first)
mysql {db} < ~/anpak1077.sql
rm -rf storage/app/public && tar -xzf ~/anpak1077-media.tgz -C storage/app
php artisan storage:link
php artisan migrate:status | grep -i pending      # expected: nothing

# 4. caches, and the sessions of the earlier install (their user ids now belong to other users: /admin gave a 404 in a browser that was still logged in)
php artisan optimize:clear && php artisan lara:route:cache
rm -f storage/framework/sessions/*               # SESSION_DRIVER=file; with the database driver empty the sessions table

# 5. scheduler (Cloudways: Cron Job Management):  * * * * * cd {app root} && php artisan schedule:run >> /dev/null 2>&1

# 6. the sitemap once (the scheduler writes public/sitemap.xml daily; until then /sitemap.xml redirects to /nl/sitemap.xml, which shows the home page)
php artisan tinker --execute '(new \Lara\App\Sitemap\GoogleSitemapGenerator)(["nl"]);'
```

Notes from the first deploy (2026-11-16):

- Cloudways "Deployment via Git" (dashboard: choose the branch, Pull) works for the code; it keeps the git data outside `public_html`, so `git status` there says "not a git repository". Check the code by its files instead (e.g. `v8-overrides.php`, `app/Console/Commands/LaraV8/SyncCommand.php`, `composer.json` at 10.7.20). It runs no composer, build or artisan command: those stay SSH steps.
- The server has no Node: upload `anpak1077-assets.tgz` (built locally) and unpack it in `public/`; the media archive likewise.
- A browser still logged in to the earlier install got a 404 on `/admin` (its session points to a user id that is now a different v8 user); a private window worked. Clear the old sessions when deploying over an earlier install (step 4).

Checks:

- [x] Server: PHP 8.4, scheduler cron (user, 2026-11-16).
- [x] Code on `v10`, assets uploaded, `.env` (with `CONSENT_GA4_ID` and `APP_NAME=Anpak`: titles end in "| Anpak").
- [x] Data loaded, media in place, `storage:link`.
- [x] Compared with live and with anpak1077 (58 pages): status, headings, text and images identical to anpak1077; only the title suffix differs (`APP_NAME`). On the server: `/flyer/` and `/flyer/?page=1` 200 (Apache serves the folder), theme assets, Glide images (webp), editor images under `/storage`, PDF download, cookie consent with GA4, no English hreflang.
- [x] Sitemap generated (step 6): `/sitemap.xml` is XML with 22 URLs (live has none). Tinker prints a harmless notice that it cannot write `~/.config/psysh` on Cloudways. It uses `APP_URL`: generate it again after the switch (or wait for the daily task).
- [x] Forms: contact (`/nl/contact`, `/nl/contact-1`) and the classic form; mail works on dev.anpak.eu (user, 2026-11-16). In production the webmaster mail goes to `company_email` (an anpak.eu address) with the `smtp` mailer; on staging to `config('lara.admin_company_email')` with the dev mailer (`MAIL_DEV_*`). The first test failed with `View [email.contactforms.webmaster] not found`: the anpak mail views are now in `email/contactforms` and `email/classicforms` (v10 `dcaf4b1`); pull, `php artisan view:clear`, test again. The applications form is not used (menu item unpublished in v8).

## Content to settle before the freeze

- [x] News #517 linked to `https://www.anpak.eu/nl/diensten/documenten` (old URL): replaced by `/nl/diensten/downloads` with a `replace` override in `v8-overrides.php` (user decision, 2026-11-16). Verify: no warnings.
- [ ] Any other content changed in v10 before go-live: record it in `v8-overrides.php`, or the final sync overwrites it.

## Switch

- [ ] Content freeze on the v8 admin.
- [ ] Fresh production dump + media rsync → `lara:v8:sync --dry-run`, `lara:v8:sync`, `lara:v8:verify`.
- [ ] `php artisan optimize:clear && php artisan lara:route:cache`; set `APP_URL=https://www.anpak.eu` and `APP_ENV=production` first, then generate the sitemap again (step 6 of staging).
- [ ] DNS / vhost to v10; SSL for `anpak.eu` and `www.anpak.eu`.
- [ ] Check: home (slider, 4 services, testimonials, clients, contact band, news), the menu items, a news item, a document download, a team member, the ATEX services, contact form + mail + reCAPTCHA, cookie consent, `robots.txt`, `sitemap.xml`, `/en` (English is unpublished; live gives a 500 today).
- [ ] Search Console: sitemap; watch 404s. Known: v8 had no redirects; v8 service detail URLs (`/nl/diensten/arbeidshygiene/{slug}.html`) keep working in v10.

## Fallback

- [ ] Keep the v8 site (`anpak8246`) and its database read-only on another vhost for a few weeks.

## Known differences that stay

- Unknown URLs answer with the home page and status 200 (core; same as v8).
- The page-title photo is random per page load (as v8).
- The job detail page redirects to `/nl/404` (live: to the list); neither site links to it.

## After go-live

- [x] **Update to Lara CMS 10.7.24** (found on macgyverbouw, 2026-11-24; done with 2.0.5 on 10.7.25, 2026-11-24): before 10.7.24 the cached widgets (`sliderWidget` ×10, `menuSubCacheWidget` here) were cached for a day without the language; anpak's `/en` answers 200, so one English request can leave the Dutch pages without their sliders or with English menus. `composer update` of the appdezign packages (merge project `main` into the site branch), build, deploy, `php artisan cache:clear`.
- [x] **Form mail pages** (found on macgyverbouw, 2026-11-24; done 2026-11-24 into `zhgkvpwmcx`: pages 38, 1087, 1119 with the v10 slug): import `anpak-deploy/email-pages-v10-slug.sql` with TablePlus into the live v10 database (guarded: removes the empty "… Email Title" page v10 created, gives the v8 mail pages the slug v10 looks up), then `optimize:clear` and `responsecache:clear`.

## Updates

- **2.0.5, Lara CMS 10.7.25 (2026-11-24; from 10.7.22):** the form spinner (base 10.7.25; anpak's own contact-form copy got the same change), the language-aware widget cache (10.7.24), `RedirectToCanonicalHost` from the project with its test, the 10.7.24 sitemap generator (detail pages listed: 65 URLs, was 22), `.gitignore` `/.lara/`. Cloudways' www redirect (which dropped the path) is off; the middleware sends `anpak.eu/<path>?<query>` to the same path on www. Release `v2.0.5` (`main` `90278af`; git flow tagged `2.0.5`, renamed to `v2.0.5` before pushing, `gitflow.prefix.versiontag` now `v`); deployed (manual backup, Pull, `composer install`, 0 pending migrations, `email-pages-v10-slug.sql` into `zhgkvpwmcx` — anpak's v10 database is its former v8 database, upgraded in place, caches, sitemap). Checked: 73 URLs (menu pages and all detail pages) identical to the snapshot before the update; sitemap 65 www URLs; the spinner template on both contact pages; the www redirect keeps path and query. Form submission not tested on production (user: no test mails to the client). **Open (found, not changed):** the sitemap lists 4 folders (`/nl/anpak`, `/nl/diensten`, `/nl/diensten/links`, `/nl/vacatures`) that answer 302, as before. Plan: `__migrate/_notes/2026-11-24-update-migrated-sites-10-7-25.md`.
