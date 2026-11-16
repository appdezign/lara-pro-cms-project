# anpak.eu: go-live checklist

v8.2.46 (`anpak8246`, production db `zhgkvpwmcx`) → v10 (`anpak1077`, Lara CMS 10.7.20). General steps: runbook step 18. Status 2026-11-16: everything up to staging is done locally; the items marked **open** need the user.

## Before (staging)

- [ ] Server: the Cloudways app of the earlier v10 attempt, **https://dev.anpak.eu** (user, 2026-11-16): check PHP 8.4, its database and the scheduler cron (`schedule:run`: Google sitemap daily, media sync hourly, Google Analytics).
- [ ] Site code in the site repository **`appdezign/anpak-eu`** (user, 2026-11-16; the repo of the earlier `anpak10` attempt): the generated `laracms/app` (19 content types), `laracms/themes/anpak`, `v8-overrides.php` (4 overrides), `public/flyer`, `config/google-fonts.php` (Lato), `config/filarank.php` (title separator ` | `), `vite.front.client.config.js` (`THEME_NAME = 'anpak'`).
- [ ] `.env`: `APP_DEBUG=false`, `LARA_CLIENT_THEME=anpak`, mail (`MAIL_DEV_*` for staging, `MAIL_*` smtp for production), the **Google Maps, reCAPTCHA and GA4 keys from the local `.env`** (they are anpak's live keys), `LARA_V8_DB_*` (read-only user), `LARA_V8_MEDIA_PATH`, `LARA_V8_SITE_HOSTS=anpak.eu`.
- [ ] Build: `composer install --no-dev -o`, `npm ci`, admin and client builds, `php artisan storage:link` (the repo has `public/storage` committed as a symlink to another machine's path).
- [ ] Data: fresh production dump + media rsync, `lara:v8:sync`, `lara:v8:verify` → expected: no errors, no warnings.
- [ ] Forms: contact (`/nl/contact`, `/nl/contact-1`) and the classic form; mail goes to `company_email` (an anpak.eu address) in production, to the dev mailer on staging. The applications form is not used (menu item unpublished in v8).
- [ ] `/flyer/` and `/flyer/?page=1` (static folder in `public/`; locally Herd redirects them to `/nl/flyer`, Apache should serve `index.html`).
- [ ] Compare with live (task 22 scripts in the scratchpad: `compare.php`, `headings.php`; 58 pages).

## Content to settle before the freeze

- [x] News #517 linked to `https://www.anpak.eu/nl/diensten/documenten` (old URL): replaced by `/nl/diensten/downloads` with a `replace` override in `v8-overrides.php` (user decision, 2026-11-16). Verify: no warnings.
- [ ] Any other content changed in v10 before go-live: record it in `v8-overrides.php`, or the final sync overwrites it.

## Switch

- [ ] Content freeze on the v8 admin.
- [ ] Fresh production dump + media rsync → `lara:v8:sync --dry-run`, `lara:v8:sync`, `lara:v8:verify`.
- [ ] `php artisan optimize:clear && php artisan lara:route:cache`.
- [ ] DNS / vhost to v10; SSL for `anpak.eu` and `www.anpak.eu`.
- [ ] Check: home (slider, 4 services, testimonials, clients, contact band, news), the menu items, a news item, a document download, a team member, the ATEX services, contact form + mail + reCAPTCHA, cookie consent, `robots.txt`, `sitemap.xml`, `/en` (English is unpublished; live gives a 500 today).
- [ ] Search Console: sitemap; watch 404s. Known: v8 had no redirects; v8 service detail URLs (`/nl/diensten/arbeidshygiene/{slug}.html`) keep working in v10.

## Fallback

- [ ] Keep the v8 site (`anpak8246`) and its database read-only on another vhost for a few weeks.

## Known differences that stay

- Unknown URLs answer with the home page and status 200 (core; same as v8).
- The page-title photo is random per page load (as v8).
- The job detail page redirects to `/nl/404` (live: to the list); neither site links to it.
