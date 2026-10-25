# Plan: calendar export of invitations (2026-10-14)

Goal: visitors can put a planned trip into their calendar with one click, and members can subscribe to a calendar feed that keeps itself up to date with the club's upcoming trips. Both use iCalendar (RFC 5545, `.ics`), which Google Calendar, Apple Calendar, Outlook and Thunderbird all import.

**Status: done in PR #34** (merged 2026-10-14). Decided: a hand-written builder (option a below), and a separate `InvitationCalendarController`. The date conversions live in `App\Service\EventTime` (the plan suggested `App\Util\EventTime`).

Still open:
- After deploy: import an invitation's `.ics` and subscribe to the feed in Google Calendar and Apple Calendar ([deploy checklist](../deploy-checklist.md)).
- Optional, not built: a per-invitation "Google Kalendár" link (step 4).

## What we build

| URL | Route name | Content |
|---|---|---|
| `/pozvanky/{year}/{slug}/kalendar.ics` | `invitation_ics` | one published invitation, downloaded as a file (`Content-Disposition: attachment; filename="{slug}.ics"`) |
| `/pozvanky/kalendar.ics` | `invitation_ics_feed` | a calendar to subscribe to: published invitations that start from 90 days ago onwards (`Content-Disposition: inline`) |

- Both are `GET` only and return `Content-Type: text/calendar; charset=utf-8`.
- Why `/{slug}/kalendar.ics` and not `/{slug}.ics`: the detail route `/pozvanky/{year}/{slug}` accepts any `[^/]+` slug, so it would match `foo.ics` first. `/pozvanky/kalendar.ics` doesn't collide with `/pozvanky/{year}` (`\d+`) or `/pozvanky/aktualne`.
- The feed keeps 90 days of past trips so that a trip doesn't disappear from subscribers' calendars the day after it happens. The 20-item limit of `findLatest()` doesn't fit here, so add a new repository method (step 2).
- Unpublished invitations return 404 and stay out of the feed, the same as the detail page (`findByYearSlug()` already filters on `publish`). Admins get no exception here: drafts don't belong in a calendar.

## Mapping invitation → `VEVENT`

| iCalendar property | Source | Notes |
|---|---|---|
| `UID` | `invitation-{id}@kst.zochar.sk`, `{id}` being the entity's auto-increment id | stable across title and slug edits, so a re-import updates the event instead of duplicating it. The prefix and `@domain` make it globally unique, as RFC 5545 asks: a bare `42` could clash with another calendar's event in the same app, or with a chronicle `42` if those are ever exported |
| `DTSTAMP` | `modifiedAt ?? createdAt`, in UTC (`…Z`) | real UTC instants, like `iso_timestamp`. Using the last change, not "now", keeps the output stable |
| `LAST-MODIFIED` | same as `DTSTAMP` | |
| `SUMMARY` | `title` | |
| `DTSTART` / `DTEND` | `startDate` / `endDate` | see "Dates" below |
| `DESCRIPTION` | `summary`, then an empty line and the detail page URL | plain text, `strip_tags` like the JSON-LD does |
| `URL` | `invitation_url(invitation)` | already in `StructuredDataExtension` |
| `LOCATION` | `location`: name, street, `postalCode addressLocality`, country, joined with `, `, skipping empty parts | omitted when there is no location |
| `GEO` | `latitude;longitude` | only when both are set |
| `CATEGORIES` | names of `sportType` | optional, cheap |
| `ORGANIZER` | not set | RFC requires a `mailto:` address, and we don't want to publish one |
| `STATUS` | `CONFIRMED` | |

Calendar-level properties: `VERSION:2.0`, `PRODID:-//KST Žochár Topoľčany//kst.zochar.sk//SK`, `CALSCALE:GREGORIAN`, `METHOD:PUBLISH`. The feed also gets `X-WR-CALNAME` (translated, e.g. "KST Žochár – pozvánky"), `X-WR-TIMEZONE:Europe/Bratislava`, `REFRESH-INTERVAL;VALUE=DURATION:PT12H` and `X-PUBLISHED-TTL:PT12H`, so clients poll twice a day.

### Dates

