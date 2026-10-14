# Simple webpage for local sport club

Custom web page based on framework Symfony for local tourist club "Klub Slovenských Turistov Žochár Topoľčany" or "KST Žochár Topoľčany". Club is in Slovakia - content is only in slovak language.

Live page: https://kst.zochar.sk

Used:
* Backend:
    * ORM (Entity)
    * Controller (Routes in annotations)
    * Forms (Embed a Collection of Forms)
    * Second level cache (PDO)
    * Twig
    * KnpMenuBundle (menu and breadcrumbs)
* Frontend:
    * Webpack
    * Bootstrap
    * CKEditor
    * @a2lix/symfony-collection
* Tests
    * DataFixtures
    * Functional tests (for user & admin roles)

## Local development (Docker)

The stack is based on [dunglas/symfony-docker](https://github.com/dunglas/symfony-docker):
FrankenPHP (PHP 8.5) + MySQL 8.4 + Node (Webpack Encore watcher). It is driven by [Task](https://taskfile.dev);
PHP, Composer and Node run in the containers, never on the host (`task --list` shows every task, AGENTS.md →
"Local development" explains them).

```bash
task up      # build and start php, database, node: https://localhost:18443 (accept the self-signed certificate)
             # the php container runs the migrations on the dev DB when it starts
task dbr     # load the fixtures into a fresh dev schema (asks first)
task test    # reset the test DB (migrations + fixtures), then run phpunit
task check   # phpstan + rector dry run + tests

# after changing an entity: generate a migration, review it, run it
task dmd
task dmm

task logs -- node   # Encore rebuilds public/build/ on change
```

Ports 18080/18443/3311 already taken? e.g. `HTTP_PORT=28080 HTTPS_PORT=28443 HTTP3_PORT=28443 DATABASE_PORT=3312 task up`.

MySQL is published on `127.0.0.1:3311` (`DATABASE_PORT`), user `app`, password `!ChangeMe!`, database `kstzochar` (+ `kstzochar_test`).

The containers run as your user, so the files they create are yours; `task fix-perms` repairs files left by an
older stack that ran as root.

Production image: `docker compose -f compose.yaml -f compose.prod.yaml build`.

## Production

Production runs on shared hosting, without Docker (the Docker setup is only for local development). After uploading a release, apply new migrations:

- With a shell: `APP_ENV=prod php bin/console doctrine:migrations:migrate --no-interaction`.
- Without one (phpMyAdmin): generate the SQL locally with `bin/console doctrine:migrations:migrate --write-sql=prod.sql` against a copy of the production database, run it, and record each applied migration, e.g. `INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES ('DoctrineMigrations\\Version20261008190100', NOW(), 0);`.

Google Analytics only runs where `GA_MEASUREMENT_ID` is set (a GA4 ID like `G-XXXXXXXXXX`). It is empty in `.env`, so dev and test send nothing; on production set it in `.env.local` next to `DATABASE_URL` and `APP_SECRET`, otherwise the site runs without tracking. With an ID set, a cookie banner asks for consent first, and Google Analytics is loaded only after the visitor accepts (`assets/js/cookieConsent.js`); `/cookies` lists the cookies the site uses.

The baseline migration `Version20000101000000` contains the schema from before migrations were introduced; on a database that already has it (production), it changes nothing and is only recorded.
