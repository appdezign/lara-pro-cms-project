# seerdencommunicatie.nl: go-live checklist

v8.2.46 (`seerden8246`, production db `xxqektecpc`, theme `seerden`) → v10 (`seerden10723`, Lara CMS 10.7.23, theme `seerden`). General steps: runbook step 18. Status 2026-11-19: staging https://dev.seerdencommunicatie.nl deployed and checked; next: the switch.

Live: https://www.seerdencommunicatie.nl (`seerdencommunicatie.nl` redirects 301 to `www`). Plan with every decision: `seerden8246/.ai/plans/2026-11-18-migrate-v8-to-v10.md`; inventory: `sites/seerden-inventory.md`.

## Repositories

- **`appdezign/seerdencommunicatie-nl`, branch `v10`** (pushed 2026-11-18, `38fe328`): the site. The project at 10.7.23, the migration tooling, the generated `laracms/app` (13 site content types), `laracms/themes/seerden`, `lang/vendor`, `v8-overrides.php` (5 overrides), `THEME_NAME = 'seerden'`, Manrope (`@googlefonts('manrope')`, already in `config/google-fonts.php`), and the built assets (`public/assets/admin`, `public/assets/themes/{base,seerden}`; see runbook "Built assets in git"). Not in git: `storage/app/public` (media) and `public/storage` (run `storage:link`). The repo has no `main`/`develop` yet: create them from `v10` when the release flow starts.
- **`appdezign/lara-pro-cms-project`, branch `v8-migration`**: the tooling and the runbook. Tooling changes go there first (worktree `__migrate/seerden10723-tooling`), then `git merge v8-migration` into `v10`.
- Locally `seerden10723` stays on `v10`; `../seerden10723-tooling` is a git worktree of `v8-migration`.
- After go-live: make the site repo the only remote (runbook gotcha "After go-live, make the site repo the only remote").

## Open before staging

