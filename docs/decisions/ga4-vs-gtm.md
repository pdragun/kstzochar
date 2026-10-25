# Decision: Google Analytics through gtag.js or Google Tag Manager (2026-10-08)

Status: **open**, waiting for a decision (still open on 2026-10-25). Nothing is deployed until it is made.

## The problem

- **Repo** (since PRs #25, #26 and #27): GA4 is loaded with gtag.js from `assets/js/cookieConsent.js`. It loads only after the visitor accepts the cookie banner, sets Consent Mode v2 defaults (analytics granted; `ad_storage`, `ad_user_data` and `ad_personalization` denied) and reads the measurement ID from the env var `GA_MEASUREMENT_ID`.
- **Production** (https://kst.zochar.sk, checked 2026-10-08): the `<head>` of every page loads the Google Tag Manager container `GTM-NFDSVQF7`. It loads **before any consent** and has a `<noscript>` iframe in `<body>`. The snippet isn't in the repo or its git history (`git log -S "GTM-"` finds nothing), so someone added it by hand to the template on the hosting.

Consequences:
- **The next deploy removes the GTM snippet.** Whatever the container does stops, unless `GA_MEASUREMENT_ID` is set or GTM is added to the repo.
- **Running both would count GA4 twice.** If GTM is put back next to `GA_MEASUREMENT_ID` and the container holds a GA4 tag, every page view is counted twice.
- **The current production setup ignores consent.** It loads Google tags without consent, which the cookie banner is meant to prevent (EU ePrivacy / Slovak electronic communications law).

## What GA4 and GTM are

GA4 and GTM are not alternatives:

- **GA4 (Google Analytics 4)** is the analytics service: it collects the data and shows the reports. Both options end up in GA4.
- **GTM (Google Tag Manager)** is a container, managed in a web interface, that loads other tags (GA4, Google Ads, Meta pixel, …) and can add custom triggers, such as tracking clicks.
- **gtag.js** is Google's script for loading GA4 directly from the page code. Google serves it without a version number, so it's always the newest.

The decision is whether to load GA4 **directly with gtag.js** or **through GTM**.

## Option A: gtag.js directly (current repo code)

Pros:
- **Everything is in git.** The tracking and consent code are reviewed, tested and deployed with the site, so there are no hidden changes like the current GTM snippet.
- **Consent is guaranteed.** No tag can be added without a code change, so the banner and `/cookies` stay accurate.
- **Fewer scripts.** It loads one script instead of `gtm.js` plus gtag.js inside it.
- **It's already done.** It needs only `GA_MEASUREMENT_ID=G-…` in the production `.env.local`.

Cons:
- **Any new tracking needs a code change and a deploy.**

Downloads:
- **PDF downloads are covered** by GA4's own Enhanced measurement → File downloads (`file_download` event for links ending in `.pdf`, `.docx`, `.xlsx`, `.zip`, …). It works the same with gtag.js. The site's PDF (`/build/downloads/stanovy_kst-zochar-topolcany_2011-03-31.pdf` on `/kontakt`) and PDFs linked in articles qualify.
- **GPX downloads are not tracked automatically**: `/gpx/{id}` has no file extension, and `.gpx` isn't on GA4's list. That needs a few lines in `cookieConsent.js` (a `file_download` event on clicks on `/gpx/` links, only when consent was given).

## Option B: Google Tag Manager

Pros:
- **Tags can be changed without a deploy.** They're added and changed in the GTM web interface, which suits someone who doesn't edit code.
- **It suits many marketing tags**, such as Google Ads conversions, Meta pixel or Hotjar, and custom event tracking.

Cons:
- **Consent must be wired up** in the code and in GTM. `cookieConsent.js` would load `gtm.js` instead of gtag.js only after consent, and the `<noscript>` iframe would go, because it can't respect consent. Every tag in the container must also respect consent: GTM's built-in consent checks plus Consent Mode defaults.
- **Anyone with access to the container can add tags** that the banner and `/cookies` don't mention, without anyone seeing it in the repo.
- **The tracking configuration lives outside git.**
- **It's more work.** `cookieConsent.js` needs changing (the env var would hold a `GTM-…` ID), and `/cookies` needs reviewing.

## Recommendation

**Option A (gtag.js directly)** fits a club website that needs page views and download counts. Choose B only if the container turns out to do something gtag.js can't easily replace, or someone outside the code needs to manage tags.

## Before deciding, check the GTM container

In https://tagmanager.google.com, open container `GTM-NFDSVQF7` and check:

- [ ] **Who created it** and who has access (Admin → User management).
- [ ] **Tags**: which tags exist (GA4 / Google tag, PDF tracking, others). Write down the GA4 measurement ID (`G-…`).
- [ ] **Triggers**: what the PDF tracking reacts to, and whether anything tracks GPX or other clicks.
- [ ] **Whether the GA4 property** (Admin → Data streams → web stream) has Enhanced measurement → File downloads turned on.

## Steps per option

### A: gtag.js
1. ~~Merge PR #27~~ (Consent Mode defaults). Done, merged 2026-10-11.
2. **Optionally track GPX downloads** in `cookieConsent.js` (separate PR).
3. **Set the ID on production:** `GA_MEASUREMENT_ID=G-…` in `.env.local`. Use the same ID as the GA4 tag in GTM, so the same property and its history continue.
4. **Deploy.** The repo's `base.html.twig` replaces the hand-edited one and removes the GTM snippet.
5. **Verify:** accept the banner and check that GA4 → Realtime shows the page view. The `…/g/collect` request should have `gcs=G101`, and clicking the PDF should send `file_download`.
6. **Retire the GTM container:** pause its tags or delete it.

### B: GTM
1. **Load GTM after consent:** change `cookieConsent.js` to load `gtm.js` (`GTM-…` from an env var) instead of gtag.js. Keep the Consent Mode defaults before it and drop the `<noscript>` iframe.
2. **Set up consent in the container:** in GTM, turn on Consent overview and make every tag require consent (`analytics_storage` for GA4).
3. **Update `/cookies`** with every cookie the container's tags set.
4. **Prevent double counting:** make sure no `GA_MEASUREMENT_ID` is set next to it.
5. **Deploy and verify** with Tag Assistant (https://tagassistant.google.com).

## Sources

- Set up consent mode on websites: https://developers.google.com/tag-platform/security/guides/consent
- Troubleshoot consent mode with Tag Assistant: https://developers.google.com/tag-platform/security/guides/consent-debugging
- Google tag API reference: https://developers.google.com/tag-platform/gtagjs/reference
- Behavioral modeling for consent mode: https://support.google.com/tagmanager/answer/11161109
- Tag Manager release notes: https://support.google.com/tagmanager/answer/4620708
