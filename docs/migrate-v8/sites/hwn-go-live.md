# watersportverenigingnannewijd.nl: go-live checklist

v8.2.46 (`hwn8246`, production db `upykynmwjw`, theme `hwn2024`) → v10 (`hwn10729`, Lara CMS 10.7.29, theme `hwn`). General steps: runbook step 18 (with its Deploy lessons). Status 2026-11-27: staging done on https://v10.watersportverenigingnannewijd.nl (database `scfyvdesed`); the switch next.

Live: https://www.watersportverenigingnannewijd.nl on the Cloudways server at `95.179.176.155` (the same server as macgyverbouw, doormode and inventiverepair), nginx, **not behind Cloudflare**. DNS at webhost.company (`ns1–3.webhost.company`); `www` (CNAME to the root) and the root both `95.179.176.155`; **`v10.watersportverenigingnannewijd.nl` already resolves** to the same address and serves the placeholder v10 app (`<title>v10`, 2026-11-27). `http://www` → `https://www` 301 with the path; `watersportverenigingnannewijd.nl/*` (http and https) → `https://www.watersportverenigingnannewijd.nl/` 301 **without the path** (the query is kept: Cloudways' own www redirect, as inventiverepair). `/` → `/nl` 302. `robots.txt` `Allow: /`; **a sitemap** (`/sitemap.xml` 200, 520 URLs). Plan with every decision: `hwn8246/.ai/plans/2026-11-26-migrate-v8-to-v10-hwn.md`; inventory: `sites/hwn-inventory.md`; text pass: `hwn8246/.ai/plans/hwn-compare/`.

