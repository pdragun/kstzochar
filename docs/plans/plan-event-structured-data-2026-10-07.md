# Plan: structured data for event invitations, blogs and chronicles (2026-10-07)

Goal: search engines recognize an invitation page (`/pozvanky/{year}/{slug}`) as an event, so it can show up as an event rich result in Google. Part 2 (below) adds article and author markup to blog posts and chronicles.

**Status: done.** Part 1 in PR #20 and part 2 in PR #21 (both merged 2026-10-08). Part 1 has no `image`: there is no raster image of at least 50 000 pixels yet. The checks after deploy (steps 6 and 11) are on the [deploy checklist](../deploy-checklist.md).

**Prerequisite — execute first:** `plan-start-location-2026-10-07.md` (the `Location` entity with address and coordinates, `EventInvitation::$location`). It must be merged before this plan starts. It settles decision 1 below and replaces step 4.

## Background: which format

- **schema.org `Event` in JSON-LD** (a `<script type="application/ld+json">` in `<head>`) is the format Google, Bing and others document and recommend for event rich results. This is the main deliverable.
- **Microformats2 `h-event`** (the `class="h-event p-name dt-start"` vocabulary) is the successor of the old hCalendar `vevent`. Google does not use it for rich results, but IndieWeb tools, some feed readers and calendar importers do. The invitation template already has a partial `h-event` (`templates/event_invitation/showEventInvitationByYearBySlug.html.twig:50`), but it is broken: `dt-start` sits on a `<strong>` with a `datetime` attribute, and parsers only read `datetime` from `<time>`, so they get the text "10. 9. 2011" instead of an ISO date. We fix it while we are there; it costs a few attributes.
- The breadcrumbs in `base.html.twig` already emit a JSON-LD `BreadcrumbList` (`App\Twig\StructuredDataExtension::breadcrumbJsonLd()`, moved from microdata on 2026-10-08), so all schema.org data on the site is JSON-LD. No microdata: it is coupled to the visual markup and harder to keep correct.

## What data we have

| schema.org property | Source | Notes |
|---|---|---|
| `name` | `invitation.title` | required |
| `startDate` | `invitation.startDate` | required, see timezone below |
| `endDate` | `invitation.endDate` | recommended; omit when null |
| `description` | `invitation.summary` | |
| `url` | absolute URL of `invitation_show_by_Year_by_Slug` | |
| `eventStatus` | constant `https://schema.org/EventScheduled` | |
| `eventAttendanceMode` | constant `https://schema.org/OfflineEventAttendanceMode` | |
| `organizer` | constant `Organization` "KST Žochár Topoľčany", `url` https://kst.zochar.sk | take the name from `base.title` or a new translation key |
| `image` | club logo (`build/images/kst-zochar_znak.svg`) as fallback; Google wants a raster image, so a PNG/JPG version is better | recommended |
| `location` | `invitation.location` (prerequisite plan): `Place{name, address: PostalAddress{streetAddress?, addressLocality, postalCode?, addressRegion?, addressCountry}, geo?: GeoCoordinates{latitude, longitude}}` | **required by Google** for the rich result; omit the whole key when the invitation has no location, and omit null sub-fields |
| `sport`, routes | `sportType`, `routes` (title, length, elevation) | no standard Event property fits; could go into `description` text, optional |

### Timezone

PHP runs with `date.timezone = UTC` (`frankenphp/conf.d/10-app.ini`), and the `DateTimeType` form stores the wall-clock time the admin typed (for example 10:00 Slovak time) as a "UTC" value. `|date('c')` would output `2011-09-10T10:00:00+00:00`, which is two hours wrong. The builder has to reinterpret the stored wall-clock time in `Europe/Bratislava`:

```php
new DateTimeImmutable($date->format('Y-m-d H:i:s'), new DateTimeZone('Europe/Bratislava'))
```

