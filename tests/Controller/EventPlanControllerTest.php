<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventPlanControllerTest extends WebTestCase
{
    public function testShowAllYears(): void
    {
        $client = static::createClient();
        $client->request('GET', '/plan');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Ročné plány');
    }

    public function testShowListInvitationPostInYear(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/plan/2010');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Plán podujatí na rok 2010');

        //Test planned events + sport types
        $this->assertEquals('2. 1.', $crawler->filterXPath('//*[@id="event-plan"]/tbody/tr[2]/td[1]')->text());
        $this->assertEquals('Zimný výstup na Úhrad', $crawler->filterXPath('//*[@id="event-plan"]/tbody/tr[2]/td[2]')->text());
        $this->assertEquals('P, L', $crawler->filterXPath('//*[@id="event-plan"]/tbody/tr[2]/td[3]')->text());

        $this->assertEquals('9. 1.', $crawler->filterXPath('//*[@id="event-plan"]/tbody/tr[3]/td[1]')->text());
        $this->assertEquals('Zimný výstup na Javorový vrch', $crawler->filterXPath('//*[@id="event-plan"]/tbody/tr[3]/td[2]')->text());
        $this->assertEquals('BUS', $crawler->filterXPath('//*[@id="event-plan"]/tbody/tr[3]/td[3]')->text());

        $this->assertEquals('23. 1.', $crawler->filterXPath('//*[@id="event-plan"]/tbody/tr[4]/td[1]')->text());
        $this->assertEquals('Výjazd za snehom', $crawler->filterXPath('//*[@id="event-plan"]/tbody/tr[4]/td[2]')->text());
        $this->assertEquals('Z, V', $crawler->filterXPath('//*[@id="event-plan"]/tbody/tr[4]/td[3]')->text());
        
    }

    /** Plan links to the blog under the year the blog was created, not the year of the event */
    public function testLinkToBlogFromPlan(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/plan/2012');

        $this->assertResponseIsSuccessful();
        $link = $crawler->selectLink('Zimný prechod Malej Fatry')->link();
        $this->assertStringEndsWith('/blog/viacdnove-akcie/2011/zimny-prechod-malej-fatry', $link->getUri());

        $client->click($link);
        $this->assertResponseIsSuccessful();
    }

    /** /plan offers the latest year with a published event */
    public function testLatestPlanYear(): void
    {
        $client = static::createClient();
        $client->request('GET', '/plan');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a[href="/plan/2012"]');
    }

    #[DataProvider('provide404Urls')]
    public function test404(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    /** @return list<array{string}> */
    public static function provide404Urls(): array
    {
        return [
            ['/pla'],
            ['/plana'],
            ['/plan/2000'],
            ['/plan/3000'],
            ['/plan/asdf'],
            ['/plan/asdf/asdf'],
        ];
    }
}
