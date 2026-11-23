# doormode.nl: go-live checklist

v8.2.46 (`doormode8246`, production db `etdrzkydbm`, theme `doormode`) → v10 (`doormode10723`, Lara CMS 10.7.23, theme `doormode`). General steps: runbook step 18 (with its Deploy lessons). Status 2026-11-23: **live on v10** since the switch on the evening of 2026-11-23 (staging app became production; v8 app is the fallback).

Live: https://www.doormode.nl (`doormode.nl` and `http://` redirect 301 to `https://www`), **behind Cloudflare** (proxy: e-mail addresses in pages are obfuscated, `robots.txt` is Cloudflare's managed file with AI-bot rules). Plan with every decision: `doormode8246/.ai/plans/2026-11-23-migrate-v8-to-v10.md`; inventory: `sites/doormode-inventory.md`; text pass: `doormode8246/.ai/plans/doormode-compare/`.

## Repositories

- **`appdezign/doormode-nl`, branch `v10`** (pushed 2026-11-23): the site. The project at 10.7.23, the migration tooling, the generated `laracms/app` (9 site content types: blog, team, event, location, gallery, video, doc, contactform, classicform), `laracms/themes/doormode`, `lang/vendor`, `v8-overrides.php` (1 override: English unpublished), `THEME_NAME = 'doormode'`, fonts `@googlefonts('doormode')` (Open Sans + Playfair Display, in `config/google-fonts.php`), and the built assets (`public/assets/admin`, `public/assets/themes/{base,doormode}`, `public/assets/filament/img`; runbook "Built assets in git"). Not in git: `storage/app/public` (media), `public/storage` (run `storage:link`; untracked since `6002308`). No `main`/`develop` yet: create them from `v10` at the go-live.
- **`appdezign/lara-pro-cms-project`, branch `v8-migration`**: the tooling and the runbook. Tooling changes go there first (worktree `__migrate/doormode10723-tooling`), then `git merge v8-migration` into `v10`.
- After go-live: make the site repo the only remote, `main`/`develop`, a version tag; remove the project tags and the tooling worktree once everything is pushed (runbook gotcha "After go-live, make the site repo the only remote").

## Open before staging

