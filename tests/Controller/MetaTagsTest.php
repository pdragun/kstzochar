<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\EventInvitation;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\String\UnicodeString;

/** Title, description, canonical link, Open Graph tags and the analytics script in the page head */
class MetaTagsTest extends WebTestCase
{
    public function testHomePage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $this->assertSame('Turistický klub z Topoľčian - KST Žochár Topoľčany', $crawler->filter('title')->text(normalizeWhitespace: false));
        $this->assertSame('http://localhost/', $crawler->filter('link[rel="canonical"]')->attr('href'));
        $this->assertSame('http://localhost/', $this->meta($crawler, 'og:url'));
        $this->assertSame('website', $this->meta($crawler, 'og:type'));
        $this->assertSame('Turistický klub z Topoľčian', $this->meta($crawler, 'og:title'));
        $this->assertSame('KST Žochár Topoľčany', $this->meta($crawler, 'og:site_name'));
        $this->assertSame('sk_SK', $this->meta($crawler, 'og:locale'));
        $this->assertSame(
            $crawler->filter('meta[name="description"]')->attr('content'),
            $this->meta($crawler, 'og:description'),
        );
        // GA_MEASUREMENT_ID is empty outside production
        $this->assertStringNotContainsString('googletagmanager', (string) $client->getResponse()->getContent());
    }

    public function testListingIgnoresQueryString(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/pozvanky?utm_source=facebook');
        $this->assertResponseIsSuccessful();

        $this->assertSame('Pozvánky - KST Žochár Topoľčany', $crawler->filter('title')->text());
        $this->assertSame('Pozvánky', $this->meta($crawler, 'og:title'));
        $this->assertSame('http://localhost/pozvanky', $crawler->filter('link[rel="canonical"]')->attr('href'));
    }

    public function testBlogIndexHasOwnDescription(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/blog');
        $this->assertResponseIsSuccessful();

        $this->assertSame('Blog - KST Žochár Topoľčany', $crawler->filter('title')->text());
        $this->assertStringStartsWith('Články o dianí v klube', $crawler->filter('meta[name="description"]')->attr('content'));
    }

    public function testArticle(): void
    {
        $client = static::createClient();
        // slugs match case-insensitively, the canonical link has the entry's own slug
        $crawler = $client->request('GET', '/kronika/2010/JASKYNE-UHRADU');
        $this->assertResponseIsSuccessful();

        $this->assertSame('article', $this->meta($crawler, 'og:type'));
        $this->assertSame('Jaskyne Úhradu - Kronika', $this->meta($crawler, 'og:title'));
        $this->assertSame('Novoročný výstup na Úhrad sme spojili s návštevou jaskýň.', $this->meta($crawler, 'og:description'));
        $canonical = $crawler->filter('link[rel="canonical"]')->attr('href');
        $this->assertEqualsIgnoringCase('http://localhost/kronika/2010/jaskyne-uhradu', $canonical);
        $this->assertStringNotContainsString('JASKYNE', (string) $canonical);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d\+0[12]:00$/',
            (string) $this->meta($crawler, 'article:published_time'),
        );
    }

    public function testEscaping(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $invitation = new EventInvitation()
            ->setTitle('Čaj & "káva" <b>')
            ->setSlug(new UnicodeString('caj-a-kava'))
            ->setSummary('Pozor na "úvodzovky" & <tagy>')
            ->setContent('<p>Obsah</p>')
            ->setStartDate(new DateTimeImmutable('2012-06-02 08:30:00'))
            ->setCreatedAt(new DateTimeImmutable())
            ->setCreatedBy($container->get(UserRepository::class)->findOneByEmail('john.doe@example.com'));
        $entityManager->persist($invitation);
        $entityManager->flush();

        $crawler = $client->request('GET', '/pozvanky/2012/caj-a-kava');
        $this->assertResponseIsSuccessful();

        // escaped exactly once
        $html = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString('<title>Čaj &amp; &quot;káva&quot; &lt;b&gt; - Pozvánky - KST Žochár Topoľčany</title>', $html);
        $this->assertStringContainsString('<meta property="og:title" content="Čaj &amp; &quot;káva&quot; &lt;b&gt; - Pozvánky">', $html);
        $this->assertSame('Čaj & "káva" <b> - Pozvánky - KST Žochár Topoľčany', $crawler->filter('title')->text());
        $this->assertSame('Čaj & "káva" <b> - Pozvánky', $this->meta($crawler, 'og:title'));
        $this->assertSame('Pozor na "úvodzovky" & <tagy>', $this->meta($crawler, 'og:description'));
        $this->assertSame('Pozor na "úvodzovky" & <tagy>', $crawler->filter('meta[name="description"]')->attr('content'));
        $this->assertSame('http://localhost/pozvanky/2012/caj-a-kava', $crawler->filter('link[rel="canonical"]')->attr('href'));

        $entityManager->remove($entityManager->find(EventInvitation::class, $invitation->getId()));
        $entityManager->flush();
    }

    private function meta(Crawler $crawler, string $property): ?string
    {
        return $crawler->filter(sprintf('meta[property="%s"]', $property))->attr('content');
    }
}
