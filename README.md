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

# first run: create schema (there are no migrations) and load fixtures, for dev and test DB
docker compose exec php bin/console doctrine:schema:create
docker compose exec php bin/console doctrine:fixtures:load -n
docker compose exec php bin/console --env=test doctrine:schema:create
docker compose exec php bin/console --env=test doctrine:fixtures:load -n

docker compose exec php bin/phpunit
docker compose exec php vendor/bin/phpstan analyse
docker compose logs -f node  # Encore rebuilds public/build/ on change
```

MySQL is published on host port `3311` (`MYSQL_HOST_PORT`), user `app`, password `!ChangeMe!`, database `kstzochar` (+ `kstzochar_test`).

The php container runs as root, so files it creates on Linux (e.g. by `composer`) are owned by root:
`docker compose exec php chown -R $(id -u):$(id -g) .`

Production image: `docker compose -f compose.yaml -f compose.prod.yaml build`.