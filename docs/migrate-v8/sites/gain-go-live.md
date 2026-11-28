# vereniginggain.nl: go-live checklist

v8.2.46 (`gain8246`, production db `yrmfjkvtpn`, theme `gain`) → v10 (`gain10729`, Lara CMS 10.7.33, theme `gain`). General steps: runbook step 18 (with its Deploy lessons). Status 2026-11-28: **live on v10** (the former staging app, database `ekbwfwgebg`); the v8 app is the fallback.

Live: https://www.vereniginggain.nl on the Cloudways server at `95.179.176.155` (the same server as hwn, macgyverbouw, doormode and inventiverepair), nginx, **not behind Cloudflare**. DNS at webhost.company (`ns1–3.webhost.company`); the root `95.179.176.155`, `www` a CNAME to the root; **`v10.vereniginggain.nl` already resolves** (CNAME to the root) and answers **403** (an app without an index yet, 2026-11-27). `http://www` → `https://www` 301 with the path; `vereniginggain.nl/*` (http and https) → `https://www.vereniginggain.nl/` 301 **without the path** (the query is kept: Cloudways' own www redirect, as hwn). `/` → `/nl` 302. `robots.txt` `Allow: /`; **no sitemap** on live (`/sitemap.xml` 302 to `/nl/sitemap.xml`, which serves the home page with 200). Plan with every decision: `gain8246/.ai/plans/2026-11-27-migrate-v8-to-v10-gain.md`; inventory: `sites/gain-inventory.md`; text pass: `gain8246/.ai/plans/gain-compare/`.

Mail: `vereniginggain.nl` MX Microsoft 365 (`vereniginggain-nl.mail.protection.outlook.com`), SPF `v=spf1 include:spf.protection.outlook.com include:authsmtp.com include:servers.mcsv.net ~all`, DMARC `p=none` (checked 2026-11-27).

## Repositories

- **`appdezign/vereniginggain-nl`, branch `v10`** (pushed 2026-11-27, `15413de`): the site. The project at 10.7.30, the migration tooling, the generated `laracms/app` (site content types: activities, blog, doc, docpro, event, experience, faq, gallery, interview, location, organizer, portfolio, step, testimonial, video; classicform, contactform, member, product, sponsor and team skipped), the event duplicate (site code), `laracms/themes/gain`, `lang/vendor`, `v8-fields.php` (interview paragraphs, the page lead as the field `intro`, docpro secretkey left out), `v8-overrides.php` (footer privacy link, English unpublished, the demo page Services and the test page Slider Test trashed), `config/consent.php` `policy_url` `/nl/privacy`, `THEME_NAME = 'gain'`, Google font `fira-sans`, and the built assets (`public/assets/admin`, `public/assets/themes/{base,gain}`, `public/assets/filament/img`; runbook "Built assets in git"). Not in git: `storage/app/public` (media), `public/storage` (run `storage:link`), `public/sitemap.xml` (generated). No `main`/`develop` yet: create them from `v10` at the go-live.
- **`appdezign/lara-pro-cms-project`, branch `v8-migration`**: the tooling and the runbook. Tooling changes go there first (worktree `__migrate/gain10729-tooling`), then merged into `v10`.
- After go-live: make the site repo the only remote, `main`/`develop`, a version tag; remove the project tags and the tooling worktree once everything is pushed (runbook gotcha "After go-live, make the site repo the only remote").

## Open before staging

