<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Breadcrumbs carry valid schema.org BreadcrumbList microdata */
class BreadcrumbTest extends WebTestCase
{
    public function testBreadcrumbMicrodata(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/plan/2010');

        $this->assertResponseIsSuccessful();
        $items = $crawler->filter('ol.breadcrumb[itemtype="https://schema.org/BreadcrumbList"] > li[itemprop="itemListElement"]');
        $this->assertGreaterThan(1, $items->count());

        foreach ($items as $index => $item) {
            $this->assertSame('https://schema.org/ListItem', $item->getAttribute('itemtype'));
            $listItem = $items->eq($index);
            $this->assertSame((string) ($index + 1), $listItem->filter('meta[itemprop="position"]')->attr('content'));
            $this->assertNotSame('', trim($listItem->filter('[itemprop="name"]')->text()));
        }

        // Every item but the current page links to its page
        $this->assertCount($items->count() - 1, $crawler->filter('ol.breadcrumb a[itemprop="item"][href]'));
        $this->assertSame('page', $items->last()->attr('aria-current'));
    }
}
