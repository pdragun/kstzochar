<?php

declare(strict_types=1);

namespace App\Utils;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;

class SecondLevelCachePDO {

    protected static $instance = null;
    private DoctrineDbalAdapter $cache;

    protected function __construct() {
        $this->cache = new DoctrineDbalAdapter($_ENV['DATABASE_URL'], 'app');
    }

    protected function __clone() {
        
    }

    public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
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
