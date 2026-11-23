# Decision: what Dependabot updates on its own (2026-11-23)

Status: **decided**. Config: `.github/dependabot.yml` (PR #76).

Dependabot opens version-update pull requests for Composer and npm (weekly) and for GitHub Actions and the Docker images (monthly), grouped to keep their number down. Some upgrades are held back on purpose and done by hand:

- **Symfony minor and major upgrades** (8.1 -> 8.2) of the components on the framework's version line (the `8.1.*` ones in `composer.json`). `extra.symfony.require` pins the minor, and an upgrade needs the Flex recipe updates (`composer recipes:update`) and a look at the deprecations. Patches within 8.1 still come. When a component is added at `8.1.*`, add it to the ignore list too.
- **Debian major releases** of the prod stage's `debian` image: it gets the frankenphp binary, the PHP extensions and their libraries from the `dunglas/frankenphp` builder, so both must be on the same Debian release.
- **Node majors** (Dockerfile `node_upstream` and the `node` service in `compose.override.yaml`): move both to the next LTS release together.
- **0.x npm packages** (`@a2lix/symfony-collection`, `regenerator-runtime`) come as pull requests of their own, not in the grouped minor/patch one, since a 0.x minor can break.

## Checking an update pull request

CI (`task check`) builds the dev image and runs the PHP checks and tests. It doesn't build the `frankenphp_prod` / `assets_builder` stages or the production assets, so before merging a Docker or npm update, build them locally: `task build` and `docker build --target frankenphp_prod .`.
