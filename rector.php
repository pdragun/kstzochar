<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/config/reference.php',
    ])
    ->withCache(__DIR__ . '/var/cache/rector')
    ->withPhpSets()
    ->withComposerBased(doctrine: true, phpunit: true, symfony: true)
    ->withAttributesSets();
