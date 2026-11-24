# macgyverbouw.nl: go-live checklist

v8.2.46 (`macgyv8246`, production db `nasswqfkpw`, theme `macgyver`) → v10 (`macgyv10723`, Lara CMS 10.7.24, theme `macgyver`). General steps: runbook step 18 (with its Deploy lessons). Status 2026-11-24: **staging done** on https://v10.macgyverbouw.nl (Lara CMS 10.7.24 since 2026-11-24; deploy files made 2026-11-23 21:29, verify ok 28); next: the switch.

Live: https://www.macgyverbouw.nl on the Cloudways server `1652362` (v8 app `phplaravel-1652362-6575259.cloudwaysapps.com`, the same server as doormode), nginx, **not behind Cloudflare**. DNS at webhost.company (`ns1–3.webhost.company`), `www` and the root both `95.179.176.155`. `http://` and `macgyverbouw.nl` → `https://www.macgyverbouw.nl/` 301, **without the path** (`macgyverbouw.nl/nl/nieuws` lands on the home page: Cloudways' own www redirect). `robots.txt` `Allow: /`; **no sitemap** (`/sitemap.xml` 302 to the home page). Plan with every decision: `macgyv8246/.ai/plans/2026-11-23-migrate-v8-to-v10-macgyverbouw.md`; inventory: `sites/macgyv-inventory.md`; text pass: `macgyv8246/.ai/plans/macgyver-compare/`.

## Repositories

- **`appdezign/macgyverbouw-nl`, branch `v10`** (pushed 2026-11-23): the site. The project at 10.7.23, the migration tooling, the generated `laracms/app` (6 site content types: blog, gallery, contactform, classicform, quoteform, callmeform; 12 empty demo types skipped), `laracms/themes/macgyver`, `lang/vendor`, `v8-overrides.php` (6 overrides: 4 contact labels, the old contact link in news 269, English unpublished), `THEME_NAME = 'macgyver'`, fonts `@googlefonts('macgyver')` (Oswald + Roboto, in `config/google-fonts.php`; body text Helvetica Neue), and the built assets (`public/assets/admin`, `public/assets/themes/{base,macgyver}`, `public/assets/filament/img`; runbook "Built assets in git"). Not in git: `storage/app/public` (media), `public/storage` (run `storage:link`; untracked since `6fd1b32`). No `main`/`develop` yet: create them from `v10` at the go-live.
- **`appdezign/lara-pro-cms-project`, branch `v8-migration`**: the tooling and the runbook. Tooling changes go there first (worktree `__migrate/macgyv10723-tooling`), then merged into `v10`.
- After go-live: make the site repo the only remote, `main`/`develop`, a version tag; remove the project tags and the tooling worktree once everything is pushed (runbook gotcha "After go-live, make the site repo the only remote").

## Open before staging