and then format with `DATE_ATOM` (gives `+02:00` in summer and `+01:00` in winter). When the time is exactly `00:00`, the admin most likely did not set a time, so emit the date only (`2011-09-10`), which schema.org allows for all-day events.

## Decisions needed

1. **Location.** *Settled by the prerequisite plan: a `Location` entity (name, town, country, optional street, postal code, region and coordinates) linked to the invitation, emitted as a `Place` with a `PostalAddress` and optional `GeoCoordinates` only when set.* Original options, kept for reference: Google requires `location` (a `Place` with `name` and `address`) for an event rich result. Without it the JSON-LD is still valid schema.org and helps general understanding, but the rich result will not appear.
   - **a) (recommended)** Add a nullable `location` string to `EventInvitation` (meeting place, for example "Topoľčany, autobusová stanica"), a form field in `EventInvitationType`, and emit `Place{name: location, address: {PostalAddress, addressLocality: location, addressCountry: "SK"}}` only when it is set. Old invitations stay without it.
   - b) Use a fixed default (`Topoľčany`, the club's home town) when nothing is set. Simple, but often wrong for trips that start elsewhere, and Google penalizes misleading structured data.
   - c) No location for now; ship JSON-LD without it and decide later.
   - Note: the project has no migrations (`migrations/` is empty, the schema is created with `doctrine:schema:create`), so option a) needs a manual `ALTER TABLE event_invitation ADD location VARCHAR(255) DEFAULT NULL` on production, or `doctrine:schema:update --dump-sql` to generate it.
2. **Scope beyond the detail page.** Recommendation: only the invitation detail page. Chronicles describe past events (not useful for event search), and list pages should link to the detail pages instead of repeating the data.

## Steps

### 1. JSON-LD builder
- Add a function `event_json_ld(invitation)` to the existing Twig extension `App\Twig\StructuredDataExtension` (it already has `breadcrumb_json_ld()` and the private `encode()` with the flags below; split the building into an `App\Service\EventStructuredData` service if it gets large).
- Input: `EventInvitation`; dependencies: `UrlGeneratorInterface` (absolute URL with `UrlGeneratorInterface::ABSOLUTE_URL`), `Packages` (absolute logo URL), `TranslatorInterface` (organizer name).
- Output: an array with `@context`, `@type: Event` and the properties from the table; leave out null values.
- Encode with `json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_THROW_ON_ERROR)`. `JSON_HEX_TAG` keeps a title containing `</script>` from breaking out of the script tag.
- `description`: `summary` is plain text, but strip tags anyway (`strip_tags`) in case HTML gets into it.

### 2. Template slot in `base.html.twig`
- Add an empty `{% block head %}{% endblock %}` inside `<head>`, after the meta tags.
- While editing: the default description has a stray `<` (`{{ 'base.description'|trans }}<{% endblock %}`), remove it.

### 3. Invitation detail template
- `{% block head %}<script type="application/ld+json">{{ event_json_ld(invitation)|raw }}</script>{% endblock %}`
- Fix the `h-event` markup:
  - `<time class="dt-start" datetime="…">` instead of `<strong class="dt-start">` (keep the bold with `<strong><time …>…</time></strong>`, so the `#start-date` text assertion in the test stays the same). Use the same Bratislava-offset value as the JSON-LD (expose it from the builder, for example a `event_iso_date(date)` Twig filter).
  - `dt-end` when `endDate` is set (the template does not show the end date today; showing "– 12. 9. 2011" would be a small visible change, decide while implementing).
  - `p-summary` on a visible or hidden summary, `u-url` on a permalink (the `h1` can wrap a link, or add `<a class="u-url" href="…" hidden>`), `p-location h-card` is already rendered by the prerequisite plan; check that it sits inside the `h-event` element.

