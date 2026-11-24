# inventiverepair.nl: go-live checklist

v8.2.46 (`invent8246`, production db `qbfnpgvjve`, theme `inventive`) → v10 (`invent10725`, Lara CMS 10.7.25, theme `inventive`). General steps: runbook step 18 (with its Deploy lessons). Status 2026-11-24: site code on `appdezign/inventiverepair-nl` branch `v10`; staging next.

Live: https://www.inventiverepair.nl on the Cloudways server at `95.179.176.155` (the same server as macgyverbouw and doormode), nginx, **not behind Cloudflare**. DNS at webhost.company (`ns1–3.webhost.company`); `www` (CNAME to the root) and the root both `95.179.176.155`; **`v10.inventiverepair.nl` already resolves** to the same address (2026-11-24). `http://www` → `https://www` 301 with the path; `inventiverepair.nl/*` (http and https) → `https://www.inventiverepair.nl/` 301 **without the path** (the query is kept: Cloudways' own www redirect, as macgyverbouw). `robots.txt` `Allow: /`; **a sitemap** (`/sitemap.xml` 200, 72 URLs). Plan with every decision: `invent8246/.ai/plans/2026-11-24-migrate-v8-to-v10-inventiverepair.md`; inventory: `sites/invent-inventory.md`; text pass: `invent8246/.ai/plans/inventive-compare/`.

## Repositories

- **`appdezign/inventiverepair-nl`, branch `v10`** (pushed 2026-11-24): the site. The project at 10.7.25 (with `RedirectToCanonicalHost` in `bootstrap/app.php`), the migration tooling, the generated `laracms/app` (7 site content types: blog, event, portfolio, doc, link, contactform, classicform; 9 empty demo types skipped), `laracms/themes/inventive`, `lang/vendor`, `v8-overrides.php` (6 overrides: the submit text, three old `.html` links, the company film over https, English unpublished), `THEME_NAME = 'inventive'`, no Google font (Verdana), and the built assets (`public/assets/admin`, `public/assets/themes/{base,inventive}`, `public/assets/filament/img`; runbook "Built assets in git"). Not in git: `storage/app/public` (media), `public/storage` (run `storage:link`; untracked since `0bb9b14`), `public/sitemap.xml` (generated). No `main`/`develop` yet: create them from `v10` at the go-live.
- **`appdezign/lara-pro-cms-project`, branch `v8-migration`**: the tooling and the runbook. Tooling changes go there first (worktree `__migrate/invent10725-tooling`), then merged into `v10`.
- After go-live: make the site repo the only remote, `main`/`develop`, a version tag; remove the project tags and the tooling worktree once everything is pushed (runbook gotcha "After go-live, make the site repo the only remote").

## Open before staging

