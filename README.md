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

Load fixtures `bin/console doctrine:fixtures:load`
Run test `bin/phpunit`