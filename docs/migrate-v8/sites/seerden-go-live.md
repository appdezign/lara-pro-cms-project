# seerdencommunicatie.nl: go-live checklist

v8.2.46 (`seerden8246`, production db `xxqektecpc`, theme `seerden`) → v10 (`seerden10723`, Lara CMS 10.7.23, theme `seerden`). General steps: runbook step 18. Status 2026-11-19: staging https://dev.seerdencommunicatie.nl deployed and checked; next: the switch.

Live: https://www.seerdencommunicatie.nl (`seerdencommunicatie.nl` redirects 301 to `www`). Plan with every decision: `seerden8246/.ai/plans/2026-11-18-migrate-v8-to-v10.md`; inventory: `sites/seerden-inventory.md`.

## Repositories

- **`appdezign/seerdencommunicatie-nl`, branch `v10`** (pushed 2026-11-18, `38fe328`; since the go-live on 2026-11-20 `main` and `develop` at `9b01a4e`, tag `v1.0.0`, and Cloudways deploys `main`): the site. The project at 10.7.23, the migration tooling, the generated `laracms/app` (13 site content types), `laracms/themes/seerden`, `lang/vendor`, `v8-overrides.php` (5 overrides), `THEME_NAME = 'seerden'`, Manrope (`@googlefonts('manrope')`, already in `config/google-fonts.php`), and the built assets (`public/assets/admin`, `public/assets/themes/{base,seerden}`; see runbook "Built assets in git"). Not in git: `storage/app/public` (media) and `public/storage` (run `storage:link`). The repo has no `main`/`develop` yet: create them from `v10` when the release flow starts.
- **`appdezign/lara-pro-cms-project`, branch `v8-migration`**: the tooling and the runbook. Tooling changes go there first (worktree `__migrate/seerden10723-tooling`), then `git merge v8-migration` into `v10`.
- Locally `seerden10723` stays on `v10`; `../seerden10723-tooling` is a git worktree of `v8-migration`.
- After go-live: make the site repo the only remote (runbook gotcha "After go-live, make the site repo the only remote").

## Open before staging

- [x] **Staging** (user, 2026-11-18): https://dev.seerdencommunicatie.nl (on 2026-11-18 still a placeholder page, nginx).
- [x] **Keys** (user, 2026-11-18): the Google Maps (`GOOGLE_MAPS_API_KEY`, `GOOGLE_MAPS_GEOCODING_API_KEY`), reCAPTCHA (`GOOGLE_RECAPTCHA_SITE_KEY`, `GOOGLE_RECAPTCHA_SECRET_KEY`) and GA4 keys (`CONSENT_GA4_ID`; setting `ga4_id` `G-0B6CD217XK`) in the local `.env` are seerden's own: copy them to each server's `.env`.
- [x] **Mail**: the production `MAIL_*` settings (form mail goes to `company_email` = `ellen@seerdencommunicatie.nl`) and `MAIL_FROM_*`; `MAIL_DEV_*` for staging. Done: AuthSMTP as v8 (see Switch, before the day).
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

# 3. data (SFTP seerden-deploy/seerden10723.sql and seerden10723-media.tgz to public_html/storage/app/ first;
#    the application user cannot write to ~, and storage/app is not served)
mysql {db} < storage/app/seerden10723.sql
rm -rf storage/app/public && tar -xzf storage/app/seerden10723-media.tgz -C storage/app
rm storage/app/seerden10723.sql storage/app/seerden10723-media.tgz   # the dump holds user password hashes
php artisan storage:link                          # public/storage is untracked since 9b01a4e
php artisan google-fonts:fetch                    # after APP_URL is set (the stylesheet holds absolute URLs)
php artisan migrate:status | grep -i pending      # expected: nothing

# 4. caches (and old sessions if deployed over an earlier install)
php artisan optimize:clear && php artisan responsecache:clear && php artisan lara:route:cache