- [x] **Staging app and domain** (user, 2026-11-24: **https://v10.inventiverepair.nl**; the DNS record exists). A v10 Cloudways app on the same server (it becomes production at the switch). Database **`yrjbgurjmu`** (user, 2026-11-24). PhpStorm SFTP project: `__lara10_cloudways/remote/inventiverepair.nl_v10_cloudways` (server "cloudways", host `95.179.176.155`; mapping to check).
- [x] **Keys** (user, 2026-11-24: the v8 keys on the server): Google Maps (`GOOGLE_MAPS_API_KEY`, `GOOGLE_MAPS_GEOCODING_API_KEY`; the map on "Plattegrond") and reCAPTCHA (`GOOGLE_RECAPTCHA_SITE_KEY`, `GOOGLE_RECAPTCHA_SECRET_KEY`; v2 checkbox on the contact form) from the v8 app's `.env` (`__cloudways/inventiverepair.nl_cloudways/.env` has them) go into the v10 app's `.env` on the server; check that the reCAPTCHA keys allow the staging domain. Locally they stay empty. GA4: `CONSENT_GA4_ID=G-CW6MX6TG7K` (v8 setting `ga4_id`); staging then counts in the live property.
- [x] **Mail** (user, 2026-11-24, as proposed): the contact form goes to **`info@inventiverepair.nl`** = `company_email` (imported from v8; with `APP_ENV=production` `FormController` sends to it and from it). Production: AuthSMTP as the v8 app (`MAIL_MAILER=smtp`, `MAIL_HOST=mail.authsmtp.com`, `MAIL_PORT=25`, `MAIL_ENCRYPTION=null`, username/password from the v8 `.env`); `MAIL_FROM_ADDRESS=info@inventiverepair.nl`, `MAIL_FROM_NAME="Inventive Repair"` for the other mails (v8 has no `MAIL_FROM_*`: the config default `beheer@firmaq.nl`). DNS (checked 2026-11-24): SPF `v=spf1 a mx include:_spf.google.com include:authsmtp.com ~all`, MX Google Workspace, DMARC `p=none`. Staging: dev mailer.
- [x] **`APP_NAME="Inventive Repair"`** (user, 2026-11-24): page title suffix " — Inventive Repair" and `og:site_name` (live titles have no suffix; kept, as the earlier sites). On the server with the quotes (Deploy lessons).
- [x] **www redirect**: `RedirectToCanonicalHost` (project, 10.7.25) answers `inventiverepair.nl/<path>?<query>` with a 301 to `APP_URL` + path + query, only with `APP_ENV=production` (macgyverbouw decision; live drops the path). Nothing to decide; check after the switch.
- [x] **Sitemap** (user, 2026-11-24: as is): v10 generates the same 70 Dutch URLs as live's sitemap (live 72: plus `/en/` and `/en/about-1`, English is unpublished in v10). Like live it lists the unpublished pages (e.g. Privacybeleid, Bedrijfsfilm, Meer info), the past events and the six partner detail pages `/nl/aangesloten-bij/aangesloten-bij-1/{bovag,…}` (500 on live, 200 in v10; nothing links to them, the list links to the partners' own sites). Proposed: keep it as live (no SEO change); a site rule like macgyverbouw's is possible later.
- [x] **Cookie banner privacy link** (user, 2026-11-24; `e0cc0ad` on `v10`): v8's banner linked to `/nl/privacy`, which does not exist (live answers with the home page; v10 404). The privacy page is `/nl/privacybeleid` (unpublished, but served and in the sitemap). `config/consent.php` `ui.policy_url` = `'/nl/privacybeleid'`.

## Staging

**Manual server steps first** (they are in no repo; remind the user again before the first deploy, plan task 16):

- Cloudways PHP **8.4**, and enable **`tmpfile`, `highlight_file` and `fpassthru`** (Application Settings → PHP: remove them from `disable_functions`).
- Upload the Google Analytics service-account file by hand to **`storage/app/analytics/service-account-lara-dash.json`**; without it the admin dashboard errors on the first deploy.

Locally (in `invent10725`, on `v10`):

```bash
php artisan lara:v8:verify -v                    # ok 31, no warnings, 6 site overrides in place
mysqldump -h127.0.0.1 -uroot --single-transaction --routines v10_invent > ../invent-deploy/invent10725.sql
python3 -c 'import tarfile; t = tarfile.open("../invent-deploy/invent10725-media.tgz", "w:gz"); t.add("storage/app/public", arcname="public", filter=lambda i: None if i.name == "public/_fonts" or i.name.startswith("public/_fonts/") else i); t.close()'
# not macOS tar: NFD file names and ._* files (seerden)
```

The deploy files live in **`__migrate/invent-deploy/`**: `invent10725.sql` (the v10 database), `invent10725-media.tgz` (`storage/app/public` without `_fonts`) and `backups/` (dumps before the final sync). The dump holds user password hashes: never in a repo or the home folder.

On the server, guided one step at a time (runbook step 18: one command or setting, wait for the result, check it from here):