`dehwn.nl` (the domain of the club's mail, `info@dehwn.nl`) is a separate domain on Cloudflare (MX Microsoft 365); its web origin answers 522 (2026-11-27). Not part of this migration.

## Repositories

- **`appdezign/watersportverenigingnannewijd-nl`, branch `v10`** (pushed 2026-11-27, `d0e272c`): the site. The project at 10.7.29 (with `RedirectToCanonicalHost` in `bootstrap/app.php`), the migration tooling, the generated `laracms/app` (7 site content types: blog, event, gallery, video, doc, link, contactform; the 8 `app` types and classicform skipped), `laracms/themes/hwn`, `lang/vendor`, `v8-overrides.php` (2 overrides: German unpublished, blog 6144's link to the 2023 annual meeting document), `THEME_NAME = 'hwn'`, Google fonts `roboto-hwn` and `source-sans-pro`, and the built assets (`public/assets/admin`, `public/assets/themes/{base,hwn}`, `public/assets/filament/img`; runbook "Built assets in git"). Not in git: `storage/app/public` (media), `public/storage` (run `storage:link`), `public/sitemap.xml` (generated). No `main`/`develop` yet: create them from `v10` at the go-live.
- **`appdezign/lara-pro-cms-project`, branch `v8-migration`**: the tooling and the runbook. Tooling changes go there first (worktree `__migrate/hwn10729-tooling`), then merged into `v10`.
- After go-live: make the site repo the only remote, `main`/`develop`, a version tag; remove the project tags and the tooling worktree once everything is pushed (runbook gotcha "After go-live, make the site repo the only remote").

## Open before staging

- [x] **Staging app and domain** (user, 2026-11-27): **https://v10.watersportverenigingnannewijd.nl** (the DNS record exists and the placeholder app answers). A v10 Cloudways app on the same server (it becomes production at the switch). Database **`scfyvdesed`** (user, 2026-11-27). PhpStorm SFTP project: `__lara10_cloudways/remote/watersportverenigingnannewijd.nl_v10_cloudways` (server "cloudways", mapping `/` → project; holds the placeholder `public/index.html`).
- [x] **Keys** (user, 2026-11-26: the v8 keys on the server): Google Maps (`GOOGLE_MAPS_API_KEY`, `GOOGLE_MAPS_GEOCODING_API_KEY`; the map with both harbours on the contact page), reCAPTCHA (`GOOGLE_RECAPTCHA_SITE_KEY`, `GOOGLE_RECAPTCHA_SECRET_KEY`; the contact form) and `GOOGLE_TRANSLATE_API_KEY` from the v8 app's `.env` (`__cloudways/watersportverenigingnannewijd.nl_cloudways/.env` has them) go into the v10 app's `.env` on the server; check that the reCAPTCHA keys allow the staging domain. Locally they stay empty. GA4: `CONSENT_GA4_ID=G-0GSPCBY43J` (v8 setting `ga4_id`); staging then counts in the live property. Dashboard: `ANALYTICS_PROPERTY_ID=462017707` (as v8) with the service-account file.
- [x] **Mail** (user, 2026-11-27: sender as proposed): the contact form goes to **`info@dehwn.nl`** = `company_email` (imported from v8; with `APP_ENV=production` `FormController` sends to it and from it). Production: AuthSMTP as the v8 app (`MAIL_MAILER=smtp`, `MAIL_HOST=mail.authsmtp.com`, `MAIL_PORT=25`, `MAIL_ENCRYPTION=null`, username/password from the v8 `.env`). Sender for the other mails: `MAIL_FROM_ADDRESS=info@dehwn.nl`, `MAIL_FROM_NAME="De HWN"` (v8 has no `MAIL_FROM_*`: the config default `beheer@firmaq.nl`). DNS (checked 2026-11-27): `dehwn.nl` SPF `v=spf1 a mx include:_spf.yourfilter.nl include:authsmtp.com include:spf.protection.outlook.com ~all`, MX Microsoft 365, DMARC `p=none`; `watersportverenigingnannewijd.nl` SPF `v=spf1 a mx include:authsmtp.com ~all`, MX `mail.watersportverenigingnannewijd.nl`, no DMARC. Staging: dev mailer.
- [x] **`APP_NAME="Heerenveense Watersportvereniging Nannewijd"`** (user, 2026-11-26): page title suffix and `og:site_name` (live titles have no suffix; kept, as the earlier sites). On the server with the quotes (Deploy lessons).
- [x] **www redirect**: `RedirectToCanonicalHost` (project) answers `watersportverenigingnannewijd.nl/<path>?<query>` with a 301 to `APP_URL` + path + query, only with `APP_ENV=production` (live drops the path). Nothing to decide; check after the switch.
- [x] **Sitemap** (user, 2026-11-27: v10's list): v10 generates 513 URLs, live 520. Only on live: `/en/` (the demo `[EN] homepage`; no `en` language in v10), `/nl/contact/ligplaatsen` (classicform, skipped) and 5 **expired** news items (`publish_to` in the past: 6134, 6138, 6157, 6160, 6161; live and v10 both answer them with a 302 to `/nl/nieuws`). Every other URL is the same. Kept: v10 leaves out only URLs that redirect or no longer exist.
- [x] **Cookie banner privacy link** (user, 2026-11-27; `d0e272c` on `v10`): v8's banner linked to `/{language}/privacy`; the page `/nl/privacy` exists (200 on live and v10). v10 `config/consent.php` `ui.policy_url` = `'/nl/privacy'` (was `null`).

## Staging

**Manual server steps first** (they are in no repo; remind the user again before the first deploy, plan task 16):

- Cloudways PHP **8.4**, and enable **`tmpfile`, `highlight_file` and `fpassthru`** (Application Settings → PHP: remove them from `disable_functions`).
- Upload the Google Analytics service-account file by hand to **`storage/app/analytics/service-account-lara-dash.json`**; without it the admin dashboard errors on the first deploy.

Locally (in `hwn10729`, on `v10`):

```bash
php artisan lara:v8:verify -v                    # ok 33, warning 2 (the two dead links left as they are), 2 site overrides in place
mysqldump -h127.0.0.1 -uroot --single-transaction --routines v10_hwn > ../hwn-deploy/hwn10729.sql
python3 -c 'import tarfile; t = tarfile.open("../hwn-deploy/hwn10729-media.tgz", "w:gz"); t.add("storage/app/public", arcname="public", filter=lambda i: None if i.name == "public/_fonts" or i.name.startswith("public/_fonts/") else i); t.close()'
# not macOS tar: NFD file names and ._* files (seerden)
```

The deploy files live in **`__migrate/hwn-deploy/`**: `hwn10729.sql` (the v10 database), `hwn10729-media.tgz` (`storage/app/public` without `_fonts`; about 1.1 GB of media: upload takes a while) and `backups/` (dumps before the final sync). The dump holds user password hashes: never in a repo or the home folder.

On the server, guided one step at a time (runbook step 18: one command or setting, wait for the result, check it from here):

1. Code: Cloudways "Deployment via Git", repo `appdezign/watersportverenigingnannewijd-nl`, branch `v10`, Pull (it runs no composer, build or artisan). `php -v` (8.4), `composer install --no-dev -o`.
2. `.env` (keep the server's DB settings; `key:generate`): `APP_NAME="Heerenveense Watersportvereniging Nannewijd"`, `APP_ENV=staging`, `APP_DEBUG=false`, `APP_URL=https://v10.watersportverenigingnannewijd.nl`, `LARA_CLIENT_THEME=hwn`, `MAIL_DEV_*`, the keys above, `CONSENT_GA4_ID=G-0GSPCBY43J`, `ANALYTICS_PROPERTY_ID=462017707`; no `LARA_V8_*`. Template: `hwn-deploy/staging.env`.
3. Database: the user imports `hwn-deploy/hwn10729.sql` into the app's database with **TablePlus** (name the database and the file at every step; check a page from here right after).
4. Media: the user uploads `hwn10729-media.tgz` with **SFTP in PhpStorm** to `public_html/storage/app/`, then `rm -rf storage/app/public && tar -xzf storage/app/hwn10729-media.tgz -C storage/app` and removes the archive; check the file count against the archive, no `._*`, names NFC.
5. `rm -f public/storage && php artisan storage:link` (check `ls -l public/storage`), `php artisan google-fonts:fetch` (after `APP_URL`; Roboto and Source Sans Pro), `php artisan migrate:status | grep -i pending` (expected: nothing).
6. Caches: `php artisan optimize:clear && php artisan responsecache:clear && php artisan lara:route:cache` (after every database import).
7. Scheduler (Cloudways Cron Job Management): `* * * * * cd {app root} && php artisan schedule:run >> /dev/null 2>&1`.
8. Sitemap once: `php artisan schedule:test --name=google-sitemap`; `ls -l public/sitemap.xml`; every URL on the staging host.
9. Block indexing of staging (`robots.txt` `Disallow: /`; a dashboard pull restores the repo `robots.txt`, so block again after each pull).
10. GA service-account file (manual step above); the admin dashboard.
11. Contact form (both mails on the dev mailer), the map with both harbours, the livestream page.
12. Admin: open and save a page, a news item, a document and a photo album; the front URLs still identical afterwards.

Deploy files (2026-11-27 10:42; `hwn10729.sql` again at 11:32 after the div fix, verify ok 33, warning 2, 2 overrides, the 4 local test 404 hits removed): `hwn10729.sql` (1.4 MB, 53 tables, no `hwn10729.test`), `hwn10729-media.tgz` (1.1 GB, Python tarfile, 2057 files; 0 non-NFC, 0 `._*`, no `_fonts`), `staging.env` (secrets as `{...}` placeholders; `ANALYTICS_PROPERTY_ID=462017707` added: without it the dashboard shows only the versions widget).

Deploy log (guided):

- Step 1 (user, 2026-11-27): PHP 8.4 with `tmpfile`, `highlight_file`, `fpassthru`; Deployment via Git, `v10` pulled; `composer install --no-dev -o`. Checked: theme CSS and SVG 200, `/` → `/nl` 302 (Laravel). The placeholder `public/index.html` (not in the repo) still served: removed in step 2.
- Step 2 (user, 2026-11-27): `.env` from `hwn-deploy/staging.env`, `key:generate`, placeholder `public/index.html` removed (`/index.html` now Laravel). Checked from here: `/nl` 200 with theme `hwn` and the `APP_NAME` suffix, `/admin/login` 200. **Database `scfyvdesed` and the media were already in place**: text pass staging vs local (`hwn-compare/compare_staging.py`, `staging-pass.md`) 64 URLs identical (status, title, headings, text); Glide gallery image 200; `fonts.css` 302 (not fetched yet). Origin confirmed below.
- Steps 3–4 (user, 2026-11-27, done ahead of the guide): `hwn-deploy/hwn10729.sql` (10:42) imported into `scfyvdesed` with TablePlus; media archive uploaded and unpacked. Checked from here: the staging text pass above (64 URLs identical), Glide images 200. File count on the server: with step 5.
- Step 5 (user, 2026-11-27): media on the server 2057 files (= the archive), 0 `._*`; `storage:link`; `google-fonts:fetch` (incl. `roboto-hwn`, `source-sans-pro`); `migrate:status`: 0 pending. Checked from here: both `fonts.css` 200 from the staging host, font files 200, no local or `http://` references.
- Step 6 (user, 2026-11-27): `optimize:clear`, `responsecache:clear`, `lara:route:cache`; staging text pass again: 64 URLs identical.
- Step 7 (user, 2026-11-27): scheduler cron (Cloudways Cron Job Management, `schedule:run` every minute in `/home/1652362.cloudwaysapps.com/scfyvdesed/public_html`).
- Step 8 (user, 2026-11-27): sitemap (`schedule:test --name=google-sitemap`). Checked from here: 513 URLs, all on `v10.watersportverenigingnannewijd.nl`, the same list as local; 485 answer 200, 16 folders and unpublished items 302, **12 news items 404**: 11 slugs with dots (`heerenveens-kanaal.`, `tijgerhaai-a.n.w.b.`, `…20.00-uur`, …) and `2015`. The show route `{slug}.html` compiles to `[^/\.]++` (Symfony: no separator in the slug), in v8 too: live shows them as the not-found page with status 200, v10 with 404. Not a regression; old news (2014–2021).
- Step 9 (user, 2026-11-27): `robots.txt` `Disallow: /` (checked from here; block again after every pull until the switch).
- Step 10 (user, 2026-11-27): GA service-account file uploaded to `storage/app/analytics/service-account-lara-dash.json`; the admin dashboard shows the Analytics charts (`ANALYTICS_PROPERTY_ID=462017707`). Checked from here: the contact page loads the Maps script with a key; `/nl/livestream` 200.
- Step 11 (user, 2026-11-27): contact form sent, both mails on the dev mailer; the map (both harbours) and the livestream OK.
- Step 12 (user, 2026-11-27): a page, a news item, a document and a photo album saved without errors; menus OK. Staging text pass afterwards: 63 identical, **De Welle "Algemeen" (3962, saved) different**: the v10 rich editor unwraps v8 `<div>` lines on save ("Mark PitTelefoon: …"). Affected: hwn pages 3959, 3962, 3967 (inventiverepair: 11 pages, 2 blogs). Fixed in the tooling (user, 2026-11-27: div → p at import): `937f493`, `39ddb63`, `d77e011` on `v8-migration` (`DivBlocks`: consecutive div lines one `<p>` with `<br />`, a spacer div ends it, left/justify do not split; fixture + tests, runbook step 8). `import-content`: 3 pages updated, second run unchanged, verify ok 33 / warning 2. Theme (`b5735d3` on `v10`): images in a paragraph inline, as v8 (preflight made them blocks: an empty line after the team photo; Geschiedenis too). Measured at 1440 px against live: team page "Mark Pit" at the same height, De Welle within 6 px, De Bron within 15 px; Geschiedenis 3506 vs live 3510 (was 3531). Text pass local vs live unchanged; LaraV8 173 passed. New `hwn10729.sql` (11:32); staging to update (Pull, dump, robots, caches).
- Staging update 1 (user, 2026-11-27): Pull `v10` (`b5735d3`), `robots.txt` `Disallow: /` again. Checked from here: the new theme CSS `app-B_JFM5_1.css` 200, robots blocked. (No git on the Cloudways server: check a deploy by its asset hashes.)
- Staging update 2 (user, 2026-11-27): `hwn10729.sql` (11:32) into `scfyvdesed`, caches. Checked from here: staging text pass 64 URLs identical to local; the three converted pages serve the joined lines; theme CSS as local.

Checks:

- [x] Server: PHP 8.4 with `tmpfile`, `highlight_file`, `fpassthru`; GA service-account file, admin dashboard; scheduler cron.
- [x] Every URL of the text pass (`hwn-compare/urls.txt`, 64 URLs) identical to `hwn10729`; assets and images of home, a sub page, news, downloads, a photo album and the contact form OK; Glide images 200; `/admin/login` 200; no local or `http://` references.
- [x] The sticky menu bar and its dropdowns; the mobile menu; the topbar links (Facebook, WhatsApp, "Mail ons").
- [x] Contact form: both mails (webmaster and confirmation) on the dev mailer; the map with Jachthaven De Welle and De Bron.
- [x] Admin: a page, a news item, a document and a photo album open and save. Edits on staging are lost at the switch.

## Content to settle before the freeze

- [ ] Any content changed in v10 before go-live: record it in `v8-overrides.php`, or the final sync overwrites it.
- [x] The German rows (6 pages, 1 blog, 3 docs, 1 widget; language unpublished) and the `en` demo leftovers: kept as imported; only the German menu links to them, nothing on the Dutch site does (live replaced German by `/nl/deutsch`).

## Switch

**Approach (user rule): the staging app becomes production.** The v10 app gets the production domains in the Cloudways dashboard; the v8 app stays as the fallback (no domains, crons off, `MAIL_MAILER=log`). No DNS change (same server). Guided step by step.

Before the day:

- [ ] Production mail settings and sender decided (see Open before staging); active once `APP_ENV=production`.
- [ ] reCAPTCHA keys: the v8 keys, live on `www.watersportverenigingnannewijd.nl`, in the v10 app's `.env`; the widget renders only with `APP_ENV=production`: check right after the switch.
- [ ] `.env`: `SESSION_DOMAIN=null`; `SESSION_DRIVER=file` (clearing the sessions: `rm -f storage/framework/sessions/*`).
- [ ] Cloudways backups of the v10 app (daily, plus a manual backup before the switch).

The switch:

- [ ] Content freeze on the v8 admin (news and photo albums change often: the newest album is from 2026-11-25).
- [ ] Final production dump from the v8 app with TablePlus into `hwn8246/.lara/production_switch/dbdump/` (before its domains move); production media with SFTP into `hwn8246/.lara/production_switch/media` (emptied first; `LARA_V8_MEDIA_PATH` points there).
- [ ] Backups first: `v10_hwn` and `v8_hwn` into `hwn-deploy/backups/` (`*-before-final-sync.sql`).
- [ ] Locally: reload `v8_hwn` from the fresh dump (runbook 17; the grant stays), `lara:v8:sync --dry-run`, `lara:v8:sync`, `lara:v8:verify -v`; a fresh `hwn10729.sql` and media archive (Python) in `hwn-deploy/`; remove local test hits from `seo_not_founds`.
- [ ] Server (v10 app): the user imports the dump with TablePlus, uploads and unpacks the media if it changed; clear the sessions; caches.
- [ ] Server: `robots.txt` back to the repo version (dashboard Pull).
- [ ] Server `.env`: `APP_URL=https://www.watersportverenigingnannewijd.nl`, `APP_ENV=production`, production `MAIL_*`/`MAIL_FROM_*`; `rm -rf storage/app/public/_fonts && php artisan google-fonts:fetch`; caches; the sitemap again.
- [ ] v8 app: crons off, `MAIL_MAILER=log`, domains removed (Cloudways temporary URL). Before each command check which app the shell is in.
- [ ] v10 app: primary domain `www.watersportverenigingnannewijd.nl`, alias `watersportverenigingnannewijd.nl`; staging domain removed; Let's Encrypt for both. **Clear the caches again after the move** (cached menu widgets keep the host of the request that filled them: macgyverbouw).
- [ ] Check: http → https and non-www → www with path and query, home, every menu item, news, downloads and a document, photo albums, the livestream, the 404, every asset and image, the contact form with reCAPTCHA and both mails, the cookie consent, `robots.txt`, `sitemap.xml` (www URLs), the admin login and dashboard. Text pass production vs local.
- [ ] Search Console: resubmit the sitemap; watch 404s.
- [ ] Git: `main`/`develop` from `v10`, tag `v1.0.0`, Cloudways deploys `main`; the site repo becomes the only remote; project tags and the tooling worktree removed once everything is pushed.

## Fallback

- [ ] The v8 app stays on the server with its files and database, without domains, on its Cloudways temporary URL (crons and mail off), for a few weeks. Switching back: move the domains back and issue SSL again; content edited in v10 after the switch would have to be redone in v8.

## Known differences that stay

- Unknown URLs answer 404 (live: 200 with the not-found text).
- 12 old news items (11 slugs with dots, and `2015`) cannot be opened, as on live (v10: 404, live: the not-found text with 200); both sitemaps list them. Making them reachable would need a slug override (and redirects) or a core route constraint (`->where('slug', '[^/]+')`, laracms10).
- `/de/` answers 404 (German unpublished; live: a redirect to `/nl/de/…`, the German pages were never reachable). `/en/` still serves the `[EN] homepage` (as live; core: an unpublished or missing language stays routable); nothing links to it.
- Page titles end in " — Heerenveense Watersportvereniging Nannewijd"; news and document detail pages carry their own title.
- `watersportverenigingnannewijd.nl/*` will redirect to the same path on www (v8: to the www home page).
- Unpublished pages show v10's login page (v8: an empty page).
- The sitemap leaves out the 5 expired news items, `/en/` and the skipped "Ligplaatsen" form.
- The classicform "Ligplaatsen" (11 entries, last 2019; only in an unpublished menu item) and the 8 `app` types are not migrated; their data stays in the v8 database.
- The home page shows the unpublished CTA "Welkom bij De HWN" and not the published CTA 99 (as live: the CTA widget takes the first CTA of its hook).
- The cookie banner is v10's consent plugin.