- [x] **Staging app and domain** (user, 2026-11-23): **https://v10.doormode.nl**, the v10 Cloudways app on the same server as the v8 app (it becomes production at the switch). Through the Cloudflare proxy; on 2026-11-23 the app still answered 403 (nginx, empty). Database name: from the user at the TablePlus import. PhpStorm SFTP project: `__lara10_cloudways/remote/doormode.nl_v10_cloudways` (server "cloudways", root `/public_html`).
- [x] **Keys** (user, 2026-11-23: use the v8 keys): Google Maps (`GOOGLE_MAPS_API_KEY`, `GOOGLE_MAPS_GEOCODING_API_KEY`; the contact page map) and reCAPTCHA (`GOOGLE_RECAPTCHA_SITE_KEY`, `GOOGLE_RECAPTCHA_SECRET_KEY`; v2 checkbox on the contact form) from the v8 app's `.env` (the local copy `doormode8246/.env` has the same) go into the v10 app's `.env` on the server; check that the reCAPTCHA keys allow the staging domain. Locally they stay empty (user, 2026-11-23). GA4: `CONSENT_GA4_ID=G-52D5VGD7LJ` (v8 setting `ga4_id`); staging then counts in the live property.
- [x] **Mail** (user, 2026-11-23): the form goes to **`info@doormode.nl`** = `company_email` (imported from v8; `FormController` sends to it and from it with `APP_ENV=production`). Production: AuthSMTP as the v8 app (`MAIL_MAILER=smtp`, `MAIL_HOST=mail.authsmtp.com`, `MAIL_PORT=25`, `MAIL_ENCRYPTION=null`, username/password from the v8 `.env`); `MAIL_FROM_ADDRESS=info@doormode.nl`, `MAIL_FROM_NAME="DOOR mode"` (proposed; for the other mails, e.g. password resets; v8 used the config default, a firmaq.nl address). DNS (checked 2026-11-23): SPF `v=spf1 include:authsmtp.com include:_spf.mx.cloudflare.net ~all`, MX Cloudflare Email Routing (forwarding), DMARC `p=none`. Staging: dev mailer, tested.
- [x] **`APP_NAME="DOOR mode"`** (user, 2026-11-23): page title suffix " — DOOR mode" and `og:site_name` (live titles have no suffix; kept, as seerden). On the server with the quotes (Deploy lessons).
- [x] **Cloudflare** (user, 2026-11-23): the user manages the doormode.nl DNS in Cloudflare; SSL/TLS encryption mode **Full** (Cloudflare accepts the origin certificate without validating it, so the Cloudways Let's Encrypt certificate works; issue it after the domains move, the HTTP challenge passes through the proxy). The origin IP stays the same (same server): no DNS change at the switch.

## Staging

**Manual server steps first** (they are in no repo; remind the user again before the first deploy, plan task 16):

- Cloudways PHP **8.4**, and enable **`tmpfile`, `highlight_file` and `fpassthru`** (Application Settings → PHP: remove them from `disable_functions`).
- Upload the Google Analytics service-account file by hand to **`storage/app/analytics/service-account-lara-dash.json`**; without it the admin dashboard errors on the first deploy.

Locally (in `doormode10723`, on `v10`):

```bash
php artisan lara:v8:verify -v                    # ok 27, no warnings, 1 site override in place
mysqldump -h127.0.0.1 -uroot --single-transaction --routines v10_doormode > ../doormode-deploy/doormode10723.sql
python3 -c 'import tarfile; t = tarfile.open("../doormode-deploy/doormode10723-media.tgz", "w:gz"); t.add("storage/app/public", arcname="public", filter=lambda i: None if i.name == "public/_fonts" or i.name.startswith("public/_fonts/") else i); t.close()'
# not macOS tar: NFD file names and ._* files (seerden)
```

The deploy files live in **`__migrate/doormode-deploy/`**: `doormode10723.sql` (the v10 database), `doormode10723-media.tgz` (`storage/app/public` without `_fonts`) and `backups/` (dumps before the final sync). The dump holds user password hashes: never in a repo or the home folder.

On the server, guided one step at a time (runbook step 18: one command or setting, wait for the result, check it from here):

1. Code: Cloudways "Deployment via Git", repo `appdezign/doormode-nl`, branch `v10`, Pull (it runs no composer, build or artisan). `php -v` (8.4), `composer install --no-dev -o`.
2. `.env` (keep `APP_KEY` and the server's DB settings): `APP_NAME="DOOR mode"`, `APP_ENV=staging`, `APP_DEBUG=false`, `APP_URL=https://v10.doormode.nl`, `LARA_CLIENT_THEME=doormode`, `MAIL_DEV_*`, the keys above, `CONSENT_GA4_ID=G-52D5VGD7LJ`.
3. Database: the user imports `doormode-deploy/doormode10723.sql` into the app's database with **TablePlus** (no `mysql` on the server); checked from here afterwards.
4. Media: the user uploads `doormode10723-media.tgz` with **SFTP in PhpStorm** to `public_html/storage/app/` (the application user cannot write to `~`; `storage/app` is not served), then `rm -rf storage/app/public && tar -xzf storage/app/doormode10723-media.tgz -C storage/app` and removes the archive; check: 34 imported files + the editor folder, no `._*`, names NFC.
5. `rm -f public/storage && php artisan storage:link` (check `ls -l public/storage`), `php artisan google-fonts:fetch` (after `APP_URL`), `php artisan migrate:status | grep -i pending` (expected: nothing).
6. Caches: `php artisan optimize:clear && php artisan responsecache:clear && php artisan lara:route:cache`.
7. Scheduler (Cloudways Cron Job Management): `* * * * * cd {app root} && php artisan schedule:run >> /dev/null 2>&1`.
8. Sitemap once: `php artisan schedule:test --name=google-sitemap` (not tinker); `ls -l public/sitemap.xml`.
9. Block indexing of staging (Cloudways password or `robots.txt` `Disallow: /`; a dashboard pull restores the repo `robots.txt`, so block again after each pull).

Deploy log (2026-11-23, guided):

- Code pulled (Deployment via Git, `v10`), `composer install` and `storage:link` done; `.env` from the user.
- The first database import was a wrong file (seerden data: `View [_partials.hero.hero_fixed] not found`, seerden's hero); the user re-imported `doormode10723.sql` into `zxsxvqjwdy`. The route cache still held the old menu (every menu URL 404 except Contact) until `optimize:clear && responsecache:clear && lara:route:cache`. Lesson: rebuild the caches after every database import.
- `.env` had `APP_ENV=testing` (log lines `testing.ERROR`): set to `staging`.
- Admin: opening a wallpaper or landing page gave a 500 (fixed: SQL imported, caches rebuilt, pages save; seerden's file imported too, its site 200) (`Attempt to read property "template_extra_fields" on null`, `HasContentSection.php:168`): the v8 page templates had no v10 page view. Fixed in the tooling (`fe862ba`: `import-entities` adds the views; locally 5 views, 13 page menu items to their template view, verify ok 27). For staging: `doormode-deploy/page-templates-v1.sql` (guarded inserts + the menu items), tested twice on a copy of the deploy dump (views and menu items as local, the edit forms 200); the user imports it with TablePlus into `zxsxvqjwdy`, then the caches. Seerden had the same 500 on its Instagram page: `seerden-deploy/page-template-instagramfeed.sql` (tested on a copy of `v10_seerden`).
- `robots.txt` blocked on the server (`Disallow: /`, after Cloudflare's managed rules); Cloudflare edges served the old copy for a while (purge the URL in Cloudflare if needed). Block it again after every pull until the switch.

Checks:

- [x] Server: PHP 8.4 with `tmpfile`, `highlight_file`, `fpassthru` (user; `php -v` 8.4.28); GA service-account file uploaded, admin dashboard works (user); scheduler cron `cd /home/1652362.cloudwaysapps.com/zxsxvqjwdy/public_html && php artisan schedule:run` (user). 2026-11-23.
- [x] Every URL of the text pass (25 URLs) identical to `doormode10723` (status, title, headings, text; the contact page differs only in Cloudflare's e-mail obfuscation); all 75 asset and image URLs of those pages and `/admin/login` 200; `fonts.css` and its 68 font files from `v10.doormode.nl` 200. 2026-11-23, after the cache rebuild.
- [x] Evenementen lists no events (stock v8 front scope); the contact page shows the two-column grid. The map: check in a browser (Maps key from v8).
- [x] Sitemap: generated with `schedule:test --name=google-sitemap` (user): `/sitemap.xml` 200, `text/xml`, 16 `v10.doormode.nl` URLs (2026-11-23). Unlinked pages left out (user; `f5d67c3`): Evenementen, Fotoalbum, Privacy, Offline; Contact stays. The 3 blog posts are missing (project-level generator bug, runbook seerden section).
- [x] Forms: the user submitted the contact form on staging; both mails (webmaster and confirmation) arrived on the dev mailer; the map loads (2026-11-23). Production mail and reCAPTCHA are checked right after the switch.
- [x] Admin: the user logged in, opened and saved pages (after the page-template fix, `page-templates-v1.sql`); afterwards the 25 front URLs are still identical to `doormode10723` (2026-11-23). Edits on staging are lost at the switch.

## Content to settle before the freeze

- [x] Any content changed in v10 before go-live: record it in `v8-overrides.php`, or the final sync overwrites it. None: edits on staging were overwritten by the final import, as planned (2026-11-23).
- [x] The two English rows (page 1071 "[EN] homepage", unpublished language): kept as imported; nothing links to them.

## Switch

**Approach (user rule): the staging app becomes production.** The v10 app gets the production domains in the Cloudways dashboard; the v8 app stays as the fallback (no domains, crons off, `MAIL_MAILER=log`). No DNS change (same server; Cloudflare keeps pointing to it). Guided step by step.

Before the day:

- [x] Production mail settings and sender decided (see Open before staging); active once `APP_ENV=production`.
- [x] reCAPTCHA keys: the v8 keys (user), live on `www.doormode.nl`, already in the v10 app's `.env`; the widget renders only with `APP_ENV=production`: check right after the switch.
- [x] `.env`: `SESSION_DOMAIN=null`; `SESSION_DRIVER=file` (checked on the v10 app 2026-11-23; clearing the sessions: `rm -f storage/framework/sessions/*`).
- [x] Cloudways backups of the v10 app (daily, plus a manual backup before the switch; user 2026-11-23).
- [x] Sitemap decision (user, 2026-11-23): unlinked pages left out, applied in `f5d67c3` on `v10` (pull before the switch; the sitemap is generated again at the switch).

The switch:

- [x] Content freeze on the v8 admin. 2026-11-23 ~16:45.
- [x] Final production dump from the v8 app with TablePlus into `doormode8246/.lara/dbdump/` (before its domains move); production media with SFTP into `doormode8246/.lara/media` (emptied first; `LARA_V8_MEDIA_PATH` points there). `doormode-v8-23112026-final.sql` (16:49, 61 tables): identical to the morning dump except the `google_analytics_sync` timestamp; media 189 files, identical to the morning copy (`.lara/media-morning`).
- [x] Backups first: `v10_doormode` and `v8_doormode` into `doormode-deploy/backups/`. `backups/{v10,v8}_doormode-before-final-sync.sql` (16:52).
- [x] Locally: reload `v8_doormode` from the fresh dump (runbook 17), `lara:v8:sync --dry-run`, `lara:v8:sync`, `lara:v8:verify -v`; a fresh `doormode10723.sql` and media archive (Python) in `doormode-deploy/`. Sync: everything `same` except pages, 16 "updated": with `--fresh` the content import writes the derived page columns from v8, and only `position` differed (v10's Pages list renumbers 1001… where v8 has 101…, same order) plus `updated_at` of 4 pages touched by the Pages list; `ishome`, `menuroute` and all content identical. Second dry run all `same`, verify ok 27, every page 200. The staging deploy files moved to `backups/*-staging.*`; new dump 16:54; media archive identical in content to the staging one.
- [x] Server (v10 app): the user imports the dump with TablePlus, uploads and unpacks the media; NFC/`._*` check; clear the sessions. Dump into `zxsxvqjwdy`; media not uploaded again: the archive was identical and the server held the same 117 files, none newer than the deploy; sessions cleared.
- [x] Server: `robots.txt` back to the repo version (a dashboard pull restores it). Dashboard Pull of `v10` (only tooling/docs changed since the staging pull). Afterwards the origin serves `User-agent: *` / `Allow: /` (equivalent to the repo's empty `Disallow:`).
- [x] Server `.env`: `APP_URL=https://www.doormode.nl`, `APP_ENV=production`, production `MAIL_*`/`MAIL_FROM_*`; `rm -rf storage/app/public/_fonts && php artisan google-fonts:fetch`; `php artisan optimize:clear && php artisan responsecache:clear && php artisan lara:route:cache`; the sitemap again. Fonts point to `https://www.doormode.nl`; sitemap 16 www URLs.
- [x] v8 app: crons off, `MAIL_MAILER=log`, domains removed (Cloudways temporary URL). Before each command check which app the shell is in (`grep -m1 '"version"' composer.json`). (v8 `composer.json` version field: `8.2.22`, the project; lara-cms 8.2.46 inside.)
- [x] v10 app: primary domain `www.doormode.nl`, alias `doormode.nl` (redirect to `www`, as live); remove the staging domain or redirect it; SSL for both (behind Cloudflare, see Open before staging). Staging domain removed (404). **Lesson:** the non-www → www redirect was a v8 app setting in Cloudways (not in v8's code or `.htaccess`) and did not move with the domain; Cloudways' own www redirect sends every path to the home page, so it is off and the redirect is a **Cloudflare Redirect Rule** (hostname `doormode.nl` → dynamic `concat("https://www.doormode.nl", http.request.uri.path)`, 301, query string kept). A first version had the hostnames reversed (www → root). Let's Encrypt installed for both domains.
- [x] Check: http → https and non-www → www (301), home, every menu item and the unpublished pages (Contact, Fotoalbum, Evenementen, Privacy, Offline), the 3 blog posts, the 404, every asset and image, the contact form with reCAPTCHA and the mail, the cookie consent, `robots.txt`, `sitemap.xml` (www URLs), the admin login. 2026-11-23: text pass production vs local (`doormode-compare/production-pass.md`): 24 pages identical, contact only Cloudflare's e-mail obfuscation and the reCAPTCHA hint (production); 78 assets and 38 font files 200; no staging or `http://` references; sitemap 16 www URLs; contact form, reCAPTCHA, map, both mails, cookie consent, admin and dashboard checked by the user.
- [ ] Search Console: submit the sitemap; watch 404s (v8 had no redirects; the past event detail URLs of live's sitemap no longer exist in v10 as list entries).
- [ ] Git: `main`/`develop` from `v10`, tag `v1.0.0`, Cloudways deploys `main`; the site repo becomes the only remote; project tags and the tooling worktree removed once everything is pushed.

## Fallback

- [ ] The v8 app stays on the server with its files and database, without domains, on its Cloudways temporary URL (crons and mail off), for a few weeks. Switching back: move the domains back and issue SSL again; content edited in v10 after the switch would have to be redone in v8.

## Known differences that stay

- Unknown URLs answer 404 with the "Oops" page (live: 200). On short pages (Privacy, Evenementen, the 404) the footer sits at the bottom of the window (v8: right under the content).
- `/en` is unpublished (live: a 500); v10 still answers `/en/` with the "[EN] homepage" page (core: an unpublished language stays routable); nothing links to it.
- Page titles end in " — DOOR mode".
- Contact form: required-field markers ("* = dit is een verplicht veld"); the button says "versturen" (live: the placeholder "_submit"); live's first box is 40 px taller (an empty paragraph v8 left in the column shortcode).
- The contact page's two columns are a rich editor grid (from v8's `[kolom_1van2]` shortcodes): editable in the admin.
- Privacy and Offline (not in the menu) are boxed and centred (live: no container, the image from the left edge).
- Social icons are SVG (v8: Font Awesome); Twitter/LinkedIn are not shown (`#` in v8, not shown there either).
- The cookie banner is v10's consent plugin.