- [x] **Staging app and domain** (user, 2026-11-23): **https://v10.macgyverbouw.nl**, a v10 Cloudways app on server `1652362` (it becomes production at the switch); the DNS record goes in at webhost.company (no Cloudflare here). Database **`cnhhvubunk`** (user, 2026-11-23). PhpStorm SFTP project: `__lara10_cloudways/remote/macgyverbouw.nl_v10_cloudways` (server "cloudways", host `95.179.176.155`; mapping "macgyver10").
- [x] **Keys** (user, 2026-11-23: use the v8 keys): Google Maps (`GOOGLE_MAPS_API_KEY`, `GOOGLE_MAPS_GEOCODING_API_KEY`; the map on the contact page) and reCAPTCHA (`GOOGLE_RECAPTCHA_SITE_KEY`, `GOOGLE_RECAPTCHA_SECRET_KEY`; v2 checkbox on the contact, quote and call-me forms) from the v8 app's `.env` (`__cloudways/macgyverbouw.nl_cloudways/.env` has them) go into the v10 app's `.env` on the server; check that the reCAPTCHA keys allow the staging domain. Locally they stay empty. GA4: `CONSENT_GA4_ID=G-6BCQSKD8FY` (v8 setting `ga4_id`); staging then counts in the live property.
- [x] **Mail** (user, 2026-11-23): the forms go to **`johan@macgyverbouw.nl`** = `company_email` (imported from v8; `FormController` sends to it and from it with `APP_ENV=production`). Production: AuthSMTP as the v8 app (`MAIL_MAILER=smtp`, `MAIL_HOST=mail.authsmtp.com`, `MAIL_PORT=25`, `MAIL_ENCRYPTION=null`, username/password from the v8 `.env`); `MAIL_FROM_ADDRESS=johan@macgyverbouw.nl`, `MAIL_FROM_NAME="MacGyverBouw"` (for the other mails; v8 used the config default `beheer@firmaq.nl`). DNS (checked 2026-11-23): SPF `v=spf1 a mx include:zoho.com include:authsmtp.com ~all`, MX Zoho, DMARC `p=none`. Staging: dev mailer.
- [x] **`APP_NAME="MacGyverBouw"`** (user, 2026-11-23): page title suffix " — MacGyverBouw" and `og:site_name` (live titles have no suffix; kept, as seerden and doormode). On the server with the quotes (Deploy lessons).
- [x] **www redirect** (user, 2026-11-23: keep Cloudways' redirect as live): live redirects `macgyverbouw.nl/*` to the www **home page** (Cloudways' redirect drops the path; seerden kept it that way, doormode moved it to Cloudflare). There is no Cloudflare here; proposed: keep Cloudways' redirect as live (no SEO change), check after the switch that it is set on the v10 app (it is an app setting and does not move with the domain: doormode lesson).
- [x] **Sitemap** (user, 2026-11-23; `64ebdec` on `v10`: 14 URLs): live has none; v10 generates `public/sitemap.xml` (scheduler) with 16 URLs locally: the home, every menu page including the redirecting folders `/nl/diensten` and `/nl/contact`, the unpublished Privacy page and the three unpublished form pages (linked from the header button and the sidebar); the 2 blog posts and the gallery are missing (project-level generator bug, runbook seerden section). Proposed, as doormode (site code `laracms/app/Sitemap/GoogleSitemapGenerator.php`): leave out unpublished pages that nothing links to and the redirecting folders; keep the three form pages and Privacy (linked from the cookie banner, below).
- [x] **Cookie banner privacy link** (user, 2026-11-23; `64ebdec`): v8's banner linked to `/nl/privacy`; v10's consent plugin `config/consent.php` `ui.policy_url` = `'/nl/privacy'`.

## Staging

**Manual server steps first** (they are in no repo; remind the user again before the first deploy, plan task 16):

- Cloudways PHP **8.4**, and enable **`tmpfile`, `highlight_file` and `fpassthru`** (Application Settings → PHP: remove them from `disable_functions`).
- Upload the Google Analytics service-account file by hand to **`storage/app/analytics/service-account-lara-dash.json`**; without it the admin dashboard errors on the first deploy.

Locally (in `macgyv10723`, on `v10`):

```bash
php artisan lara:v8:verify -v                    # ok 28, no warnings, 6 site overrides in place
mysqldump -h127.0.0.1 -uroot --single-transaction --routines v10_macgyv > ../macgyv-deploy/macgyv10723.sql
python3 -c 'import tarfile; t = tarfile.open("../macgyv-deploy/macgyv10723-media.tgz", "w:gz"); t.add("storage/app/public", arcname="public", filter=lambda i: None if i.name == "public/_fonts" or i.name.startswith("public/_fonts/") else i); t.close()'
# not macOS tar: NFD file names and ._* files (seerden)
```

The deploy files live in **`__migrate/macgyv-deploy/`**: `macgyv10723.sql` (the v10 database), `macgyv10723-media.tgz` (`storage/app/public` without `_fonts`) and `backups/` (dumps before the final sync). The dump holds user password hashes: never in a repo or the home folder.

On the server, guided one step at a time (runbook step 18: one command or setting, wait for the result, check it from here):

