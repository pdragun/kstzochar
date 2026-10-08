<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/** schema.org BlogPosting/Article JSON-LD, the h-entry microformat and the visible byline of blog posts and chronicles */
class ArticleStructuredDataTest extends WebTestCase
{
    private const string HISTORIA_URL = '/blog/z-klubovej-kuchyne/2011/historia-turistiky';

    public function testBlogWithAuthor(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::HISTORIA_URL);
        $this->assertResponseIsSuccessful();

        $article = $this->jsonLd($crawler);
        $this->assertSame('https://schema.org', $article['@context']);
        $this->assertSame('BlogPosting', $article['@type']);
        $this->assertSame('História turistiky', $article['headline']);
        // the slug's case depends on whether BlogControllerTest re-saved the post
        $this->assertEqualsIgnoringCase('http://localhost' . self::HISTORIA_URL, $article['url']);
        $this->assertSame($article['url'], $article['mainEntityOfPage']);
        $this->assertSame(['@type' => 'Person', 'name' => 'Jana Nováková'], $article['author']);
        // publishedAt is null, createdAt 2011-08-23 14:32:50 UTC is 16:32:50 in Bratislava
        $this->assertSame('2011-08-23T16:32:50+02:00', $article['datePublished']);
        $this->assertSame(
            ['@type' => 'Organization', 'name' => 'KST Žochár Topoľčany', 'url' => 'http://localhost/'],
            $article['publisher'],
        );
        $this->assertArrayNotHasKey('articleBody', $article);

        $entry = $crawler->filter('article.h-entry');
        $this->assertSame('História turistiky', $entry->filter('h1.p-name')->text());
        $this->assertSame($article['url'], $entry->filter('data.u-url')->attr('value'));
        $this->assertSame('Autor: Jana Nováková · 23. 8. 2011', $entry->filter('.byline')->text());
        $this->assertSame('Jana Nováková', $entry->filter('.byline .p-author.h-card')->text());
        $this->assertSame('2011-08-23T16:32:50+02:00', $entry->filter('.byline time.dt-published')->attr('datetime'));
        $this->assertCount(1, $entry->filter('div.e-content'));
    }

    public function testBlogFallsBackToCreator(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/blog/viacdnove-akcie/2011/nizke-tatry');
        $this->assertResponseIsSuccessful();

        $this->assertSame(['@type' => 'Person', 'name' => 'John Doe'], $this->jsonLd($crawler)['author']);
        $this->assertSame('Autor: John Doe · 24. 8. 2011', $crawler->filter('article.h-entry .byline')->text());
    }

    public function testChronicle(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/kronika/2010/jaskyne-uhradu');
        $this->assertResponseIsSuccessful();

        $article = $this->jsonLd($crawler);
        $this->assertSame('Article', $article['@type']);
        $this->assertSame('Jaskyne Úhradu', $article['headline']);
        $this->assertEqualsIgnoringCase('http://localhost/kronika/2010/jaskyne-uhradu', $article['url']);
        $this->assertSame(['@type' => 'Person', 'name' => 'Jana Nováková'], $article['author']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d\+0[12]:00$/', $article['datePublished']);

        $entry = $crawler->filter('article.h-entry');
        $this->assertSame('Jaskyne Úhradu', $entry->filter('h1.p-name')->text());
        $this->assertSame('Jana Nováková', $entry->filter('.byline .p-author.h-card')->text());
        $this->assertSame($article['datePublished'], $entry->filter('.byline time.dt-published')->attr('datetime'));
        $this->assertCount(1, $entry->filter('#start-date'));
    }

    public function testAuthorField(): void
    {
        $client = static::createClient();
        $client->loginUser(static::getContainer()->get(UserRepository::class)->findOneByEmail('john.doe@example.com'));

        $crawler = $client->request('GET', '/kronika/2010/jaskyne-uhradu/edit');
        $this->assertSame(
            ['— ten, kto článok vytvoril —', 'Jana Nováková', 'John Doe'],
            $crawler->filter('select[name="event_chronicle[authorBy]"] option')->each(static fn (Crawler $option): string => $option->text()),
        );

        // no author: the byline shows who entered the post
        $crawler = $client->request('GET', self::HISTORIA_URL . '/edit');
        $form = $crawler->selectButton('Uložiť článok')->form();
        $authorField = $form['blog[authorBy]'];
        $janaId = $authorField->getValue();
        $this->assertNotSame('', $janaId);
        $authorField->setValue('');
        $client->submit($form);

        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();
        $this->assertSame('John Doe', $crawler->filter('.byline .p-author')->text());

        // and back
        $crawler = $client->request('GET', self::HISTORIA_URL . '/edit');
        $form = $crawler->selectButton('Uložiť článok')->form();
        $form['blog[authorBy]']->setValue($janaId);
        $client->submit($form);

        $crawler = $client->followRedirect();
        $this->assertSame('Jana Nováková', $crawler->filter('.byline .p-author')->text());
    }

    /** @return array<string, mixed> the article JSON-LD in the page head */
    private function jsonLd(Crawler $crawler): array
    {
        $scripts = $crawler->filter('head script[type="application/ld+json"]');
        $this->assertCount(1, $scripts);
        $data = json_decode($scripts->text(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);

        return $data;
    }
}
