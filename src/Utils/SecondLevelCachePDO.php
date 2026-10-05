<?php

declare(strict_types=1);

namespace App\Utils;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;

final class SecondLevelCachePDO {

    private static ?self $instance = null;
    private DoctrineDbalAdapter $cache;

    private function __construct() {
        $this->cache = new DoctrineDbalAdapter($_ENV['DATABASE_URL'], 'app');
    }

    private function __clone() {}

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function getCache(): DoctrineDbalAdapter
    {
        return $this->cache;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function clearAllCache(): void
    {
        $this->cache->delete('home-page');
        $this->cache->delete('main-menu-data');
    }
}