- [x] **Staging** (user, 2026-11-18): https://dev.seerdencommunicatie.nl (on 2026-11-18 still a placeholder page, nginx).
- [x] **Keys** (user, 2026-11-18): the Google Maps (`GOOGLE_MAPS_API_KEY`, `GOOGLE_MAPS_GEOCODING_API_KEY`), reCAPTCHA (`GOOGLE_RECAPTCHA_SITE_KEY`, `GOOGLE_RECAPTCHA_SECRET_KEY`) and GA4 keys (`CONSENT_GA4_ID`; setting `ga4_id` `G-0B6CD217XK`) in the local `.env` are seerden's own: copy them to each server's `.env`.
- [ ] **Mail**: the production `MAIL_*` settings (form mail goes to `company_email` = `ellen@seerdencommunicatie.nl`) and `MAIL_FROM_*`; `MAIL_DEV_*` for staging.
- [x] **`APP_NAME="Seerden Communicatie"`** (user, 2026-11-18): the suffix of every page title (" — Seerden Communicatie") and `og:site_name`; live titles have no suffix (decision: keep v10's suffix). Set locally; set it on each server.

## Staging

**Manual server steps first** (they are in no repo):

- Cloudways PHP **8.4**, and enable **`tmpfile`, `highlight_file` and `fpassthru`** (Application Settings → PHP: remove them from `disable_functions`).
- Upload the Google Analytics service-account file by hand to **`storage/app/analytics/service-account-lara-dash.json`**; without it the admin dashboard errors on the first deploy.

Locally (in `seerden10723`, on `v10`):

```bash
php artisan lara:v8:verify -v                    # ok 36, no warnings
mysqldump -h127.0.0.1 -uroot --single-transaction --routines v10_seerden > ../seerden-deploy/seerden10723.sql
python3 -c 'import tarfile; t = tarfile.open("../seerden-deploy/seerden10723-media.tgz", "w:gz"); t.add("storage/app/public", arcname="public", filter=lambda i: None if i.name == "public/_fonts" or i.name.startswith("public/_fonts/") else i); t.close()'
# not macOS tar: it writes non-ASCII names as NFD (one editor image broke on staging) and adds ._* files
```

The deploy files live in **`__migrate/seerden-deploy/`** (as `anpak-deploy`): `seerden10723.sql` (the v10 database), `seerden10723-media.tgz` (`storage/app/public` without `_fonts`) and `backups/` (dumps before the final sync). The built assets are in git, so there is no assets archive. The dump holds user password hashes: never commit it; delete it from the server after loading. Staging files made 2026-11-19 10:44 (verify ok 36); the media archive rebuilt with Python at 11:09 (the bsdtar one is in `backups/`).

On the server (SSH as the application user, in the app root `public_html`):

```bash
# 1. code: Cloudways "Deployment via Git", branch v10, Pull (it runs no composer, build or artisan)
php -v                                            # 8.4
composer install --no-dev -o                      # assets are in git; no Node needed

# 2. .env (keep APP_KEY and the DB settings of the server)
#    APP_NAME="Seerden Communicatie"  APP_ENV=staging  APP_DEBUG=false  APP_URL=https://dev.seerdencommunicatie.nl  LARA_CLIENT_THEME=seerden
#    MAIL_DEV_*, the keys above, CONSENT_GA4_ID

# 3. data (upload seerden-deploy/seerden10723.sql and seerden10723-media.tgz to ~ first)
mysql {db} < ~/seerden10723.sql
rm -rf storage/app/public && tar -xzf ~/seerden10723-media.tgz -C storage/app
rm ~/seerden10723.sql                             # it holds user password hashes
php artisan storage:link
php artisan google-fonts:fetch                    # after APP_URL is set (the stylesheet holds absolute URLs)
php artisan migrate:status | grep -i pending      # expected: nothing

# 4. caches (and old sessions if deployed over an earlier install)
php artisan optimize:clear && php artisan responsecache:clear && php artisan lara:route:cache

# 5. scheduler (Cloudways: Cron Job Management):  * * * * * cd {app root} && php artisan schedule:run >> /dev/null 2>&1

# 6. the sitemap once
php artisan tinker --execute '(new \Lara\App\Sitemap\GoogleSitemapGenerator)(["nl"]);'
```

Checks:

- [ ] Server: PHP 8.4 with `tmpfile`, `highlight_file`, `fpassthru`; GA service-account file; scheduler cron.
- [ ] Code on `v10`, `.env` complete, data and media loaded, `storage:link`, fonts fetched.
- [x] Compared with `seerden10723` (2026-11-19, 27 URLs: every menu URL, both contact pages, the 7 projects, the 4 testimonials, `/nl/project`, `/nl/aanbevelingen`, `/en`, an unknown URL): status, text and image count identical; all 42 asset and image URLs of three pages 200 (theme CSS/JS, Glide images, favicons); cookie consent with GA4 `G-0B6CD217XK`; reCAPTCHA on the contact form; no English hreflang; no debug output.
- [x] Open after the first deploy (2026-11-19); rechecked after the user's fixes. **Final check 2026-11-19:** 27 URLs identical to `seerden10723` (status, text, image count, titles); all 78 asset and image URLs 200, including the PvdA editor image (renamed to NFC) and the fonts.
  - [x] Sitemap: `/sitemap.xml` 200, XML, 17 URLs.
  - [x] **`/storage`** fixed by the user (`rm public/storage && php artisan storage:link`): fonts.css and the 6 Manrope files 200; 77 of 78 asset/image URLs of the 25 pages 200.
  - [x] **`APP_NAME`** fixed by the user (it needed quotes; unquoted, staging gave 500): titles and `og:site_name` "Seerden Communicatie".
  - [x] **One editor image** (`images/shares/Campagnecoördinator PvdA … Fryslân …png`, Campagnes and an event page) still 302: the macOS-tar archive stored its name as NFD. Rename it to NFC on the server (command in the plan notes / below), and remove the `._*` files the archive added:
    ```bash
    php -r '$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("storage/app/public", FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST); foreach ($it as $f) { $p = $f->getPathname(); $n = dirname($p)."/".Normalizer::normalize(basename($p), Normalizer::FORM_C); if ($n !== $p) { rename($p, $n); echo "$p -> $n\n"; } }'
    find storage/app/public -name '._*' -print -delete
    ```
- [ ] The sitemap lists `/nl/nieuws`, `/nl/video`, `/nl/fotoalbums`, `/nl/project`, `/nl/aanbevelingen` and `/nl/instagram`: reachable (200, as on live) but in no published menu; live has no sitemap. Decide before the switch whether they belong in it.
- [x] Staging indexing blocked by the user: `robots.txt` is `Disallow: /` (2026-11-19).
- [x] Admin images (`/assets/filament/img`: login background, logo, admin favicon) were missing: the `.gitignore` ignored all of `public/assets/filament/`, but `img` comes from the admin Vite build. Tracked now (`4194977` on `v10`, pushed): pulled by the user; the 4 images and all 17 asset URLs of `/admin/login` 200 (2026-11-19).
- [x] Forms: the user submitted `/nl/contact` and `/nl/contact-1` on staging; both mails arrived in Mailtrap (dev mailer, 2026-11-19). Production mail (`MAIL_*`, `MAIL_FROM_*`; to `company_email`) and reCAPTCHA are checked right after the switch.
- [x] Admin: the user logged in on staging and saved some records (2026-11-19); afterwards the 27 front URLs are still identical to `seerden10723`. Edits made on staging are lost at the switch (the staging database is replaced by the synced local one).

## Content to settle before the freeze

- [ ] Two media files are missing in the local v8 copy (an event image and a team image, see the plan, task 10): check them against production in the media rsync of the switch.
- [ ] Any content changed in v10 before go-live: record it in `v8-overrides.php`, or the final sync overwrites it.

## Switch

**Approach (user, 2026-11-19): the staging app becomes production.** Staging and production are always on the same Cloudways server. The v10 staging app (dev.seerdencommunicatie.nl) gets the production domains in the Cloudways dashboard; the v8 app keeps its files and database as the fallback. DNS already points to this server, so no DNS change is needed and the SSL certificate can be issued right after the domain move. Claude guides the switch step by step (runbook step 18, Deploy lessons).

Before the day:

- [ ] Production mail settings for the v10 app's `.env`: `MAIL_*` (SMTP) and `MAIL_FROM_*`; form mail goes to `company_email` = `ellen@seerdencommunicatie.nl`. If mail goes out through the server (not an SMTP provider): SPF/DKIM of `seerdencommunicatie.nl` must allow it.
- [ ] reCAPTCHA key allows `www.seerdencommunicatie.nl` and `seerdencommunicatie.nl` (not only the dev domain).
- [ ] `.env` of the v10 app: no `SESSION_DOMAIN` with the dev domain (empty, or the production domain), or admin logins fail after the swap.
- [ ] Cloudways backups for the v10 app at a production schedule.
- [ ] Decision: the module pages in no published menu in the sitemap (`/nl/nieuws`, `/nl/video`, `/nl/fotoalbums`, `/nl/project`, `/nl/aanbevelingen`, `/nl/instagram`).
- [ ] Note: staging already sends to the live GA4 property (`G-0B6CD217XK`); test visits count in the statistics.

The switch:

- [ ] Content freeze on the v8 admin.
- [ ] Final production dump from the v8 app (before moving the domain) → `v8_seerden`; rsync of the production media into `seerden8246/.lara/production_switch/media`; `LARA_V8_MEDIA_PATH` to that folder.
- [ ] Backups first: `v10_seerden` and `v8_seerden` into `seerden-deploy/backups/` (`seerden10723_before_final_sync.sql`, `v8_seerden_before_final_sync.sql`).
- [ ] Locally: `lara:v8:sync --dry-run`, `lara:v8:sync`, `lara:v8:verify -v`; then a fresh dump and media (Python archive) in `seerden-deploy/`.
- [ ] Server (v10 app): load the dump and media as in staging (this also removes the staging test edits and test form entries); NFC check of non-ASCII file names; clear the sessions.
- [ ] Server: `robots.txt` back to the repo version (`git checkout public/robots.txt` if it was edited on the server; a dirty tracked file blocks the next pull).
- [ ] Server `.env`: `APP_URL=https://www.seerdencommunicatie.nl`, `APP_ENV=production`, the production `MAIL_*`/`MAIL_FROM_*`; then `rm -rf storage/app/public/_fonts && php artisan google-fonts:fetch` (fonts.css holds the dev URL), `php artisan optimize:clear && php artisan responsecache:clear && php artisan lara:route:cache`, the sitemap again.
- [ ] v8 app: switch off its cron jobs and outgoing mail; remove its domains and give it a Cloudways temporary URL (the fallback).
- [ ] v10 app: primary domain `www.seerdencommunicatie.nl`, alias `seerdencommunicatie.nl` (redirect to `www`, as live); remove `dev.seerdencommunicatie.nl` or redirect it to `www` (one `robots.txt` serves every domain of the app, so a remaining dev domain would be an indexable copy); issue the SSL certificate for both domains.
- [ ] Check: the non-www → www redirect (301), home (hero, client logos, contact band), every menu item (Evenementen with its 4 sub-pages and their project tiles and related pages, Campagnes, Over mij, Contact, Privacy, Instagram feed), a project page, a testimonial, the 404, every asset and image (incl. `/storage` editor images, fonts, `/assets/filament/img`), both contact forms with reCAPTCHA and the mail (`company_email` and the confirmation), the cookie consent, `robots.txt` (allows), `sitemap.xml` (www URLs), the admin login.
- [ ] Search Console: the sitemap; watch 404s. v8 had no redirects.

## Fallback

- [ ] The v8 app stays on the server with its files and database, without domains, on its Cloudways temporary URL, read-only (crons and mail off) for a few weeks. Switching back: move the domains back and issue SSL again; content edited in v10 after the switch would have to be redone in v8.

## Known differences that stay

- Unknown URLs answer 404 with the "Oops" page (live: 200).
- `/en` is unpublished (live: an almost empty English home); v10 still answers `/en` with 200 (core: an unpublished language stays routable), but nothing links to it and there is no English hreflang.
- Page titles end in " — Seerden Communicatie"; project detail pages have the project's title (live: the module page's SEO title).
- The contact form marks required fields ("* = dit is een verplicht veld"); v8 did not.
- `/nl/evenementen` redirects to `/evenementen/sportevenementen` without the locale (as v8).
- Related pages link to `/nl/content/pages/{id}`, which redirects to the menu URL (core; v8 linked the menu URL).
- Small styling: the Facebook icon (SVG "f" instead of the round logo), input fields 4 px lower, the CTA button 8 px narrower.