### 4. Location (done in `plan-start-location-2026-10-07.md`; the items below are superseded)
- `EventInvitation::$location` (`Types::STRING`, length 255, nullable) with getter and setter.
- Form field in `src/Form/EventInvitationType.php`, label in `translations/messages.sk.yaml` (`form.eventInvitationType.location`).
- Show it on the detail page ("Miesto stretnutia: …", new translation key), inside `p-location`.
- Fixtures: set a location on one invitation (`gulasove-opojenie-v-tesaroch` → "Tesáre, futbalové ihrisko") and leave the others empty.
- Production: run the `ALTER TABLE` before deploying.

### 5. Tests (`tests/Controller/EventInvitationControllerTest.php`)
- `testShowInvitation`: read `script[type="application/ld+json"]`, `json_decode` it, assert `@type` is `Event`, `name`, `startDate` is `2011-09-10T10:00:00+02:00` (summer time), `url` is absolute, `location.name`, `location.address.addressLocality` and `location.address.addressCountry` match the fixture; the unused Topoľčany fixture location, once picked, also gives `streetAddress`, `postalCode` and `geo`.
- A winter invitation (`2010-01-09 07:00`, Javorový vrch) gives `+01:00`.
- An invitation without `endDate` and without location has neither key in the JSON.
- `h-event`: `time.dt-start` has a `datetime` attribute with the ISO value.
- Optional unit test for the builder (00:00 → date only, `</script>` in a title is escaped).
- Run `task check` (phpstan, rector:check, phpunit).

### 6. Verification after deploy
- Paste a live invitation URL into the Google Rich Results Test (https://search.google.com/test/rich-results) and into https://validator.schema.org.
- Microformats: check with https://pin13.net/mf2/ (php-mf2 parser) that `h-event` has `name`, `start` and `url`.
- Search Console → Enhancements → Events shows errors and warnings after Google recrawls the pages.

## Part 2: author and article markup for blogs and chronicles

Goal: blog posts (`/{section}/{year}/{slug}`) and chronicles (`/kronika/{year}/{slug}`) are recognized as articles with an author and a publication date. Today neither page has any article or author markup: `templates/blog/showBlogByBlogSectionSlugYearSlug.html.twig` renders only the `h1` and the content, the chronicle template adds dates of the event and routes, and neither shows who wrote it or when. Ship it as a separate PR after part 1, reusing the `head` block (step 2) and the date helper (step 1).

### What data we have

| schema.org property | microformats2 | Source | Notes |
|---|---|---|---|
| `@type` | `h-entry` | `BlogPosting` for blogs, `Article` for chronicles | |
| `headline` | `p-name` | `title` | Google truncates at 110 characters, no need to cut |
| `description` | `p-summary` | `summary` | strip tags |
| `articleBody` | `e-content` | `content` | only as `e-content` on the wrapper; do not copy the HTML into JSON-LD |
| `author` | `p-author h-card` | `authorBy ?? createdBy` → `Person{name: displayName}` | see decision A |
| `datePublished` | `dt-published` | `publishedAt ?? createdAt` | set by `BlogEditor::create()` / `EventContentEditor::create()`; fixture blogs have `publishedAt = null`, so the fallback matters |
| `dateModified` | `dt-updated` | `modifiedAt` | omit when null |
| `url` / `mainEntityOfPage` | `u-url` | absolute URL of the detail route | |
| `publisher` | — | same `Organization` as the event `organizer` (name, url, `logo`) | reuse the builder code |
| `image` | `u-photo` | club logo fallback, as for events | a first image from `content` would be better, optional |

Dates use the same Bratislava reinterpretation as the events (`DATE_ATOM`); these are real timestamps from `new DateTimeImmutable()`, so emit the full date-time, not date only.

### Decisions (2026-10-08: A a, B displayName, C byline)

A. **Who is the author.** `createdBy` is the admin who entered the post (always set); `authorBy` is the person who wrote it (nullable, the form field is commented out in `BlogType.php:70`, `EventChronicleType.php:97`, `EventInvitationType.php:80`, so only fixtures set it).
   - **a) (recommended)** Use `authorBy ?? createdBy`, and re-enable the `authorBy` field in `BlogType` and `EventChronicleType` (an `EntityType` on `User` with `choice_label: 'displayName'`, `required: false`, the commented code is already there), label in `translations/messages.sk.yaml`.
   - b) Use `createdBy` only and leave the forms alone. Wrong for posts that an admin entered on behalf of a member.