The stored dates are wall-clock Bratislava times saved as if they were UTC (see `event_iso_date` in `StructuredDataExtension`), and 00:00 means that no time was set. **Decided 2026-10-14: this stays.** Event dates are stored in local time, and only real instants (`createdAt`, `publishedAt`, `modifiedAt`) are UTC. Admins and visitors always see local time, and the export converts to UTC only on output. Switching the storage to UTC was rejected: it would need a data migration, and the year in URLs (`SUBSTRING(startDate, 1, 4)`) would break for trips at local midnight on 1 January. The rules:

1. **Start has a time.** Reinterpret it in `Europe/Bratislava`, convert to UTC and write `DTSTART:20261018T050000Z`. UTC with `Z` avoids having to emit a `VTIMEZONE` block, which `TZID=` would require.
2. **Start is 00:00 (all-day).** Write `DTSTART;VALUE=DATE:20261018`.
3. **`DTEND`.** RFC 5545 requires `DTEND` to have the same value type as `DTSTART`, and a `DATE` end is exclusive:
   - all-day start, end set: `DTEND;VALUE=DATE:` end date **+ 1 day** (any time on the end is dropped);
   - all-day start, no end: `DTEND;VALUE=DATE:` start + 1 day (a one-day, all-day event; without a start time there is no hour to end at 18:00 from);
   - timed start, timed end: the end converted like the start;
   - timed start, end at 00:00: the next day's 00:00 Bratislava time, converted to UTC (the trip lasts through the end date);
   - timed start, no end: a one-day event that ends at **18:00** Bratislava time on the start day (`DTEND` converted to UTC). If the start is at 18:00 or later (an evening walk), end one hour after the start instead, so that `DTEND` is always after `DTSTART`. Keep 18:00 as a class constant (`DEFAULT_END_TIME`).

### Text encoding (the part that's easy to get wrong)

- Escape `\` → `\\`, `;` → `\;`, `,` → `\,`, and newlines → `\n` in text values. Strip `\r`.
- Lines end with CRLF.
- Fold lines longer than **75 octets** (not characters): continue on the next line starting with one space. Slovak letters are 2 bytes in UTF-8, so the folding must not split a multibyte character (`mb_strcut` handles it).

## Decision needed

**Library or hand-written?**
- **a) (recommended)** A small hand-written builder, about 150 lines. We need one component type, and the rules above are fully specified. No new dependency on shared hosting, and unit tests cover the encoding.
- b) `eluceo/ical` (v2). It handles escaping, folding and timezones, but it's another dependency to keep compatible with PHP 8.5 and Symfony 8.1 upgrades, and its domain model is more than we need. Check its PHP 8.5 support before choosing it.

The steps below assume a).

## Steps

### 1. `App\Service\InvitationCalendar`
- `public function single(EventInvitation $invitation): string` and `public function feed(iterable $invitations, string $name): string`, both returning the whole `VCALENDAR` text.
- Private helpers: `event(EventInvitation): list<string>` (the property lines), `dates()`, `escape(string): string`, `fold(string): string`, `location(Location): ?string`.
- Dependencies: `UrlGeneratorInterface` for the absolute URL (or move `invitationUrl()` into a small service shared with `StructuredDataExtension`, so both build the URL the same way), `TranslatorInterface` for the calendar name.
- Reuse the Bratislava reinterpretation from `StructuredDataExtension` rather than copying it. Extract `TIMEZONE` and the "wall clock → `DateTimeImmutable` in Bratislava" step into a small static helper (e.g. `App\Util\EventTime`) that both classes call.

### 2. Repository
- `EventInvitationRepository::findForCalendar(DateTimeImmutable $since): list<EventInvitation>` returns published invitations with `startDate >= $since`, ordered by `startDate`, with `location` and `sportType` joined (`addSelect`) to avoid N+1 queries.
- The controller passes `new DateTimeImmutable('today -90 days')`.

### 3. Controller
- Two actions in `EventInvitationController` (or a new `InvitationCalendarController` to keep the existing one from growing, which is the recommendation).
- Single: `findByYearSlug()` → 404 when null → `new Response($calendar->single($invitation), headers: [...])`.
- Feed: `findForCalendar()` → response. Add `Cache-Control: public, max-age=3600` (`$response->setPublic()->setMaxAge(3600)`), because calendar clients poll often. No `content.cache` entry: the query is small, and caching it would need a midnight expiry like the home page.
- Declare the routes **before** `invitation_show_by_Year_by_Slug` in the class, or give them `priority: 1`. `/pozvanky/{year}/{slug}/kalendar.ics` has an extra segment, so it doesn't actually collide, but `/pozvanky/kalendar.ics` should be checked against every `/pozvanky/{…}` route.

### 4. Templates and translations
- Invitation detail (`showEventInvitationByYearBySlug.html.twig`), next to the date paragraph: a link "Pridať do kalendára" (`btn btn-outline-secondary btn-sm`) to `invitation_ics`. Optionally a second link "Google Kalendár" to `https://calendar.google.com/calendar/render?action=TEMPLATE&text=…&dates=…&details=…&location=…`, built by a Twig function from the same date rules. It's just a link, so it sets no cookies on our site.
- Upcoming list (`showEventInvitationUpcoming.html.twig`): a short paragraph "Odoberajte naše pozvánky do svojho kalendára" with two links:
  - `webcal://…/pozvanky/kalendar.ics` (the absolute URL with the scheme swapped; Apple Calendar and Outlook open it as a subscription);
  - the plain `https://` URL to copy into Google Calendar ("Pridať kalendár → Z webovej adresy"), with a one-line explanation.
