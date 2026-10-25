# Production deploy checklist

Manual production steps that merged pull requests still need. Production runs on shared hosting without Docker (README, "Production"). When a pull request needs such a step, add it here in the same pull request. Remove the items once they have been done on production.

As far as is known (2026-10-25), production hasn't been deployed since before PR #19, so everything below is still open. Check what is on the hosting before going through the list.

## Before the deploy

- [ ] **Decide between gtag.js and Google Tag Manager** ([decision](decisions/ga4-vs-gtm.md)). The deploy replaces the hand-edited production template, which removes the Google Tag Manager snippet that isn't in git.
- [ ] **Set `GA_MEASUREMENT_ID=G-…`** in the production `.env.local` if gtag.js is chosen (PR #25). Use the same ID as the GA4 tag in the Google Tag Manager container, so the property's history continues.
- [ ] **Check for a physical `public/robots.txt`** on the hosting (PR #24). If one exists, it hides the `/robots.txt` route, so remove it.

## During the deploy

- [ ] `composer install` (new dependencies, e.g. `twig/intl-extra` from PR #19 and `symfony/html-sanitizer` from PR #33), and upload the production asset build (`npm run build`).
- [ ] **Run the migrations** with `bin/console doctrine:migrations:migrate`:
  - `Version20000101000000`, the baseline: only recorded, because the tables exist (PR #22);
  - `Version20261008190000`: removes the DBAL 3 type comments (PR #22);
  - `Version20261008190100`: the `location` table (PR #19); it only records itself if the table already exists;
  - `Version20261011152211`: `VARCHAR(255)` string columns (PR #28).
- [ ] `bin/console cache:clear`, then `bin/console cache:pool:clear content.cache`. The content cache is stored in the database, so `cache:clear` doesn't empty it (PR #31).

## After the deploy

- [ ] Review the `/cookies` text (PR #26). Accept the banner and check that GA4 Realtime shows the page view (the `…/g/collect` request has `gcs=G101`). Declining must load nothing (PR #27).
- [ ] Fix the first real meeting locations on `/miesta-stretnutia` (PR #23).
- [ ] Submit `https://kst.zochar.sk/sitemap.xml` in Search Console (PR #24).
- [ ] Rich Results Test (Event and Article) and validator.schema.org on a live invitation, blog post and chronicle. Check the microformats with https://pin13.net/mf2/ (PRs #20, #21).
- [ ] Import an invitation's `.ics` and subscribe to `/pozvanky/kalendar.ics` in Google Calendar and Apple Calendar (PR #34).
- [ ] Check the security headers on a live page, e.g. with `curl -I` (PR #33).
- [ ] Retire the Google Tag Manager container if gtag.js was chosen.

## Waiting on input

- `og:image`: needs a picture of about 1200×630 pixels for link previews (PR #25).
