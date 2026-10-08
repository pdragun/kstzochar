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
FrankenPHP (PHP 8.5) + MySQL 8.4 + Node (Webpack Encore watcher).

```bash
docker compose build --pull
docker compose up --wait     # https://localhost (accept the self-signed certificate)

# ports 80/443/3311 already taken? e.g. HTTP_PORT=8080 HTTPS_PORT=8443 HTTP3_PORT=8443 docker compose up --wait

# first run: the php container runs the migrations on the dev DB when it starts; load fixtures,
# and migrate and load the test DB
docker compose exec php bin/console doctrine:fixtures:load -n
docker compose exec php bin/console --env=test doctrine:migrations:migrate -n
docker compose exec php bin/console --env=test doctrine:fixtures:load -n

# after changing an entity: generate a migration, review it, run it
docker compose exec php bin/console doctrine:migrations:diff
docker compose exec php bin/console doctrine:migrations:migrate

docker compose exec php bin/phpunit
docker compose exec php vendor/bin/phpstan analyse
docker compose logs -f node  # Encore rebuilds public/build/ on change
```

MySQL is published on host port `3311` (`MYSQL_HOST_PORT`), user `app`, password `!ChangeMe!`, database `kstzochar` (+ `kstzochar_test`).

The php container runs as root, so files it creates on Linux (e.g. by `composer`) are owned by root:
`docker compose exec php chown -R $(id -u):$(id -g) .`

Production image: `docker compose -f compose.yaml -f compose.prod.yaml build`.

## Production

Production runs on shared hosting, without Docker (the Docker setup is only for local development). After uploading a release, apply new migrations:

- With a shell: `APP_ENV=prod php bin/console doctrine:migrations:migrate --no-interaction`.
- Without one (phpMyAdmin): generate the SQL locally with `bin/console doctrine:migrations:migrate --write-sql=prod.sql` against a copy of the production database, run it, and record each applied migration, e.g. `INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES ('DoctrineMigrations\\Version20261008190100', NOW(), 0);`.

Google Analytics only runs where `GA_MEASUREMENT_ID` is set (a GA4 ID like `G-XXXXXXXXXX`). It is empty in `.env`, so dev and test send nothing; on production set it in `.env.local` next to `DATABASE_URL` and `APP_SECRET`, otherwise the site runs without tracking.

The baseline migration `Version20000101000000` contains the schema from before migrations were introduced; on a database that already has it (production), it changes nothing and is only recorded.
