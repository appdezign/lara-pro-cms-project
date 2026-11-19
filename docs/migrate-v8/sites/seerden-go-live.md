# seerdencommunicatie.nl: go-live checklist

v8.2.46 (`seerden8246`, production db `xxqektecpc`, theme `seerden`) → v10 (`seerden10723`, Lara CMS 10.7.23, theme `seerden`). General steps: runbook step 18. Status 2026-11-18: import, theme and the comparison with live done locally; next: staging on https://dev.seerdencommunicatie.nl.

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
tar -czf ../seerden-deploy/seerden10723-media.tgz --exclude public/_fonts -C storage/app public
```

The deploy files live in **`__migrate/seerden-deploy/`** (as `anpak-deploy`): `seerden10723.sql` (the v10 database), `seerden10723-media.tgz` (`storage/app/public` without `_fonts`) and `backups/` (dumps before the final sync). The built assets are in git, so there is no assets archive. The dump holds user password hashes: never commit it; delete it from the server after loading. Staging files made 2026-11-19 10:44 (verify ok 36).

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
- [ ] Compare with live and with `seerden10723` (scratchpad script of task 15: every menu URL, the 7 projects, the 4 testimonials, the unknown URL; desktop and mobile).
- [ ] Forms: `/nl/contact` and `/nl/contact-1` (both contact forms): submit, mail on the dev mailer; mail views are in `email/contactforms` and `email/classicforms`.
- [ ] Admin: log in as an imported v8 user, open every content type, edit and save one record; the 5 event pages show their related pages in the admin.

## Content to settle before the freeze

- [ ] Two media files are missing in the local v8 copy (an event image and a team image, see the plan, task 10): check them against production in the media rsync of the switch.
- [ ] Any content changed in v10 before go-live: record it in `v8-overrides.php`, or the final sync overwrites it.

## Switch

- [ ] **Remind the manual server steps again** if production is another server than staging (PHP settings, GA service-account file).
- [ ] Content freeze on the v8 admin.
- [ ] Fresh production dump → `v8_seerden`; rsync of the production media into `seerden8246/.lara/production_switch/media`; `LARA_V8_MEDIA_PATH` to that folder.
- [ ] Backups first: `v10_seerden` and `v8_seerden` into `seerden-deploy/backups/` (`seerden10723_before_final_sync.sql`, `v8_seerden_before_final_sync.sql`).
- [ ] `lara:v8:sync --dry-run`, `lara:v8:sync`, `lara:v8:verify -v`; then a fresh dump and media in `seerden-deploy/` and to the server as in staging.
- [ ] `APP_URL=https://www.seerdencommunicatie.nl`, `APP_ENV=production`; `rm -rf storage/app/public/_fonts && php artisan google-fonts:fetch`; `optimize:clear`, `responsecache:clear`, `lara:route:cache`; the sitemap again.
- [ ] DNS / vhost to v10; SSL for `seerdencommunicatie.nl` and `www.seerdencommunicatie.nl` (keep the redirect to `www`).
- [ ] Check: home (hero, client logos, contact band), every menu item (Evenementen with its 4 sub-pages and their project tiles and related pages, Campagnes, Over mij, Contact, Privacy, Instagram feed), a project page, a testimonial, the 404, both contact forms with reCAPTCHA and the mail (`company_email` and the confirmation), the cookie consent, `robots.txt`, `sitemap.xml`.
- [ ] Search Console: the sitemap; watch 404s. v8 had no redirects.

## Fallback

- [ ] Keep the v8 site (`seerden8246`) and its database read-only on another vhost for a few weeks.

## Known differences that stay

- Unknown URLs answer 404 with the "Oops" page (live: 200).
- `/en` is unpublished (live: an almost empty English home); v10 still answers `/en` with 200 (core: an unpublished language stays routable), but nothing links to it and there is no English hreflang.
- Page titles end in " — Seerden Communicatie"; project detail pages have the project's title (live: the module page's SEO title).
- The contact form marks required fields ("* = dit is een verplicht veld"); v8 did not.
- `/nl/evenementen` redirects to `/evenementen/sportevenementen` without the locale (as v8).
- Related pages link to `/nl/content/pages/{id}`, which redirects to the menu URL (core; v8 linked the menu URL).
- Small styling: the Facebook icon (SVG "f" instead of the round logo), input fields 4 px lower, the CTA button 8 px narrower.
