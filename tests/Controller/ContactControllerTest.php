<?php declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContactControllerTest extends WebTestCase
{
    /**
     * Test contact page
     */
    public function testContactPage()
    {
        $client = static::createClient();
        $client->request('GET', '/kontakt');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Kontakt');
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
            ['/kontak'],
            ['/kontakta'],
        ];
    }
}