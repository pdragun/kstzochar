<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Breadcrumbs carry a schema.org BreadcrumbList as JSON-LD */
class BreadcrumbTest extends WebTestCase
{
    public function testBreadcrumbJsonLd(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/plan/2010');

        $this->assertResponseIsSuccessful();
        $crumbs = $crawler->filter('ol.breadcrumb > li.breadcrumb-item');
        $this->assertGreaterThan(1, $crumbs->count());
        $this->assertSame('page', $crumbs->last()->attr('aria-current'));
        // No leftover microdata next to the JSON-LD
        $this->assertCount(0, $crawler->filter('ol.breadcrumb [itemscope], ol.breadcrumb[itemscope]'));

        $scripts = $crawler->filter('script[type="application/ld+json"]');
        $this->assertCount(1, $scripts);
        $data = json_decode($scripts->text(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('https://schema.org', $data['@context']);
        $this->assertSame('BreadcrumbList', $data['@type']);
        $this->assertCount($crumbs->count(), $data['itemListElement']);

        foreach ($data['itemListElement'] as $index => $item) {
            $this->assertSame('ListItem', $item['@type']);
            $this->assertSame($index + 1, $item['position']);
            $this->assertSame(trim($crumbs->eq($index)->text()), $item['name']);
        }

        // Crumbs that link to a page give its absolute URL
        $links = $crumbs->filter('a');
        for ($index = 0; $index < $links->count(); $index++) {
            $this->assertSame('http://localhost' . $links->eq($index)->attr('href'), $data['itemListElement'][$index]['item']);
        }
        $this->assertSame('http://localhost/plan/2010', array_last($data['itemListElement'])['item']);
    }

    public function testNoBreadcrumbsOnHomePage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('script[type="application/ld+json"]'));
    }
}
