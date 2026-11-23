# doormode.nl: go-live checklist

v8.2.46 (`doormode8246`, production db `etdrzkydbm`, theme `doormode`) → v10 (`doormode10723`, Lara CMS 10.7.23, theme `doormode`). General steps: runbook step 18 (with its Deploy lessons). Status 2026-11-23: site code on `appdezign/doormode-nl` branch `v10`; staging https://v10.doormode.nl being deployed (deploy files made 13:04, verify ok 27).

Live: https://www.doormode.nl (`doormode.nl` and `http://` redirect 301 to `https://www`), **behind Cloudflare** (proxy: e-mail addresses in pages are obfuscated, `robots.txt` is Cloudflare's managed file with AI-bot rules). Plan with every decision: `doormode8246/.ai/plans/2026-11-23-migrate-v8-to-v10.md`; inventory: `sites/doormode-inventory.md`; text pass: `doormode8246/.ai/plans/doormode-compare/`.

## Repositories

- **`appdezign/doormode-nl`, branch `v10`** (pushed 2026-11-23): the site. The project at 10.7.23, the migration tooling, the generated `laracms/app` (9 site content types: blog, team, event, location, gallery, video, doc, contactform, classicform), `laracms/themes/doormode`, `lang/vendor`, `v8-overrides.php` (1 override: English unpublished), `THEME_NAME = 'doormode'`, fonts `@googlefonts('doormode')` (Open Sans + Playfair Display, in `config/google-fonts.php`), and the built assets (`public/assets/admin`, `public/assets/themes/{base,doormode}`, `public/assets/filament/img`; runbook "Built assets in git"). Not in git: `storage/app/public` (media), `public/storage` (run `storage:link`; untracked since `6002308`). No `main`/`develop` yet: create them from `v10` at the go-live.
- **`appdezign/lara-pro-cms-project`, branch `v8-migration`**: the tooling and the runbook. Tooling changes go there first (worktree `__migrate/doormode10723-tooling`), then `git merge v8-migration` into `v10`.
- After go-live: make the site repo the only remote, `main`/`develop`, a version tag; remove the project tags and the tooling worktree once everything is pushed (runbook gotcha "After go-live, make the site repo the only remote").

## Open before staging

- [x] **Staging app and domain** (user, 2026-11-23): **https://v10.doormode.nl**, the v10 Cloudways app on the same server as the v8 app (it becomes production at the switch). Through the Cloudflare proxy; on 2026-11-23 the app still answered 403 (nginx, empty). Database name: from the user at the TablePlus import. PhpStorm SFTP project: `__lara10_cloudways/remote/doormode.nl_v10_cloudways` (server "cloudways", root `/public_html`).
- [x] **Keys** (user, 2026-11-23: use the v8 keys): Google Maps (`GOOGLE_MAPS_API_KEY`, `GOOGLE_MAPS_GEOCODING_API_KEY`; the contact page map) and reCAPTCHA (`GOOGLE_RECAPTCHA_SITE_KEY`, `GOOGLE_RECAPTCHA_SECRET_KEY`; v2 checkbox on the contact form) from the v8 app's `.env` (the local copy `doormode8246/.env` has the same) go into the v10 app's `.env` on the server; check that the reCAPTCHA keys allow the staging domain. Locally they stay empty (user, 2026-11-23). GA4: `CONSENT_GA4_ID=G-52D5VGD7LJ` (v8 setting `ga4_id`); staging then counts in the live property.
- [ ] **Mail** (user): form mail goes to `company_email` = `info@doormode.nl` (the footer shows `doormode@live.nl`, fixed in the v8 view: which one should receive the form?). The v8 app sends through AuthSMTP (`MAIL_MAILER=smtp`, `MAIL_HOST=mail.authsmtp.com`, `MAIL_PORT=25`, `MAIL_ENCRYPTION=null`, username/password in the v8 `.env`), as seerden; its sender is the config default (a firmaq.nl address, "Firmaq Media"): set `MAIL_FROM_ADDRESS`/`MAIL_FROM_NAME` for doormode (SPF of the sender domain must include `authsmtp.com`). Staging: `MAIL_DEV_*` (e.g. Mailtrap).
- [x] **`APP_NAME="DOOR mode"`** (user, 2026-11-23): page title suffix " — DOOR mode" and `og:site_name` (live titles have no suffix; kept, as seerden). On the server with the quotes (Deploy lessons).
- [ ] **Cloudflare** (user): who manages the doormode.nl zone; at the switch the domains move to the v10 app on the same server (origin IP unchanged), so Cloudflare needs no change unless its SSL mode or a staging record does. Let's Encrypt in Cloudways behind the Cloudflare proxy: check the zone's SSL mode (Full/Full strict) and whether the certificate validates with the proxy on.

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

