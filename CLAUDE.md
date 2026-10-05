# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Symfony 8.1 (Doctrine ORM 3, DBAL 4; PHP >= 8.5) website for the Slovak hiking club "KST Žochár Topoľčany" (live: https://kst.zochar.sk). All user-facing content, URLs and route paths are in Slovak (e.g. `/pozvanky`, `/kronika`, `pridat-novu`).

## Commands

Local stack is Docker (dunglas/symfony-docker: FrankenPHP + PHP 8.5, MySQL 8.4, Node for Encore), see README. Run PHP commands inside it, e.g. `docker compose exec php bin/phpunit`.

```bash
docker compose up --wait                    # php (https://localhost), database, node (encore watch)

# Backend
composer install
bin/console doctrine:fixtures:load          # load dev/test data (src/DataFixtures)
bin/phpunit                                 # run all tests (PHPUnit 13, symfony/phpunit-bridge extension)
bin/phpunit tests/Controller/BlogControllerTest.php
bin/phpunit --filter testShowInvitation
vendor/bin/phpstan analyse                  # level 6, config in phpstan.neon (with phpstan-doctrine and phpstan-symfony; needs the dev container in var/cache/dev)
vendor/bin/rector process --dry-run         # config in rector.php

# Frontend (Webpack Encore -> public/build/)
npm run dev      # or: npm run watch / npm run dev-server
npm run build    # production
```

Tests run in `APP_ENV=test`; Doctrine appends `_test` to the database name (`config/packages/doctrine.yaml`), and the functional tests assert against fixture data, so load fixtures into the test DB first: `bin/console --env=test doctrine:fixtures:load`. In Docker, `DATABASE_URL` comes from `compose.yaml` (real env vars win over `.env*` files); the MySQL init script `docker/mysql/01-test-database.sh` creates the `_test` database. The schema has to be created with `doctrine:schema:create`. The `migrations/` directory holds no migrations.

## Architecture

- **Domain model**: `Event` (`src/Entity/Event.php`) is the parent "plan" entry for a date. It has optional one-to-one links to an `EventInvitation` (pozvánka, before the event), an `EventChronicle` (kronika, report after the event) and a `Blog`. Invitations and chronicles have many-to-many `SportType`s and `EventRoute`s. Creating an invitation or chronicle is a two-step flow: first pick a date (`SetDateType`), then `.../pridat-novu/{date}/add` looks up an `Event` on that date and pre-fills title, dates, sport types and routes from it.
- **Controllers** (`src/Controller/`) use attribute routes. Content is addressed by `/{section}/{year}/{slug}`. Slugs are regenerated from the title with `AsciiSlugger` on every create/edit. Write actions are guarded with `#[IsGranted('ROLE_ADMIN')]`. Edits of many-to-many collections follow the Symfony "embed a collection of forms" pattern: snapshot the original collections, then remove orphaned links before `flush()`. The front end uses `@a2lix/symfony-collection` (`assets/js/a2lixSfCollection.js`).
- **Caching**: `App\Utils\SecondLevelCachePDO` is a singleton `DoctrineDbalAdapter` that stores cache in the app database. It caches the `home-page` data (`HomePageController`) and `main-menu-data` (`src/Menu/Builder.php`, the KnpMenu main menu with years and items for every section). **Any controller action that creates, edits or deletes content must call `SecondLevelCachePDO::getInstance()->clearAllCache()`**, otherwise the menu and home page show stale data.
- **Menu and breadcrumbs**: KnpMenuBundle builder registered as `app.menu_builder` in `config/services.yaml`, rendered through `templates/extended_knp_menu.html.twig`.
- **Translations**: UI strings are being moved into `translations/messages.sk.yaml` (Slovak only). Prefer adding keys there over hard-coding strings in controllers or templates.
- **GPX**: `App\Service\Gpx` wraps `sibyx/phpgpx` to sanitize uploaded GPX tracks (it resets metadata and author) before `GpxController` serves them.
- **Auth**: built-in `form_login` against `User` by email (`config/packages/security.yaml`, `LoginController`). `ROLE_ADMIN` inherits `ROLE_USER`.
- **Tests** (`tests/Controller/`) are functional `WebTestCase`s. Data providers are static methods wired with `#[DataProvider]` attributes; `phpunit.xml.dist` fails the run on deprecations, notices and warnings from `src/`. They check status codes (200, 404 via data providers, 302 redirects to login for admin routes) and assert Slovak text from the fixtures using CSS and XPath selectors.
- **Docker**: `Dockerfile` (stages `frankenphp_dev`, `frankenphp_prod`, `assets_builder`), `compose*.yaml`, `frankenphp/` (Caddyfile, php.ini, entrypoint). FrankenPHP runs in classic mode, not worker mode, because `SecondLevelCachePDO` keeps static state between requests.
- **Frontend**: Encore entries `app`, `a2lixSfCollection` and `css/app` (SCSS, Bootstrap 5). Encore also copies images, downloads and the CKEditor assets from `vendor/friendsofsymfony/ckeditor-bundle` into `public/build/`.