B. **Name shown publicly.** `User` has `displayName` (fixture "John Doe") and `nickName` ("John D."). Recommendation: `displayName`, but check with the club that members agree to have their full name on public pages; `nickName` is the privacy-friendly alternative. The author `url` is omitted (there are no public profile pages).
C. **Visible byline.** Microformats and good practice (Google: author markup should match visible content) need the author and date on the page. Recommendation: a small line under the `h1`, for example "Autor: John Doe · 7. 10. 2026" (new translation keys `article.author`, `article.published`).

### Steps

#### 7. Builder
- Extend the Twig extension from step 1 with `article_json_ld(content)` accepting `Blog|EventChronicle` (or a small shared interface with `getTitle()`, `getSummary()`, `getAuthorBy()`, `getCreatedBy()`, `getPublishedAt()`, `getCreatedAt()`, `getModifiedAt()`; `EventChronicle` already implements `EventContent`, check what it covers).
- A helper `content_author(content)` returning `authorBy ?? createdBy`, used by both the JSON-LD and the byline, so they cannot disagree.
- URL: blogs need the section slug and the `createdAt` year, chronicles the start-date year; pass the URL in from the template (`url(...)`) if building it in PHP gets awkward.

#### 8. Templates
- Blog and chronicle detail: `{% block head %}<script type="application/ld+json">{{ article_json_ld(blog)|raw }}</script>{% endblock %}`.
- Wrap the article in `<article class="h-entry">`: `h1.p-name` (with a `u-url` permalink), the byline `<p class="byline">Autor: <span class="p-author h-card">{{ author.displayName }}</span> · <time class="dt-published" datetime="…">7. 10. 2026</time></p>`, the content in `<div class="e-content">`, the summary as a hidden `p-summary` (or skip it, parsers fall back to the content).
- Chronicles are also past events; they stay `h-entry`/`Article` only (decision 2), no `h-event` nesting.

#### 9. Forms (if decision A a)
- Uncomment and fix the `authorBy` field in `BlogType` and `EventChronicleType`; `EventInvitationType` too if invitations should get a byline later (not part of this plan).
- No schema change: the `author_by_id` columns already exist.

#### 10. Fixtures and tests
- Fixtures: set `authorBy` on one blog, leave another without it (falls back to `createdBy`); add a second user with a different `displayName` so the test can tell `authorBy` and `createdBy` apart.
- `BlogControllerTest` / `EventChronicleControllerTest`: decode the JSON-LD, assert `@type`, `headline`, `author.name`, `datePublished` (with the `createdAt` fallback for a blog with `publishedAt = null`), absolute `url`; assert the visible byline text and `.h-entry .p-author` / `time.dt-published[datetime]`.
- Form tests for the `authorBy` field if the forms change.
- `task check`.

#### 11. Verification
- Rich Results Test (Article) and validator.schema.org on a live blog post and chronicle; pin13.net/mf2 shows an `h-entry` with `name`, `author`, `published`, `url`.

## Out of scope / later
- `og:` and Twitter card meta tags (title, description, image) for link previews on Facebook; same `head` block, separate small change. Done in PR #25, except `og:image`, which needs a picture of about 1200×630 pixels.
- An iCalendar (`.ics`) download per invitation, which would give real "add to calendar" support. Done in PR #34 ([plan](plan-ics-export-2026-10-14.md)).
- `Event` markup on chronicles or on the plan (`/plan/{year}`). Open.