# 5. scheduler (Cloudways: Cron Job Management):  * * * * * cd {app root} && php artisan schedule:run >> /dev/null 2>&1

# 6. the sitemap once
php artisan schedule:test --name=google-sitemap  # not tinker: it fails without a writable home folder
```

Checks:

- [x] Server: PHP 8.4 with `tmpfile`, `highlight_file`, `fpassthru`; GA service-account file; scheduler cron. GA file and cron confirmed on the v10 app (user, 2026-11-20).
- [x] Code on `v10`, `.env` complete, data and media loaded, `storage:link`, fonts fetched. Done (2026-11-19; since 2026-11-20 on `main`).
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
- [x] The sitemap listed `/nl/nieuws`, `/nl/video`, `/nl/fotoalbums`, `/nl/project`, `/nl/aanbevelingen` and `/nl/instagram` (reachable, linked nowhere): left out (see Before the day).
- [x] Staging indexing blocked by the user: `robots.txt` is `Disallow: /` (2026-11-19).
- [x] Admin images (`/assets/filament/img`: login background, logo, admin favicon) were missing: the `.gitignore` ignored all of `public/assets/filament/`, but `img` comes from the admin Vite build. Tracked now (`4194977` on `v10`, pushed): pulled by the user; the 4 images and all 17 asset URLs of `/admin/login` 200 (2026-11-19).
- [x] Forms: the user submitted `/nl/contact` and `/nl/contact-1` on staging; both mails arrived in Mailtrap (dev mailer, 2026-11-19). Production mail (`MAIL_*`, `MAIL_FROM_*`; to `company_email`) and reCAPTCHA are checked right after the switch.
- [x] Admin: the user logged in on staging and saved some records (2026-11-19); afterwards the 27 front URLs are still identical to `seerden10723`. Edits made on staging are lost at the switch (the staging database is replaced by the synced local one).

## Content to settle before the freeze

- [x] Two media files are missing in the local v8 copy (an event image and a team image, see the plan, task 10): check them against production in the media rsync of the switch. Resolved 2026-11-20: they are missing on production too (the production copy is identical): a v8 data issue.
- [x] Any content changed in v10 before go-live: record it in `v8-overrides.php`, or the final sync overwrites it. None (staging edits were test edits and are replaced at the switch).

## Switch

**Approach (user, 2026-11-19): the staging app becomes production.** Staging and production are always on the same Cloudways server. The v10 staging app (dev.seerdencommunicatie.nl) gets the production domains in the Cloudways dashboard; the v8 app keeps its files and database as the fallback. DNS already points to this server, so no DNS change is needed and the SSL certificate can be issued right after the domain move. Claude guides the switch step by step (runbook step 18, Deploy lessons).

Before the day:

- [x] Production mail (user, 2026-11-19): AuthSMTP as the v8 app (`MAIL_MAILER=smtp`, `MAIL_HOST=mail.authsmtp.com`, `MAIL_PORT=25`, `MAIL_ENCRYPTION=null`, the v8 username and password), `MAIL_FROM_ADDRESS=ellen@seerdencommunicatie.nl`, `MAIL_FROM_NAME="Seerden Communicatie"`. SPF already includes `authsmtp.com`. Active once `APP_ENV=production`.
- [x] reCAPTCHA (2026-11-19): the local `.env` had empty keys; the v8 app's v2 checkbox keys (`GOOGLE_RECAPTCHA_SITE_KEY`/`SECRET_KEY`, live on `www`) are copied into the v10 app's `.env` (user). The widget renders only with `APP_ENV=production`: check it right after the switch.
- [x] `.env` of the v10 app: `SESSION_DOMAIN=null` (checked 2026-11-19). `SESSION_DRIVER=file`: clearing the sessions is `rm -f storage/framework/sessions/*`.
- [x] Cloudways backups: daily, 7 days retention, the v10 app covered (user, 2026-11-19).
- [x] Sitemap (user, 2026-11-19): the 6 unlinked module pages are left out (`f71e070` on `v10`: unpublished entity menu items and route `instagram` skipped; privacy and aanspreekpunt stay). 11 URLs. Pull before the switch; the sitemap is generated again at the switch.
- [x] Note: staging already sends to the live GA4 property (`G-0B6CD217XK`); test visits count in the statistics.

The switch:

- [x] Content freeze on the v8 admin (2026-11-20).
- [x] Final production dump from the v8 app (before moving the domain) → `v8_seerden` (`seerden_prod_20112026.sql`, 10:10); the production media copied with SFTP into `seerden8246/.lara/production_switch/media` (199 files, identical to the earlier copy); `LARA_V8_MEDIA_PATH` to that folder.
- [x] Backups first: `v10_seerden` and `v8_seerden` into `seerden-deploy/backups/` (`seerden10723_before_final_sync.sql`, `v8_seerden_before_final_sync.sql`). Done 2026-11-20 as `{v10,v8}_seerden-20261120-pre-sync.sql`.
- [x] Locally: `lara:v8:sync --dry-run`, `lara:v8:sync`, `lara:v8:verify -v`; then a fresh dump and media (Python archive) in `seerden-deploy/`. Done 2026-11-20: dry run only known warnings; sync: every step `same` (the 19-11 production edits only touched `updated_at`), 5 overrides, verify ok 36; `seerden-deploy/seerden10723.sql` + `seerden10723-media.tgz` (Python, 154 files, NFC); staging versions in `backups/`.
- [x] Server (v10 app): load the dump and media as in staging (this also removes the staging test edits and test form entries); NFC check of non-ASCII file names; clear the sessions. Done 2026-11-20: uploaded to `storage/app/` (no write access to `~`); 154 files, 0 non-NFC or `._*` names; sessions cleared. `/storage` was broken by the restored `public/storage` symlink: relinked, and untracked in the repo (`9b01a4e`).
- [x] Server: `robots.txt` back to the repo version: a Cloudways dashboard pull (Deployment via Git) restores it, as it discards server edits to tracked files (there is no git on the server). Until the switch, block it again after every pull. Done 2026-11-20 (pull of `9b01a4e`, then `storage:link`).
- [x] Server `.env`: `APP_URL=https://www.seerdencommunicatie.nl`, `APP_ENV=production`, the production `MAIL_*`/`MAIL_FROM_*`; then `rm -rf storage/app/public/_fonts && php artisan google-fonts:fetch` (fonts.css holds the dev URL), `php artisan optimize:clear && php artisan responsecache:clear && php artisan lara:route:cache`, the sitemap again. Done 2026-11-20; the sitemap with `schedule:test --name=google-sitemap` (tinker wrote nothing): 11 `www` URLs.
- [x] v8 app: switch off its cron jobs and outgoing mail; remove its domains and give it a Cloudways temporary URL (the fallback). Done 2026-11-20 (crons off, `MAIL_MAILER=log`, domains removed). Note: a cache command ran in its shell by mistake (only its caches cleared): rebuild its caches before using the fallback.
- [x] v10 app: primary domain `www.seerdencommunicatie.nl`, alias `seerdencommunicatie.nl` (redirect to `www`, as live); remove `dev.seerdencommunicatie.nl` or redirect it to `www` (one `robots.txt` serves every domain of the app, so a remaining dev domain would be an indexable copy); issue the SSL certificate for both domains. Done 2026-11-20: `www` primary, `seerdencommunicatie.nl` alias, `dev.` removed (404), Let's Encrypt for both. The Cloudways www redirect drops the path (every non-www URL → www home) and does not cover `/storage` files: kept (user).
- [x] Check: the non-www → www redirect (301), home (hero, client logos, contact band), every menu item (Evenementen with its 4 sub-pages and their project tiles and related pages, Campagnes, Over mij, Contact, Privacy, Instagram feed), a project page, a testimonial, the 404, every asset and image (incl. `/storage` editor images, fonts, `/assets/filament/img`), both contact forms with reCAPTCHA and the mail (`company_email` and the confirmation), the cookie consent, `robots.txt` (allows), `sitemap.xml` (www URLs), the admin login. Done 2026-11-20: http → https and non-www → www 301; 16 URLs identical to the local site; 47 asset URLs 200 (`/storage`, fonts, `/assets/filament/img`); both contact forms with reCAPTCHA, mail to the company address and the confirmation (user); consent with GA4; `robots.txt` allows; sitemap 11 `www` URLs; admin login (user). Deploy branch switched to `main` (tag `v1.0.0`) and rechecked.
- [x] Search Console: the sitemap; watch 404s. v8 had no redirects. Sitemap submitted 2026-11-20 (user); watching 404s is ongoing.

## Fallback

- [x] The v8 app stays on the server with its files and database, without domains, on its Cloudways temporary URL, read-only (crons and mail off) for a few weeks. Switching back: move the domains back and issue SSL again; content edited in v10 after the switch would have to be redone in v8. In place since 2026-11-20.

## Known differences that stay

- Unknown URLs answer 404 with the "Oops" page (live: 200).
- `/en` is unpublished (live: an almost empty English home); v10 still answers `/en` with 200 (core: an unpublished language stays routable), but nothing links to it and there is no English hreflang.
- Page titles end in " — Seerden Communicatie"; project detail pages have the project's title (live: the module page's SEO title).
- The contact form marks required fields ("* = dit is een verplicht veld"); v8 did not.
- `/nl/evenementen` redirects to `/evenementen/sportevenementen` without the locale (as v8).
- Related pages link to `/nl/content/pages/{id}`, which redirects to the menu URL (core; v8 linked the menu URL).
- Small styling: the Facebook icon (SVG "f" instead of the round logo), input fields 4 px lower, the CTA button 8 px narrower.

## After go-live

- [x] **Update to Lara CMS 10.7.24** (found on macgyverbouw, 2026-11-24; done with 1.0.2 on 10.7.25, 2026-11-24): before 10.7.24 the cached widgets (`sliderWidget` here) were cached for a day without the language; seerden's `/en` answers 200, so one English request can leave the Dutch home without its slider. `composer update` of the appdezign packages (merge project `main` into the site branch), build, deploy, `php artisan cache:clear`.
- [x] **Form mail pages** (found on macgyverbouw, 2026-11-24; done 2026-11-24 into `zsxbaxqhzb`: page 38 `contactforms-email-nl`, mails headed "Contact form Inquiry"): import `seerden-deploy/email-pages-v10-slug.sql` with TablePlus into the live v10 database (guarded: removes the empty "… Email Title" page v10 created, gives the v8 mail pages the slug v10 looks up), then `optimize:clear` and `responsecache:clear`.

## Updates

- **1.0.2, Lara CMS 10.7.25 (2026-11-24):** the form spinner (base 10.7.25, Bootstrap Icons), the language-aware widget cache (10.7.24), `RedirectToCanonicalHost` from the project with its test. Cloudways' www redirect (which dropped the path) is off; the middleware sends `seerdencommunicatie.nl/<path>?<query>` to the same path on www. Sitemap unchanged (seerden's generator already was the 10.7.24 version plus its rule). Release `v1.0.2` (`main` `1a4eee6`); deployed (manual backup, Pull, `composer install`, 0 pending migrations, `email-pages-v10-slug.sql` into `zsxbaxqhzb`, caches, sitemap). Checked: 20 URLs identical to the snapshot before the update; the www redirect keeps path and query; the spinner and both mails with "Contact form Inquiry" (user). **Open (found, not changed):** the sitemap lists `/evenementen/sportevenementen` without the language (302 to `/nl/evenementen`), already before the update. Plan: `__migrate/_notes/2026-11-24-update-migrated-sites-10-7-25.md`.