- `<link rel="alternate" type="text/calendar" title="…" href="{{ url('invitation_ics_feed') }}">` in the `head` block of the upcoming page.
- New keys in `translations/messages.sk.yaml` under `templates.eventInvitation.*` and a new `calendar.*` group (calendar name, link texts, the Google help text). No hard-coded strings.

### 5. Things to leave alone
- **Sitemap**: don't add the `.ics` URLs. They aren't HTML pages.
- **robots.txt**: nothing to block.
- **Cookies page**: no new cookies, so `/cookies` stays as it is.
- **Security headers**: the listener adds them to `.ics` responses too, which is harmless.
- **Chronicles and blogs**: no export. They describe past events.

### 6. Tests
- **New `tests/Service/InvitationCalendarTest.php`** (plain `TestCase`, entities built in the test; the first test in `tests/Service/`):
  - escaping of `,`, `;`, `\` and newlines in the title and summary;
  - folding: a long Slovak title gives lines of at most 75 octets, each continuation starts with a space, unfolding gives back the original text, and no line is invalid UTF-8;
  - every line ends with CRLF;
  - dates: timed start in summer (`10:00` → `080000Z`), in winter (`10:00` → `090000Z`), all-day start with and without an end (exclusive `DTEND` + 1 day), timed start with an end at 00:00, timed start with no end (`DTEND` at 18:00 local: `160000Z` in summer, `170000Z` in winter), and a 19:00 start with no end (`DTEND` one hour later);
  - `LOCATION` and `GEO` only when the location and its coordinates are set.
- **New `tests/Controller/InvitationCalendarTest.php`** (functional, fixture data):
  - `/pozvanky/2011/{fixture slug}/kalendar.ics` → 200, `text/calendar` content type, attachment disposition, contains `BEGIN:VCALENDAR`, `SUMMARY:` with the fixture title, `UID:invitation-{id}@kst.zochar.sk`, `DTSTART:20110910T080000Z` (fixture `2011-09-10 10:00`, summer time);
  - a data provider of 404s: the unpublished fixture invitation, an unknown slug, a wrong year;
  - feed → 200, contains the "tomorrow" fixture invitation (`invitation2`), doesn't contain the 2011 one (older than 90 days) or the unpublished one, and has `X-WR-CALNAME`;
  - the detail page and the upcoming page contain the new links (CSS selector on `a[href$="kalendar.ics"]` and `a[href^="webcal://"]`).
- Existing tests: `MetaTagsTest` and `BreadcrumbTest` shouldn't change. Check that `SecurityHeadersTest` and `AdminAccessTest` need no additions (the new routes are public).

### 7. Docs
- `CLAUDE.md`, Architecture: one bullet on the calendar export (routes, `InvitationCalendar`, the date rules, "unpublished never exported").
- `CLAUDE.md`: say explicitly that event `startDate`/`endDate` are stored as local Europe/Bratislava wall-clock time, by decision, not UTC, so nobody "fixes" it later.
- README: nothing to change for deploy (no migration, no new env var).

## Size

Medium: one service with unit tests, one controller, two template additions, translations. No schema change and no migration.
