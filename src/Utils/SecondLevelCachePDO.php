<?php declare(strict_types=1);

namespace App\Utils;

use Symfony\Component\Cache\Adapter\PdoAdapter;


class SecondLevelCachePDO {

    protected static $instance = null;
    private $cache;

    protected function __construct() {
        $this->cache = new PdoAdapter($_ENV['DATABASE_URL'], 'app');
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


    public function getCache() {
        return $this->cache;
    }

    public function clearAllCache() {
        $this->cache->delete('home-page');
        $this->cache->delete('main-menu-data');
    }
}