<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\EventInvitation;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMXPath;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\String\UnicodeString;

class SitemapControllerTest extends WebTestCase
{
    private const string BASE = 'http://localhost';

    public function testSitemap(): void
    {
        $client = $this->client();
        $sitemap = $this->sitemap($client);

        $this->assertResponseHeaderSame('Content-Type', 'application/xml; charset=UTF-8');
        foreach ([
            '/',
            '/pozvanky',
            '/pozvanky/aktualne',
            '/kronika',
            '/plan',
            '/blog',
            '/kontakt',
            '/cookies',
            '/pozvanky/2010',
            '/pozvanky/2011',
            '/kronika/2010',
            '/plan/2010',
            '/plan/2012',
            '/blog/z-klubovej-kuchyne',
            '/blog/viacdnove-akcie',
            '/blog/receptury-na-tury',
            '/pozvanky/2010/Zimny-vystup-na-Javorovy-vrch',
        ] as $path) {
            $this->assertArrayHasKey(self::BASE . $path, $sitemap, $path);
        }

        // other tests edit fixture chronicles and blogs, which regenerates their slugs, so only check that there are some
        foreach (['/kronika/2010', '/blog/z-klubovej-kuchyne/2011', '/blog/viacdnove-akcie/2011'] as $prefix) {
            $this->assertNotEmpty(preg_grep('~^' . self::BASE . $prefix . '/[^/]+$~', array_keys($sitemap)), $prefix);
        }

        $this->assertNull($sitemap[self::BASE . '/pozvanky']); // lists have no lastmod

        // no admin, login or GPX pages
        foreach (array_keys($sitemap) as $url) {
            $this->assertDoesNotMatchRegularExpression('~/(edit|delete|add|login|gpx|miesta-stretnutia)\b~', $url);
        }
    }

    /** Every listed page exists, so search engines don't get a 404 from the sitemap */
    public function testEveryUrlIsReachable(): void
    {
        $client = $this->client();

        foreach (array_keys($this->sitemap($client)) as $url) {
            $client->request('GET', $url);
            $this->assertResponseIsSuccessful($url);
        }
    }

    /** lastmod is the modification time, or the creation time of content never edited, in Bratislava time */
    public function testLastmod(): void
    {
        $client = $this->client();
        $created = $this->createInvitation('sitemap-nova', new DateTimeImmutable('2011-09-04 15:55:39'));
        $modified = $this->createInvitation('sitemap-upravena', new DateTimeImmutable('2011-09-04 15:55:39'))
            ->setModifiedAt(new DateTimeImmutable('2012-01-15 10:00:00'));
        static::getContainer()->get(EntityManagerInterface::class)->flush();

        try {
            $sitemap = $this->sitemap($client);
            $this->assertSame('2011-09-04T17:55:39+02:00', $sitemap[self::BASE . '/pozvanky/2009/sitemap-nova'] ?? null);
            $this->assertSame('2012-01-15T11:00:00+01:00', $sitemap[self::BASE . '/pozvanky/2009/sitemap-upravena'] ?? null);
        } finally {
            $this->remove($created, $modified);
        }
    }

    public function testUnpublishedContentIsLeftOut(): void
    {
        $client = $this->client();
        $invitation = $this->createInvitation('nezverejnena-pozvanka', new DateTimeImmutable())->setPublish(false);
        static::getContainer()->get(EntityManagerInterface::class)->flush();

        try {
            $sitemap = $this->sitemap($client);
            $this->assertArrayNotHasKey(self::BASE . '/pozvanky/2009/nezverejnena-pozvanka', $sitemap);
            $this->assertArrayNotHasKey(self::BASE . '/pozvanky/2009', $sitemap); // its year has nothing else
        } finally {
            $this->remove($invitation);
        }
    }

    /** Saving content clears the cached sitemap, so the new lastmod shows at once */
    public function testEditUpdatesLastmod(): void
    {
        $client = $this->client();
        $url = self::BASE . '/pozvanky/2010/Zimny-vystup-na-Javorovy-vrch';
        $before = $this->sitemap($client)[$url];

        $client->loginUser(static::getContainer()->get(UserRepository::class)->findOneByEmail('john.doe@example.com'));
        $crawler = $client->request('GET', '/pozvanky/2010/Zimny-vystup-na-Javorovy-vrch/edit');
        sleep(1); // timestamps have second precision
        $client->submit($crawler->selectButton('Uložiť pozvánku')->form());
        $this->assertResponseRedirects();

        $after = $this->sitemap($client)[$url];
        $this->assertNotNull($after);
        $this->assertGreaterThan(new DateTimeImmutable((string) $before), new DateTimeImmutable($after));
    }

    public function testRobots(): void
    {
        $client = static::createClient();
        $client->request('GET', '/robots.txt');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertStringContainsString("Sitemap: http://localhost/sitemap.xml\n", (string) $client->getResponse()->getContent());
    }

    /** A published invitation from 2009, a year without fixture invitations */
    private function createInvitation(string $slug, DateTimeImmutable $createdAt): EventInvitation
    {
        $invitation = new EventInvitation()
            ->setTitle($slug)
            ->setSlug(new UnicodeString($slug))
            ->setSummary($slug)
            ->setContent('<p>' . $slug . '</p>')
            ->setStartDate(new DateTimeImmutable('2009-05-01 08:00:00'))
            ->setCreatedAt($createdAt)
            ->setCreatedBy(static::getContainer()->get(UserRepository::class)->findOneByEmail('john.doe@example.com'));
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($invitation);
        $entityManager->flush();

        return $invitation;
    }

    private function remove(EventInvitation ...$invitations): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        foreach ($invitations as $invitation) {
            $entityManager->remove($entityManager->getReference(EventInvitation::class, $invitation->getId()));
        }
        $entityManager->flush();
    }

    /** A client with an empty content cache, so the sitemap reflects the database */
    private function client(): KernelBrowser
    {
        $client = static::createClient();
        static::getContainer()->get('content.cache')->clear();

        return $client;
    }

    /** @return array<string, ?string> lastmod (or null) by URL */
    private function sitemap(KernelBrowser $client): array
    {
        $client->request('GET', '/sitemap.xml');
        $this->assertResponseIsSuccessful();

        $document = new DOMDocument();
        $this->assertTrue($document->loadXML((string) $client->getResponse()->getContent()));
        $this->assertSame('http://www.sitemaps.org/schemas/sitemap/0.9', $document->documentElement?->namespaceURI);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $entries = [];
        foreach ($xpath->query('/s:urlset/s:url') ?: [] as $url) {
            $loc = $xpath->evaluate('string(s:loc)', $url);
            $lastmod = $xpath->evaluate('string(s:lastmod)', $url);
            $this->assertArrayNotHasKey($loc, $entries, 'duplicate ' . $loc);
            $entries[$loc] = $lastmod === '' ? null : $lastmod;
        }

        return $entries;
    }
}
