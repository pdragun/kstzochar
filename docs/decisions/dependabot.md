# Decision: what Dependabot updates on its own (2026-11-23)

Status: **decided**. Config: `.github/dependabot.yml` (PR #76).

Dependabot opens version-update pull requests for Composer and npm (weekly) and for GitHub Actions and the Docker images (monthly), grouped to keep their number down. Some upgrades are held back on purpose and done by hand:

- **Symfony 8.2 and later** (`symfony/*` with `versions: [">=8.2"]`). `extra.symfony.require` pins 8.1, but Dependabot runs Composer without plugins, so Flex doesn't apply it; and an upgrade needs the Flex recipe updates (`composer recipes:update`) and a look at the deprecations. Patches within 8.1 still come, and the independently versioned `symfony/*` packages (flex, maker-bundle, monolog-bundle, webpack-encore-bundle, apache-pack) are below 8.2, so they still get minor and major updates. **On each manual Symfony upgrade, move the bound to the next minor** (`>=8.3` after moving to 8.2).
- **Debian and frankenphp majors**: the prod stage's `debian` image gets the frankenphp binary, the PHP extensions and their libraries from the `dunglas/frankenphp` builder, so both must be on the same Debian release and move together.
- **Node majors** (Dockerfile `node_upstream` and the `node` service in `compose.override.yaml`): move both to the next LTS release together.
- **0.x npm packages** (`@a2lix/symfony-collection`, `regenerator-runtime`) come as pull requests of their own, not in the grouped minor/patch one, since a 0.x minor can break.

## What the Docker entries actually do

The images use short tags (`node:24-slim`, `debian:13-slim`, `dunglas/frankenphp:1-php8.5`, `mysql:${MYSQL_VERSION:-8.4}`), and Dependabot keeps a tag's precision, so with the majors held back it will rarely open a Docker pull request. Patch releases behind the same tag come with `task update` (pull and rebuild). The entries stay so a pinned, more precise tag would get updates.

## Checking an update pull request

CI (`task check`) builds the dev image and runs the PHP checks and tests. It doesn't build the `frankenphp_prod` / `assets_builder` stages or the production assets, so before merging a Docker or npm update, build them locally: `task build` and `docker build --target frankenphp_prod .`.

In a grouped Symfony patch pull request, check the `composer.lock` diff: no `symfony/*` component on the framework's line may move to 8.2 (Composer runs without Flex there, and the ignore covers the packages Dependabot updates, not every indirect one it pulls along).

## webpack held below 5.112 (2026-11-23)

webpack 5.112 removed `lib/dependencies/LoaderDependency`, which the copy-files-loader of `@symfony/webpack-encore` 4 requires, so `task build` fails with `Cannot find module 'webpack/lib/dependencies/LoaderDependency'`. `package.json` pins `webpack` to `~5.111.1`, and Dependabot ignores `>=5.112.0`. Both go with the upgrade to Encore 7, which also clears most npm advisories left open (webpack-dev-server, webpack-dev-middleware, serialize-javascript, sockjs, css-minimizer-webpack-plugin). `node-notifier` and its `uuid` come from `webpack-notifier`, a direct devDependency used by `.enableBuildNotifications()` in `webpack.config.js`, not from Encore: clearing them needs `webpack-notifier` removed (the node container can't show desktop notifications anyway) or replaced.