Checks:

- [ ] Server: PHP 8.4 with `tmpfile`, `highlight_file`, `fpassthru`; GA service-account file; scheduler cron.
- [ ] Every URL of the text pass (`doormode-compare/urls.txt`: 25 URLs) identical to `doormode10723` (status, title, text); every asset and image of the pages 200 (theme CSS/JS, Glide images, the wallpaper images, favicons, `/storage`, fonts, `/assets/filament/img` and the admin login).
- [ ] Evenementen lists no events (stock v8 front scope); the contact page shows the two-column grid and the map (Maps key).
- [ ] Sitemap: `/sitemap.xml` 200, 16 URLs (locally 2026-11-23): every published menu item and the contact page. **Unlinked pages left out** (user, 2026-11-23; `f5d67c3` on `v10`): the unpublished menu items Evenementen, Fotoalbum, Privacy and Offline (linked from nowhere); Contact stays (map icon in header and footer). Live's sitemap lists 32 URLs, among them those pages, the past event details and the 3 blog posts. The blog posts are missing from v10's because of the project-level `GoogleSitemapGenerator` bug (runbook seerden section: `MenuItem::type` is an enum, so entity detail pages are never listed).
- [ ] Forms: submit the contact form on staging; the mail arrives on the dev mailer.
- [ ] Admin: log in, open and save a few records; the front stays identical afterwards. Edits on staging are lost at the switch.

## Content to settle before the freeze

- [ ] Any content changed in v10 before go-live: record it in `v8-overrides.php`, or the final sync overwrites it.
- [ ] The two English rows (page 1071 "[EN] homepage", unpublished language): kept as imported; nothing links to them.

## Switch

**Approach (user rule): the staging app becomes production.** The v10 app gets the production domains in the Cloudways dashboard; the v8 app stays as the fallback (no domains, crons off, `MAIL_MAILER=log`). No DNS change (same server; Cloudflare keeps pointing to it). Guided step by step.

Before the day:

- [ ] Production mail settings and sender decided (see Open before staging); active once `APP_ENV=production`.
- [ ] reCAPTCHA keys for `www.doormode.nl` and `doormode.nl` in the v10 app's `.env`; the widget renders only with `APP_ENV=production`.
- [ ] `.env`: `SESSION_DOMAIN=null`; `SESSION_DRIVER=file` (clearing the sessions: `rm -f storage/framework/sessions/*`).
- [ ] Cloudways backups of the v10 app (daily).
- [x] Sitemap decision (user, 2026-11-23): unlinked pages left out, applied in `f5d67c3` on `v10` (pull before the switch; the sitemap is generated again at the switch).

The switch:

- [ ] Content freeze on the v8 admin.
- [ ] Final production dump from the v8 app with TablePlus into `doormode8246/.lara/dbdump/` (before its domains move); production media with SFTP into `doormode8246/.lara/media` (emptied first; `LARA_V8_MEDIA_PATH` points there).
- [ ] Backups first: `v10_doormode` and `v8_doormode` into `doormode-deploy/backups/`.
- [ ] Locally: reload `v8_doormode` from the fresh dump (runbook 17), `lara:v8:sync --dry-run`, `lara:v8:sync`, `lara:v8:verify -v`; a fresh `doormode10723.sql` and media archive (Python) in `doormode-deploy/`.
- [ ] Server (v10 app): the user imports the dump with TablePlus, uploads and unpacks the media; NFC/`._*` check; clear the sessions.
- [ ] Server: `robots.txt` back to the repo version (a dashboard pull restores it).
- [ ] Server `.env`: `APP_URL=https://www.doormode.nl`, `APP_ENV=production`, production `MAIL_*`/`MAIL_FROM_*`; `rm -rf storage/app/public/_fonts && php artisan google-fonts:fetch`; `php artisan optimize:clear && php artisan responsecache:clear && php artisan lara:route:cache`; the sitemap again.
- [ ] v8 app: crons off, `MAIL_MAILER=log`, domains removed (Cloudways temporary URL). Before each command check which app the shell is in (`grep -m1 '"version"' composer.json`).
- [ ] v10 app: primary domain `www.doormode.nl`, alias `doormode.nl` (redirect to `www`, as live); remove the staging domain or redirect it; SSL for both (behind Cloudflare, see Open before staging).
- [ ] Check: http → https and non-www → www (301), home, every menu item and the unpublished pages (Contact, Fotoalbum, Evenementen, Privacy, Offline), the 3 blog posts, the 404, every asset and image, the contact form with reCAPTCHA and the mail, the cookie consent, `robots.txt`, `sitemap.xml` (www URLs), the admin login.
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