1. Code: Cloudways "Deployment via Git", repo `appdezign/inventiverepair-nl`, branch `v10`, Pull (it runs no composer, build or artisan). `php -v` (8.4), `composer install --no-dev -o`.
2. `.env` (keep the server's DB settings; `key:generate`): `APP_NAME="Inventive Repair"`, `APP_ENV=staging`, `APP_DEBUG=false`, `APP_URL=https://v10.inventiverepair.nl`, `LARA_CLIENT_THEME=inventive`, `MAIL_DEV_*`, the keys above, `CONSENT_GA4_ID=G-CW6MX6TG7K`; no `LARA_V8_*`. Template: `invent-deploy/staging.env`.
3. Database: the user imports `invent-deploy/invent10725.sql` into the app's database with **TablePlus** (name the database and the file at every step; check a page from here right after).
4. Media: the user uploads `invent10725-media.tgz` with **SFTP in PhpStorm** to `public_html/storage/app/`, then `rm -rf storage/app/public && tar -xzf storage/app/invent10725-media.tgz -C storage/app` and removes the archive; check the file count against the archive, no `._*`, names NFC.
5. `rm -f public/storage && php artisan storage:link` (check `ls -l public/storage`), `php artisan google-fonts:fetch` (after `APP_URL`; the theme loads no Google font, the admin may), `php artisan migrate:status | grep -i pending` (expected: nothing).
6. Caches: `php artisan optimize:clear && php artisan responsecache:clear && php artisan lara:route:cache` (after every database import).
7. Scheduler (Cloudways Cron Job Management): `* * * * * cd {app root} && php artisan schedule:run >> /dev/null 2>&1`.
8. Sitemap once: `php artisan schedule:test --name=google-sitemap`; `ls -l public/sitemap.xml`.
9. Block indexing of staging (`robots.txt` `Disallow: /`; a dashboard pull restores the repo `robots.txt`, so block again after each pull).
10. GA service-account file (manual step above); the admin dashboard.

Deploy files (2026-11-24 18:28, verify ok 31, 6 overrides, the local test 404 hits removed): `invent10725.sql` (487 KB, 53 tables), `invent10725-media.tgz` (43 MB, Python tarfile, 129 files: the 16 imported media, the 29 editor images and the demo images the project repo tracks; 0 non-NFC, 0 `._*`, no `_fonts`), `staging.env`.

Deploy log (guided):

- Step 1 (user, 2026-11-24): PHP 8.4.28 with `tmpfile`, `highlight_file`, `fpassthru`; Deployment via Git, `v10` pulled; `composer install --no-dev -o`. Static files 200.
- Steps 2–4 (user, 2026-11-24): `.env` from `invent-deploy/staging.env` (`key:generate`), `invent10725.sql` into `yrjbgurjmu` with TablePlus, media archive unpacked, `storage:link`. Checked from here: text pass staging vs local, 62 URLs identical, status codes too (`inventive-compare/staging-pass.md`); Glide images 200 (webp); `/admin/login` 200; sitemap not yet (302).
- Step 5 (user, 2026-11-24): `google-fonts:fetch`; `migrate:status`: 0 pending.
- Step 6 (user, 2026-11-24): `optimize:clear`, `responsecache:clear`, `lara:route:cache`; text pass again: 62 URLs identical.
- Step 7 (user, 2026-11-24): scheduler cron (Cloudways Cron Job Management, `schedule:run` every minute).
- Step 8 (user, 2026-11-24): sitemap (`schedule:test --name=google-sitemap`). First run: all 70 URLs on `https://v10.inventive.nl`: a typo in the server's `APP_URL` (web requests use their own host, so the pages were fine; console output — the sitemap, the fonts CSS — uses `APP_URL`). Fixed `APP_URL=https://v10.inventiverepair.nl`, `_fonts` removed and fetched again, caches, sitemap again. Checked: 70 `v10.inventiverepair.nl` URLs, the same list as local; 63 answer 200, the 7 folders 302 to their first page (as local; live lists them too); `fonts.css` with 28 fonts from the staging host, 200; no `v10.inventive.nl` left.
- Step 9 (user, 2026-11-24): `robots.txt` `Disallow: /` (checked from here; block again after every pull until the switch).
- Step 10 (user, 2026-11-24): GA service-account file uploaded to `storage/app/analytics/service-account-lara-dash.json`; the admin dashboard works.
- Step 11 (user, 2026-11-24): contact form sent, both mails on the dev mailer; the map, the company film and the random partner box work.

Checks:

- [x] Server: PHP 8.4 with `tmpfile`, `highlight_file`, `fpassthru`; GA service-account file, admin dashboard; scheduler cron (user, 2026-11-24).
- [ ] Every URL of the text pass (`inventive-compare/urls.txt`, 62 URLs) identical to `invent10725`; assets and images of home, a sub page, news, partners, a document and the contact form OK; Glide images 200; `/admin/login` 200; no local or `http://` references.
- [x] The left sub menu follows the active main item; one random partner shows in the sidebar; the company film plays (user, 2026-11-24).
- [x] Contact form (user, 2026-11-24: sent, both mails arrived, map OK): both mails (webmaster and confirmation) on the dev mailer; no "… Email Title" page appears (mail pages carry the v10 slug). The map on "Plattegrond" with the Maps key.
- [ ] Admin: a page, a news item and a partner open and save; the front URLs still identical afterwards. Edits on staging are lost at the switch.

## Content to settle before the freeze

- [ ] Any content changed in v10 before go-live: record it in `v8-overrides.php`, or the final sync overwrites it.
- [x] The English rows (22 pages, CTAs, sliders; language unpublished): kept as imported; nothing links to them.

## Switch

**Approach (user rule): the staging app becomes production.** The v10 app gets the production domains in the Cloudways dashboard; the v8 app stays as the fallback (no domains, crons off, `MAIL_MAILER=log`). No DNS change (same server). Guided step by step.

Before the day:

- [ ] Production mail settings and sender decided (see Open before staging); active once `APP_ENV=production`.
- [ ] reCAPTCHA keys: the v8 keys, live on `www.inventiverepair.nl`, in the v10 app's `.env`; the widget renders only with `APP_ENV=production`: check right after the switch.
- [ ] `.env`: `SESSION_DOMAIN=null`; `SESSION_DRIVER=file` (clearing the sessions: `rm -f storage/framework/sessions/*`).
- [ ] Cloudways backups of the v10 app (daily, plus a manual backup before the switch).

The switch:

- [ ] Content freeze on the v8 admin.
- [ ] Final production dump from the v8 app with TablePlus into `invent8246/.lara/dbdump/` (before its domains move); production media with SFTP into `invent8246/.lara/media` (emptied first; `LARA_V8_MEDIA_PATH` points there).
- [ ] Backups first: `v10_invent` and `v8_invent` into `invent-deploy/backups/` (`*-before-final-sync.sql`).
- [ ] Locally: reload `v8_invent` from the fresh dump (runbook 17; the grant stays), `lara:v8:sync --dry-run`, `lara:v8:sync`, `lara:v8:verify -v`; a fresh `invent10725.sql` and media archive (Python) in `invent-deploy/`; remove local test hits from `seo_not_founds`.
- [ ] Server (v10 app): the user imports the dump with TablePlus, uploads and unpacks the media if it changed; clear the sessions; caches.
- [ ] Server: `robots.txt` back to the repo version (dashboard Pull).
- [ ] Server `.env`: `APP_URL=https://www.inventiverepair.nl`, `APP_ENV=production`, production `MAIL_*`/`MAIL_FROM_*`; `rm -rf storage/app/public/_fonts && php artisan google-fonts:fetch`; caches; the sitemap again.
- [ ] v8 app: crons off, `MAIL_MAILER=log`, domains removed (Cloudways temporary URL). Before each command check which app the shell is in.
- [ ] v10 app: primary domain `www.inventiverepair.nl`, alias `inventiverepair.nl`; staging domain removed; Let's Encrypt for both. **Clear the caches again after the move** (cached menu widgets keep the host of the request that filled them: macgyverbouw).
- [ ] Check: http → https and non-www → www with path and query, home, every menu item, news, events, the document, partners, the 404, every asset and image, the contact form with reCAPTCHA and both mails, the cookie consent, `robots.txt`, `sitemap.xml` (www URLs), the admin login and dashboard. Text pass production vs local.
- [ ] Search Console: resubmit the sitemap; watch 404s.
- [ ] Git: `main`/`develop` from `v10`, tag `v1.0.0`, Cloudways deploys `main`; the site repo becomes the only remote; project tags and the tooling worktree removed once everything is pushed.

## Fallback

- [ ] The v8 app stays on the server with its files and database, without domains, on its Cloudways temporary URL (crons and mail off), for a few weeks. Switching back: move the domains back and issue SSL again; content edited in v10 after the switch would have to be redone in v8.

## Known differences that stay

- Unknown URLs answer 404 (live: 200 with the home page).
- `/en/` is unpublished (live: 200, the "[EN] homepage"); v10 still answers it (core: an unpublished language stays routable); nothing links to it.
- Page titles end in " — Inventive Repair"; news, event and document detail pages carry their own title (v8: the module title "Nieuws", "Events", "Downloads").
- `inventiverepair.nl/*` will redirect to the same path on www (v8: to the www home page).
- The footer's "Sitemap" link goes to `/sitemap.xml` (v8: the pre-Lara `sitemap.html`, which showed the home page).
- The company film plays (v8: blocked `http://` embed); the home link to it goes to `/nl/bedrijfsfilm` (v8: a pre-Lara `.html` URL).
- Contact form: the v10 fields with v8's layout and button; the required-field marker text "* = verplicht veld" as live.
- The six partner detail pages answer 200 with the v10 view (live: 500); nothing links to them.
- The site keeps v8's fixed 960 px layout (no mobile layout, as live). The cookie banner is v10's consent plugin.
