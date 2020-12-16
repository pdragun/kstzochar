<?php declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventPlanControllerTest extends WebTestCase
{
    public function testShowAllYears()
    {
        $client = static::createClient();
        $client->request('GET', '/plan');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Ročné plány');
    }
    

    public function testShowListInvitationPostInYear()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/plan/2010');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Plán podujatí na rok 2010');

        //Test planned events + sport types
        $this->assertEquals('2. 1.', $crawler->filterXPath('//*[@id="event-plan"]/tr[2]/td[1]')->text());
        $this->assertEquals('Zimný výstup na Úhrad', $crawler->filterXPath('//*[@id="event-plan"]/tr[2]/td[2]')->text());
        $this->assertEquals('P, L', $crawler->filterXPath('//*[@id="event-plan"]/tr[2]/td[3]')->text());

        $this->assertEquals('9. 1.', $crawler->filterXPath('//*[@id="event-plan"]/tr[3]/td[1]')->text());
        $this->assertEquals('Zimný výstup na Javorový vrch', $crawler->filterXPath('//*[@id="event-plan"]/tr[3]/td[2]')->text());
        $this->assertEquals('BUS', $crawler->filterXPath('//*[@id="event-plan"]/tr[3]/td[3]')->text());

        $this->assertEquals('23. 1.', $crawler->filterXPath('//*[@id="event-plan"]/tr[4]/td[1]')->text());
        $this->assertEquals('Výjazd za snehom', $crawler->filterXPath('//*[@id="event-plan"]/tr[4]/td[2]')->text());
        $this->assertEquals('Z, V', $crawler->filterXPath('//*[@id="event-plan"]/tr[4]/td[3]')->text());
        
    }


    /**
     * @dataProvider provide404Urls
     * 
     * @param string $url Link to test
     */
    public function test404(string $url)
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }


    /**
     * Get list of links
     * 
     * @return array List of links to check
     */
    public function provide404Urls()
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