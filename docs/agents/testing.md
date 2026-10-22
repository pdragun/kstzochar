# Tests

Read before writing or changing tests. `AGENTS.md` has the rules most changes need.

- **Tests** (`tests/Controller/`) are functional `WebTestCase`s. Data providers are static methods wired with `#[DataProvider]` attributes; `phpunit.xml.dist` fails the run on deprecations, notices and warnings from `src/`. They check status codes (200, 404 via data providers, 302 redirects to login for admin routes) and assert Slovak text from the fixtures using CSS and XPath selectors.