1. Code: Cloudways "Deployment via Git", repo `appdezign/macgyverbouw-nl`, branch `v10`, Pull (it runs no composer, build or artisan). `php -v` (8.4), `composer install --no-dev -o`.
2. `.env` (keep `APP_KEY` and the server's DB settings): `APP_NAME="MacGyverBouw"`, `APP_ENV=staging`, `APP_DEBUG=false`, `APP_URL=https://v10.macgyverbouw.nl`, `LARA_CLIENT_THEME=macgyver`, `MAIL_DEV_*`, the keys above, `CONSENT_GA4_ID=G-6BCQSKD8FY`.
3. Database: the user imports `macgyv-deploy/macgyv10723.sql` into the app's database with **TablePlus** (name the database and the file at every step; check a page from here right after: doormode lesson).
4. Media: the user uploads `macgyv10723-media.tgz` with **SFTP in PhpStorm** to `public_html/storage/app/`, then `rm -rf storage/app/public && tar -xzf storage/app/macgyv10723-media.tgz -C storage/app` and removes the archive; check: 128 files (the 45 imported media plus the demo images the repo tracks), no `._*`, names NFC.
5. `rm -f public/storage && php artisan storage:link` (check `ls -l public/storage`), `php artisan google-fonts:fetch` (after `APP_URL`), `php artisan migrate:status | grep -i pending` (expected: nothing).
6. Caches: `php artisan optimize:clear && php artisan responsecache:clear && php artisan lara:route:cache` (after every database import: doormode lesson).
7. Scheduler (Cloudways Cron Job Management): `* * * * * cd {app root} && php artisan schedule:run >> /dev/null 2>&1`.
8. Sitemap once: `php artisan schedule:test --name=google-sitemap`; `ls -l public/sitemap.xml`.
9. Block indexing of staging (Cloudways password or `robots.txt` `Disallow: /`; a dashboard pull restores the repo `robots.txt`, so block again after each pull).

Deploy log (2026-11-23, guided):

- Step 1: PHP 8.4 with `tmpfile`, `highlight_file`, `fpassthru` (user). Database `cnhhvubunk`.
- Step 2: Deployment via Git, `v10` pulled (static files 200; Laravel 500 until `vendor` and `.env`).
- Step 3: `composer install --no-dev -o` on PHP 8.4.28 (user).
- Steps 4–5 (user, 2026-11-24): `.env` from `macgyv-deploy/staging.env` (placeholders filled in, `key:generate`), `macgyv10723.sql` imported into `cnhhvubunk` with TablePlus, media unpacked, `storage:link`, `google-fonts:fetch`.
- Step 6: `optimize:clear`, `responsecache:clear`, `lara:route:cache` (`APP_ENV=staging`, `APP_DEBUG=false`, `APP_URL=https://v10.macgyverbouw.nl`).
- Step 7: scheduler cron (Cloudways Cron Job Management, `schedule:run` every minute).
- Step 8: sitemap (`schedule:test --name=google-sitemap`): 14 `v10.macgyverbouw.nl` URLs, all 200.
- Step 9: `robots.txt` `Disallow: /` (block again after every pull until the switch).
- Step 10: GA service-account file uploaded; the admin dashboard works.
- Steps 11–12: the three forms (both mails each, v8 headings), the map, the admin (open and save) checked by the user.
- Redeploy for **Lara CMS 10.7.24** (2026-11-24; widget cache keys with the language, new sitemap generator with the site rule on top): dashboard Pull, `composer install --no-dev -o`, `migrate:status` (0 pending), caches, sitemap, `robots.txt` blocked again. Checked: sitemap 17 URLs (the 14 + both news posts + "Verbouw woonboerderij"), all 200; after `/en/` requests `/nl/` keeps its slider; text pass 22 URLs identical to local.

Checks:

- [x] Server: PHP 8.4.28 with `tmpfile`, `highlight_file`, `fpassthru`; GA service-account file, admin dashboard; scheduler cron (user, 2026-11-24).
- [x] Every URL of the text pass (`macgyver-compare/urls.txt`, 22 URLs) identical to `macgyv10723` (`staging-pass.md`, after the caches and again after the admin saves); 34 asset and link URLs of home, news, gallery and contact OK; Glide images 200; `fonts.css` from the staging domain; `/admin/login` 200; no local or `http://` references (2026-11-24).
- [x] Projecten lists only "Verbouw woonboerderij" ("Bouw dakkapel" has no images: hidden, as live); the home slider, the sidebar slider and the Facebook block show.
- [x] Forms (user, 2026-11-24: all three sent, both mails each, headings correct, map loads): contact, quote and the call-me modal (sidebar "Bel mij terug", ajax): both mails (webmaster and confirmation) on the dev mailer, with the v8 mail headings ("Verzoek via website", "Offerte aangevraagd via website", "Belmij-verzoek via website"); no "… Email Title" page appears (mail pages carry the v10 slug since `a01cdc0`). The map with the Maps key.
- [x] Admin (user, 2026-11-24): a page, a news item and the gallery open and save; the front URLs still identical afterwards. Edits on staging are lost at the switch.

## Content to settle before the freeze

- [ ] Any content changed in v10 before go-live: record it in `v8-overrides.php`, or the final sync overwrites it.
- [x] The English rows (page 1071 "[EN] homepage", the English CTAs and tags; language unpublished): kept as imported; nothing links to them.

## Switch

**Approach (user rule): the staging app becomes production.** The v10 app gets the production domains in the Cloudways dashboard; the v8 app stays as the fallback (no domains, crons off, `MAIL_MAILER=log`). No DNS change (same server). Guided step by step.

Before the day:

- [ ] Production mail settings and sender decided (see Open before staging); active once `APP_ENV=production`.
- [ ] reCAPTCHA keys: the v8 keys, live on `www.macgyverbouw.nl`, in the v10 app's `.env`; the widget renders only with `APP_ENV=production`: check right after the switch (contact, quote and the call-me modal).
- [ ] `.env`: `SESSION_DOMAIN=null`; `SESSION_DRIVER=file` (clearing the sessions: `rm -f storage/framework/sessions/*`).
- [ ] Cloudways backups of the v10 app (daily, plus a manual backup before the switch).
- [x] Sitemap, www redirect and privacy link decided (see Open before staging).

The switch:

- [ ] Content freeze on the v8 admin.
- [ ] Final production dump from the v8 app with TablePlus into `macgyv8246/.lara/dbdump/` (before its domains move); production media with SFTP into `macgyv8246/.lara/media` (emptied first; `LARA_V8_MEDIA_PATH` points there).
- [ ] Backups first: `v10_macgyv` and `v8_macgyv` into `macgyv-deploy/backups/`.
- [ ] Locally: reload `v8_macgyv` from the fresh dump (runbook 17), `lara:v8:sync --dry-run`, `lara:v8:sync`, `lara:v8:verify -v`; a fresh `macgyv10723.sql` and media archive (Python) in `macgyv-deploy/`. Expect pages reported "updated" for `position` only (runbook step 17 note).
- [ ] Server (v10 app): the user imports the dump with TablePlus, uploads and unpacks the media if it changed; NFC/`._*` check; clear the sessions.
- [ ] Server: `robots.txt` back to the repo version (dashboard Pull).
- [ ] Server `.env`: `APP_URL=https://www.macgyverbouw.nl`, `APP_ENV=production`, production `MAIL_*`/`MAIL_FROM_*`; `rm -rf storage/app/public/_fonts && php artisan google-fonts:fetch`; `php artisan optimize:clear && php artisan responsecache:clear && php artisan lara:route:cache`; the sitemap again.
- [ ] v8 app: crons off, `MAIL_MAILER=log`, domains removed (Cloudways temporary URL). Before each command check which app the shell is in.
- [ ] v10 app: primary domain `www.macgyverbouw.nl`, alias `macgyverbouw.nl` with the www redirect (decision above); remove the staging domain or redirect it; Let's Encrypt for both.
- [ ] Check: http → https and non-www → www, home, every menu item and the unpublished form pages and Privacy, both blog posts, the gallery, the 404, every asset and image, the three forms with reCAPTCHA and the mails, the cookie consent, `robots.txt`, `sitemap.xml` (www URLs), the admin login. Text pass production vs local.
- [ ] Search Console: submit the sitemap (live had none); watch 404s.
- [ ] Git: `main`/`develop` from `v10`, tag `v1.0.0`, Cloudways deploys `main`; the site repo becomes the only remote; project tags and the tooling worktree removed once everything is pushed.

## Fallback

- [ ] The v8 app stays on the server with its files and database, without domains, on its Cloudways temporary URL (crons and mail off), for a few weeks. Switching back: move the domains back and issue SSL again; content edited in v10 after the switch would have to be redone in v8.

## Known differences that stay

- Unknown URLs answer 404 with the "Oops" page (live: 200).
- `/en` is unpublished (live: a 500); v10 still answers `/en/` with the "[EN] homepage" page (core: an unpublished language stays routable); nothing links to it. The theme renders the sliders only in Dutch and the footer uses uncached menu widgets, because core caches those widgets without the language (core item for laracms10).
- Page titles end in " — MacGyverBouw".
- Form pages: required-field markers ("* = dit is een verplicht veld"); the buttons say "versturen" (v8 texts, set as translations).
- The call-me form opens in a daisyUI modal (v8: a Bootstrap modal), same fields and texts.
- The project detail title sits 16 px lower than live (v8's gallery detail had no section padding).
- The footer telephone link dials `company_telephone_real` (v8 linked the empty `company_telephone_clean`).
- The mobile menu is the v10 drawer (from the right, as v8's offcanvas).
- The cookie banner is v10's consent plugin.