- [x] **Staging app and database** (user, 2026-11-27): **https://v10.vereniginggain.nl** (DNS exists; the app answers 403). A v10 Cloudways app on the same server (it becomes production at the switch). Database **`ekbwfwgebg`**. PhpStorm SFTP project: at task 16.
- [x] **Keys**: gain has **no contact form and no map** (contactform and classicform skipped; the contact page is text plus the newsletter form, as live). The v8 `.env` (`__cloudways/vereniginggain.nl_cloudways/.env`) holds Google Maps, reCAPTCHA and Translate keys: not needed by the theme; take them over anyway so the admin has the same services (as hwn). Newsletter: the Mailchimp form posts straight to `vereniginggain.us4.list-manage.com` (no key). GA4: `CONSENT_GA4_ID=G-16JG8PMMDE` (v8 setting `ga4_id`); staging then counts in the live property. Dashboard: `ANALYTICS_PROPERTY_ID=458757485` (from the local v8 copy; **not in the server's v8 `.env`**) with the service-account file.
- [x] **Mail** (user, 2026-11-27: sender as proposed): no form, so only system mails (password reset, notifications). Production: AuthSMTP as the v8 app (`MAIL_MAILER=smtp`, `MAIL_HOST=mail.authsmtp.com`, `MAIL_PORT=25`, `MAIL_ENCRYPTION=null`, username/password from the v8 `.env`; SPF includes `authsmtp.com`). Sender: `MAIL_FROM_ADDRESS=info@vereniginggain.nl` (= `company_email`), `MAIL_FROM_NAME="Vereniging GAIN"` (v8 has no `MAIL_FROM_*`). Staging: dev mailer.
- [x] **`APP_NAME="Vereniging GAIN"`** (user, task 6): page title suffix and `og:site_name`. On the server with the quotes (Deploy lessons).
- [x] **www redirect**: `RedirectToCanonicalHost` (project) answers `vereniginggain.nl/<path>?<query>` with a 301 to `APP_URL` + path + query, only with `APP_ENV=production` (live drops the path). Check after the switch.
- [x] **Sitemap** (user, 2026-11-27: trash slider-test): live has none; v10 generates one (438 URLs locally: news 162, reports 70, events 63, …). It includes **`/nl/slider-test`** (page 120 "Slider Test": page and menu item unpublished, but v10 shows a page a menu links, and live answers it with 200 too). Trashed as Services (site override, page 120: now 404); the generator now leaves out a page item whose page is trashed (`v8-migration`), so v10 lists 437 URLs. The other unpublished main-menu items stay listed: v10 shows those pages (200). Submit the sitemap in Search Console after the switch (user).
- [x] **Cookie banner privacy link** (user, 2026-11-27): v8's banner links to `/nl/privacy` (200 on live and v10, page 71, a menu-linked page). v10 `config/consent.php` `ui.policy_url` was `null`; now `'/nl/privacy'`, as hwn.

## Staging

**Manual server steps first** (they are in no repo; remind the user again before the first deploy, plan task 16):

- Cloudways PHP **8.4**, and enable **`tmpfile`, `highlight_file` and `fpassthru`** (Application Settings → PHP: remove them from `disable_functions`).
- Upload the Google Analytics service-account file by hand to **`storage/app/analytics/service-account-lara-dash.json`**; without it the admin dashboard errors on the first deploy.

Locally (in `gain10729`, on `v10`):

```bash
php artisan lara:v8:verify -v                    # ok 34, warning 18 (17 dead links left as they are + one published page fewer: Services trashed; Slider Test was unpublished in v8)
mysqldump -h127.0.0.1 -uroot --single-transaction --routines v10_gain > ../gain-deploy/gain10729.sql
python3 -c 'import tarfile; t = tarfile.open("../gain-deploy/gain10729-media.tgz", "w:gz"); t.add("storage/app/public", arcname="public", filter=lambda i: None if i.name == "public/_fonts" or i.name.startswith("public/_fonts/") else i); t.close()'
# not macOS tar: NFD file names and ._* files (seerden)
```

The deploy files live in **`__migrate/gain-deploy/`**: `gain10729.sql` (the v10 database), `gain10729-media.tgz` (`storage/app/public` without `_fonts`; about 650 MB, 508 files), `staging.env` (secrets as `{...}` placeholders) and `backups/` (dumps before the final sync). The dump holds user password hashes: never in a repo or the home folder.

On the server, guided one step at a time (runbook step 18: one command or setting, wait for the result, check it from here):

1. Code: Cloudways "Deployment via Git", repo `appdezign/vereniginggain-nl`, branch `v10`, Pull (it runs no composer, build or artisan). `php -v` (8.4), `composer install --no-dev -o`.
2. `.env` (keep the server's DB settings; `key:generate`): `APP_NAME="Vereniging GAIN"`, `APP_ENV=staging`, `APP_DEBUG=false`, `APP_URL=https://v10.vereniginggain.nl`, `LARA_CLIENT_THEME=gain`, `MAIL_DEV_*`, the keys above, `CONSENT_GA4_ID=G-16JG8PMMDE`, `ANALYTICS_PROPERTY_ID=458757485`; no `LARA_V8_*`. Template: `gain-deploy/staging.env`.
3. Database: the user imports `gain-deploy/gain10729.sql` into the app's database with **TablePlus** (name the database and the file at every step; check a page from here right after).
4. Media: the user uploads `gain10729-media.tgz` with **SFTP in PhpStorm** to `public_html/storage/app/`, then `rm -rf storage/app/public && tar -xzf storage/app/gain10729-media.tgz -C storage/app` and removes the archive; check the file count against the archive, no `._*`, names NFC.
5. `rm -f public/storage && php artisan storage:link` (check `ls -l public/storage`), `php artisan google-fonts:fetch` (after `APP_URL`; Fira Sans), `php artisan migrate:status | grep -i pending` (expected: nothing).
6. Caches: `php artisan optimize:clear && php artisan responsecache:clear && php artisan lara:route:cache` (after every database import).
7. Scheduler (Cloudways Cron Job Management): `* * * * * cd {app root} && php artisan schedule:run >> /dev/null 2>&1`.
8. Sitemap once: `php artisan schedule:test --name=google-sitemap`; `ls -l public/sitemap.xml`; every URL on the staging host.
9. Block indexing of staging (`robots.txt` `Disallow: /`; a dashboard pull restores the repo `robots.txt`, so block again after each pull, with `printf`).
10. GA service-account file (manual step above); the admin dashboard.
11. Front: the newsletter popup and form (posts to Mailchimp: do not submit a real address), search, the event popups, the interview paragraphs, documents downloads.
12. Admin: open and save a page (with its `intro`), a news item, an interview, an event (and the event duplicate action) and a document; the front URLs still identical afterwards.

Checks:

- [x] Server: PHP 8.4 with `tmpfile`, `highlight_file`, `fpassthru`; GA service-account file, admin dashboard; scheduler cron (2026-11-27).
- [x] Every URL of the text pass (`gain-compare/urls.txt`, 92 URLs) identical to `gain10729`; Glide images 200; `/admin/login` 200; no local or `http://` references.
- [x] The fixed header and full-screen menu; the sidebar menu (desktop and mobile); the home quote slider.

## Switch

(As hwn, runbook step 18 "Switch".) Final sync of the content changed on live since the dump (`lara:v8:sync` against a fresh production dump; backups first in `gain-deploy/backups/`), the new `gain10729.sql` via TablePlus, caches. In Cloudways move the domains `vereniginggain.nl` and `www.vereniginggain.nl` from the v8 app (`yrmfjkvtpn`) to the v10 app (no DNS change), with SSL; `.env`: `APP_ENV=production`, `APP_URL=https://www.vereniginggain.nl`, the AuthSMTP mailer and `MAIL_FROM_*`; caches, sitemap again; `robots.txt` from the repo (`Allow: /`). The v8 app stays as the fallback: no domains, crons off, `MAIL_MAILER=log`.

After the switch: the www redirect with the path, `/` → `/nl`, a news item, an event popup, `/admin/login`, the sitemap on the live host, Search Console (user).

**Done (2026-11-28, guided):** final dump `gain8246-final.sql` (16:08) compared per column with the snapshot: only `updated_at` of records opened in the v8 admin, a `last_login` and v8's media-sync timestamps, so **no final sync** (the staging database became production as it was; no media copied again). Content freeze; v10 `.env` production values (`APP_ENV=production`, `APP_URL` www, AuthSMTP with `MAIL_ENCRYPTION=null`); v8 app `MAIL_MAILER=log` + `config:clear`, crons removed, domains removed (temporary URL); v10 app: primary `www.vereniginggain.nl`, alias `vereniginggain.nl`, `v10.` removed; 500 from a `.env` without `APP_KEY` → `key:generate`; caches + `lara:route:cache`; Let's Encrypt for both names (until 2027-02-26); `rm -rf storage/app/public/_fonts && google-fonts:fetch`; `robots.txt` open (= repo); sitemap 437 www URLs. Checked from here: `http://` → https, non-www → www with path and query (301), `/` → `/nl`, SSL, production text pass 92 URLs identical to local (`gain-compare/production-pass.md`), no staging/local hosts, every media and asset URL 200, fonts from www. User: password-reset mail through AuthSMTP arrived, sitemap submitted in Search Console. **Left to the user:** watch the 404s in Search Console (the old slugs with spaces have no redirects); keep the v8 app (`yrmfjkvtpn`) a few weeks without domains, crons and mail.
