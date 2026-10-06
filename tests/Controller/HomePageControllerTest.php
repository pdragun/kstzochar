<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\CacheItem;

class HomePageControllerTest extends WebTestCase
{
    /**
     * Test main home page
     * Simple test mainly for http code 200 and some values
     */
    public function testHomePage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html p.pt.pt-long', 'Klub slovenských turistov Žochár Topoľčany');
        $this->assertSelectorTextContains('html p.hp-header', 'Kronika');
        $this->assertSelectorTextContains('html p.no-bottom.hp-desc', 'Text a fotky z predchádzajúcich akcií');
    }

    /** Home page links to the plan of the latest year with a published event */
    public function testLatestPlanYearLink(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('a[href="/plan/2012"]', '2012');
    }

    /** Cached home page data expires at midnight and is stored in the test database */
    public function testHomePageCache(): void
    {
        $client = static::createClient();
        $contentCache = static::getContainer()->get('content.cache');
        $contentCache->clear();

        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $item = $contentCache->getItem('home-page');
        $this->assertTrue($item->isHit());
        $this->assertSame(
            new DateTimeImmutable('tomorrow')->getTimestamp(),
            (int) $item->getMetadata()[CacheItem::METADATA_EXPIRY],
        );

        $connection = static::getContainer()->get(Connection::class);
        $this->assertStringEndsWith('_test', (string) $connection->getDatabase());
        $this->assertGreaterThan(0, (int) $connection->fetchOne('SELECT COUNT(*) FROM cache_items'));
    }
}
