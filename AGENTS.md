# AGENTS.md

Guidance for coding agents (Claude Code, Codex, Cursor, Copilot, Gemini CLI, …) working in this repository. Claude Code reads it through `CLAUDE.md` and Gemini CLI through `GEMINI.md`, which import it; Codex, Cursor, Copilot and most other agents read `AGENTS.md` directly. Keep project knowledge in this file, not in the agent-specific ones.

## Project

Symfony 8.1 (Doctrine ORM 3, DBAL 4; PHP >= 8.5) website for the Slovak hiking club "KST Žochár Topoľčany" (live: https://kst.zochar.sk). All user-facing content, URLs and route paths are in Slovak (e.g. `/pozvanky`, `/kronika`, `pridat-novu`).

## Local development

The local stack is Docker Compose (FrankenPHP + PHP 8.5, a database, project services), driven by [Task](https://taskfile.dev): `Taskfile.yaml` holds this project's tasks and includes `taskfiles/common.yaml`, the general stack tasks. `task --list` shows them all. File ownership, startup, logs, configuration and permissions: `docs/agents/local-development.md`.

- **Always go through `task`** for anything that runs project code or touches the stack: Docker, PHP, Composer, `bin/console`, PHPUnit, php-cs-fixer, phpstan, rector, npm and Node. Never run `php`, `composer`, `bin/console`, `vendor/bin/*`, `npm`, `node` or `symfony` on the host: the host's PHP and Node differ from the containers' and don't see their environment. Don't call `docker compose exec` or `docker compose run` yourself either. If no task fits, use `task docker:exec COMMAND="…"` (php container) or `task node:exec COMMAND="…"` (node container), or add a task: general ones to `taskfiles/common.yaml`, project ones to `Taskfile.yaml`, personal ones to the git-ignored `Taskfile.local.yml`.
- Commands shown bare elsewhere in this file or in the project's docs mean their task form: `bin/console about` is `task console -- about`, `composer require foo/bar` is `task composer -- require foo/bar`, `bin/phpunit --filter testFoo` is `task test -- --filter testFoo`, `npm run build` is `task npm -- run build`.
- Reading and editing files, `git`, and `docker compose ps` / `logs` on the host are fine.
- **Start the stack early.** When the task will run project code (tests, checks, `bin/console`, Composer, npm), check `task ps` at the start and, if the php container isn't running, start `task up` in the background while you read the code, so it's ready when you need it. Don't start it for work that only reads or edits files. If a `task` command still reports `service "php" is not running`, run `task up` first. If Docker itself is unavailable or `task up` fails, report it and stop. Don't fall back to the host's PHP, Composer or Node.
- **Run the tests before changing code.** When the task changes code, run `task test` once before your first edit (in the background once the stack is up), so you know which tests already fail. A failure that was there before your change isn't yours to fix; mention it in your report.
- **When something fails**, read `var/log/dev.log` / `var/log/test.log` (or `task logs -- php`) before changing code.
- **Makers** prompt by default and hang a non-interactive shell: pass `--no-interaction` and every argument up front.
- **Secrets** go in `.env.local` (git-ignored) or `task console -- secrets:set`, never in the committed `.env`.

```bash
task up                   # build and start the stack (task down stops it, task dr recreates it)
task update               # pull the newest images for the pinned tags (PHP patch releases), rebuild without cache
task ps / task logs [-- <service>]   # services, health and ports / the last 200 log lines (task follow streams them)
task sh / task dbsh       # shell in the php container / database client as the app user
task console -- <args>    # bin/console (alias: task c)
task composer -- <args>   # composer; task install = composer install, cache clear/warmup, assets:install
task cc                   # clear and warm the dev cache (APP_ENV=test task cc for the test cache)
task test [-- <args>]     # prepare the test database, then phpunit, e.g. task test -- --filter testFoo
task check                # all of this project's checks (code style, static analysis, tests)
task cs / cs:check        # php-cs-fixer fix / dry run, where installed; also task stan, task rector
task dms / dmm / dmd      # migrations status / migrate / diff, where the project uses migrations
task dv [-- --env=test]   # doctrine:schema:validate
task db:grant-test        # grant the app user its <database>_test databases on an existing volume
task npm -- <args>        # npm in the node container; task build = production assets
task fix-perms            # hand root-owned files and Caddy volumes back to your user (no sudo)
```

## Local environment

- Services: `php` (FrankenPHP in classic, non-worker mode, container `kstzochar-php`, workdir `/app`), `database` (MySQL 8.4, `kstzochar-database`), `node` (Webpack Encore: `npm install`, then `npm run watch` into `public/build/`, `kstzochar-node`; it waits for php, because Encore copies the CKEditor files from `vendor/`).
- URL: `https://localhost:18443/` (accept the self-signed certificate; plain HTTP `18080` on 127.0.0.1). Caddy listens on the published HTTPS port itself (`SERVER_NAME` carries it), so its HTTP->HTTPS redirect keeps the port.
- DB: `localhost:3311` from the host (127.0.0.1 only), `database:3306` from the containers; database `kstzochar` (+ `kstzochar_test`), user `app`, password `!ChangeMe!`. In Docker, `DATABASE_URL` comes from `compose.yaml` (real env vars win over `.env*` files).
- Fixtures (`src/DataFixtures`) provide the dev and test data, including the admin and member users the tests log in as (e.g. member `jana.novakova@example.com`).
- `task dbr` drops every table (including `doctrine_migration_versions`), migrates and loads fixtures (asks first; `APP_ENV=test task dbr` for the test database). Recreating the schema also resets auto-increment ids, which the tests depend on.
- Static analysis: `task stan` (phpstan level 6, `phpstan.neon`, with phpstan-doctrine and phpstan-symfony; needs the dev container in `var/cache/dev`), `task rector` / `task rector:check` (`rector.php`). `task check` = stan + rector:check + test.

### Tests and the test database

Tests run in `APP_ENV=test`; Doctrine appends `_test` to the database name (`config/packages/doctrine.yaml`), so they use `kstzochar_test`. The functional tests assert against fixture data and the suite mutates it, so `task test` first resets the test database (`task test:setup`, also `task ts` / `task tdbr`: drop, migrate, fixtures), then runs PHPUnit 13 (`symfony/phpunit-bridge` extension) with `memory_limit=1G`, e.g. `task test -- tests/Controller/BlogControllerTest.php` or `task test -- --filter testShowInvitation`. The MySQL init script `docker/mysql/init/10-test-database.sh` creates `kstzochar_test` and grants the `app` user the `kstzochar\_test%` databases, but only on a fresh data volume: on an older volume, `task db:grant-test` applies the grant once (data is kept).

### Migrations

The schema comes from Doctrine migrations (`migrations/`, namespace `DoctrineMigrations`); locally the php container's entrypoint runs `doctrine:migrations:migrate --all-or-nothing` on start. Schema changes go into a new migration (`task dmd`, then review it, then `task dmm`); keep migrations transactional (the default), because `--all-or-nothing` refuses non-transactional ones. Production runs on shared hosting without Docker (the Docker setup is for local development only), so migrations are run there by hand on deploy (README, "Production"). `Version20000101000000` is a baseline with the schema from before migrations existed: on a database that already has the tables (production, which was created with `doctrine:schema:create`) it runs no SQL and is only recorded as executed (not `skipIf()`, because skipped migrations aren't recorded).

## Architecture

Rules most changes need:

- All user-facing content, URLs and route paths are Slovak.
- Invitations, chronicles and blogs are created, edited and deleted through `App\Service\EventContentEditor` / `BlogEditor` (take the `EventContentSnapshot` before `handleRequest()`); controllers keep only the flash message and the redirect. Slugs come from `App\Service\SlugGenerator`.
- **Any action that creates, edits or deletes content must call `$contentCache->clear()`**, otherwise the menu, home page and sitemap show stale data (the editors do it).
- `startDate`/`endDate` are the Europe/Bratislava wall-clock time the admin typed, deliberately not UTC: don't convert them on save (00:00 means no time). Only `createdAt`, `publishedAt` and `modifiedAt` are UTC. Conversions live in `App\Service\EventTime`.
- Write actions get `#[IsGranted('ROLE_ADMIN')]` and a line in `AdminAccessTest`.
- CKEditor content is sanitized on save and on render: `|sanitize_html('app.article_sanitizer')`, never `|raw`.
- A new cookie goes on `/cookies` (`CookiesController`).
- Tests are functional `WebTestCase`s that assert fixture data; `phpunit.xml.dist` fails the run on deprecations, notices and warnings from `src/`.
- **Menu and breadcrumbs**: KnpMenuBundle builder registered as `app.menu_builder` in `config/services.yaml`, rendered through `templates/extended_knp_menu.html.twig`.
- **Translations**: UI strings are being moved into `translations/messages.sk.yaml` (Slovak only). Prefer adding keys there over hard-coding strings in controllers or templates.
- **Docker**: `Dockerfile` (stages `frankenphp_dev`, `frankenphp_prod`, `assets_builder`), `compose*.yaml`, `frankenphp/` (Caddyfile, php.ini, entrypoint). FrankenPHP runs in classic mode; worker mode has not been tested.
- **Frontend**: Encore entries `app`, `a2lixSfCollection`, `newLocation` (hides the invitation form's "new location" fieldset behind a button), `cookieConsent` (the cookie banner) and `css/app` (SCSS, Bootstrap 5). Encore also copies images, downloads and the CKEditor assets from `vendor/friendsofsymfony/ckeditor-bundle` into `public/build/`.

Read the matching file in `docs/agents/` before working on that area:

- `content.md`: the domain model (events, invitations, chronicles, routes, locations, blogs), event dates and the calendar export, controllers and editors, slugs, the content cache
- `page-head.md`: title, meta tags, canonical URL, Open Graph, Google Analytics and the cookie banner
- `security.md`: sign-in and throttling, admin access, HTML sanitizing, security headers, GPX downloads
- `testing.md`: how the functional tests are written

## Plans and deploy steps

- Plans for larger changes live in `docs/plans/`, one file per plan, with an index in `docs/plans/README.md`. Update the plan and the index in the same pull request as the work (status per item, decisions, follow-ups). Decisions get a file in `docs/decisions/`, review reports go in `docs/reviews/`.
- A change that needs a manual step on production (a migration, an env var, a cache pool to clear, a check after deploy) adds it to `docs/deploy-checklist.md` in the same pull request.

## Shipping changes

To ship a change (branch, commit, plan update, pull request, merge when asked), follow [.claude/skills/ship/SKILL.md](.claude/skills/ship/SKILL.md). Claude Code runs it as `/ship`.
