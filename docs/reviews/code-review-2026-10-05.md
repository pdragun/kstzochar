# Code review: whole project (2026-10-05)

Scope: everything in the repository on `main` at 7c14a82 (`src/`, `templates/`, `config/`). There is no diff, so the whole codebase was reviewed.
Mode: high effort, recall-oriented. The findings were not verified, so some may be false positives. They are ranked most severe first.

```json
[
  {
    "file": "src/Repository/BlogRepository.php",
    "line": 111,
    "summary": "findBySectionYearSlug() takes $sectionId but never filters on b.section, so a blog can be reached, edited and deleted under any section slug.",
    "failure_scenario": "Two blogs titled 'Novinky' exist in 2025: one in 'z-klubovej-kuchyne' and one in 'viacdnove-akcie'. /blog/viacdnove-akcie/2025/novinky returns the one with the lower createdAt (the kitchen post), and blog_edit/blog_delete_yes act on that wrong row. /blog/receptury-na-tury/2025/<any-slug> also renders blogs from other sections under the wrong section header."
  },
  {
    "file": "src/Repository/EventRepository.php",
    "line": 71,
    "summary": "findMaxStartDate() selects MAX(e.startDate) with GROUP BY e, so the MAX is per row, and setMaxResults(1) without ORDER BY returns an arbitrary event, not the latest year. getSingleResult() also throws NoResultException when there are no published events.",
    "failure_scenario": "The plan holds events for 2019 through 2026. The first row MySQL returns is a 2019 event, so the home page and /plan link to 'Plán na rok 2019'. On an empty or all-unpublished events table, the home page and /plan return 500 (NoResultException) instead of the intended null and 404, because the `if (!$res ...)` guard is unreachable."
  },
  {
    "file": "src/Controller/HomePageController.php",
    "line": 38,
    "summary": "The 'home-page' cache item has no expiry, yet it stores date-relative queries: upcoming invitations (startDate >= today), the next multi-day blog and the latest chronicle.",
    "failure_scenario": "On 1 May the home page is cached with 'Najbližšia akcia: Výstup na Tribeč (3. 5.)'. Nobody edits content for two weeks, so on 15 May the home page still shows the past event as upcoming, and the upcoming count is wrong. Only a content write calls clearAllCache(). Fix: $item->expiresAt(tomorrow midnight)."
  },
  {
    "file": "src/Controller/EventInvitationController.php",
    "line": 384,
    "summary": "Destructive actions (invitation_delete_yes, chronicle_delete_yes, blog_delete_yes) are plain GET routes with no CSRF token.",
    "failure_scenario": "A logged-in admin opens a page or email that contains <img src=\"https://kst.zochar.sk/pozvanky/2026/vystup-na-tribec/delete/yes\">. The browser sends the session cookie and the invitation is deleted without any interaction. Link prefetchers and crawlers that follow links while authenticated can delete content too. Use POST with isCsrfTokenValid()."
  },
  {
    "file": "src/Repository/BlogRepository.php",
    "line": 103,
    "summary": "getPreparedByYearStartDate() calls ->format() on blog['startDate'], which is nullable (BlogType startDate is 'required' => false).",
    "failure_scenario": "An admin creates a 'viacdnove-akcie' blog and leaves 'Začiatok' empty. From then on, /blog/viacdnove-akcie fails with 'Call to a member function format() on null' (500) for every visitor. The template even checks `year[0].startDate is null`, so null was expected."
  },
  {
    "file": "templates/event_plan/showListByYear.html.twig",
    "line": 32,
    "summary": "The plan builds the multi-day blog URL with event.startDate's year, but findBySectionYearSlug() matches on the blog's createdAt year.",
    "failure_scenario": "A blog for a January 2027 multi-day trip is written in December 2026 (createdAt 2026). The plan for 2027 links to /blog/viacdnove-akcie/2027/<slug>, which returns 404. In the same family, home_page/index.html.twig:66 links the 'viacdnove-akcie' blog (latestBlogSectionId2) with blogSectionSlug 'z-klubovej-kuchyne'. That link only works because of the missing section filter above, and it renders the blog under the wrong section."
  },
  {
    "file": "templates/security/login.html.twig",
    "line": 13,
    "summary": "The template calls path('app_logout'), but the logout route is named 'logout' (SecurityController / security.yaml).",
    "failure_scenario": "A logged-in user opens /login (for example from a bookmark or after a redirect). Twig throws RouteNotFoundException ('Unable to generate a URL for the named route \"app_logout\"') and the page returns 500."
  },
  {
    "file": "src/Utils/SecondLevelCachePDO.php",
    "line": 16,
    "summary": "The cache adapter connects with the raw $_ENV['DATABASE_URL'] and so ignores Doctrine's when@test dbname_suffix '_test'. Test runs read and write the 'home-page' and 'main-menu-data' items in the dev database.",
    "failure_scenario": "The dev site caches the menu built from dev data. Running bin/phpunit then serves that dev-DB menu and home page to the functional tests, which assert against test fixtures. Tests fail or pass depending on whatever is in the dev cache, and test runs overwrite or clear the dev cache. Inject the Doctrine DBAL Connection (or a cache pool service) instead of building a second connection from the env var."
  },
  {
    "file": "src/Controller/EventInvitationController.php",
    "line": 164,
    "summary": "Events are looked up with 'eventInvitation' => null, so the following `if ($firstEvent->getEventInvitation() !== null)` route-copy block (lines 176-180) is dead code, and routes are never pre-filled from the parent Event. EventChronicleController:152 has the same bug.",
    "failure_scenario": "An admin creates an invitation for a date that has a planned Event. Title, dates and sport types are pre-filled, but the route rows are always empty, contrary to the documented flow. The admin must retype the routes, and route data for the event becomes duplicated or out of sync."
  },
  {
    "file": "src/Controller/EventInvitationController.php",
    "line": 203,
    "summary": "Slugs are regenerated from the title with no uniqueness check (same for chronicles and blogs), and findByYearSlug() uses setMaxResults(1), so content with the same title in the same year collides.",
    "failure_scenario": "The club posts 'Silvestrovská túra' as two invitations in 2026 (for example a repeated or split event). Both get the slug 'Silvestrovska-tura'. The second can never be shown, and its edit and delete links (which use year+slug) act on the first one, so an admin trying to delete the duplicate deletes the original."
  }
]
```

## Lower-priority notes (outside the 10-finding cap)

- `src/Menu/Builder.php:227,245`: `$cachedData['Plán']` and `$cachedData[$blogSection[0]]` are undefined when there are no events or no blogs in a section. That raises an undefined-key warning and a `foreach` over null on every page. `Builder.php:305` also dereferences `findBySlug()` without a null check.
- `src/Controller/GpxController.php:17` and `src/Service/Gpx.php:69`: `/gpx/{id}` for a route with `gpx = null` raises a TypeError (500), and a GPX file with no `<trk>` fails on `tracks[0]`.
- Delete redirects (`invitation_list_by_Year`, `chronicle_list_by_Year`, `blog_list_by_BlogSectionSlug`) return 404 after the last item in that year or section is deleted, and an empty blog section hides its "Pridať nový článok" button behind a 404.
- `BlogController.php:83,121,128,216,224,240` hard-codes section id `2`, while the home page and templates identify that section by the slug `viacdnove-akcie`. In `createBlog`, the `$originalSportTypes` snapshot of a brand-new Blog is always empty, so it is dead code.
- `EventInvitationController` and `EventChronicleController` are near copies (create, edit, delete, orphan cleanup). Shared helpers for the "snapshot and remove orphans" logic and the slug/timestamp setup would remove about 300 duplicated lines.
