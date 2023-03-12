<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomePageControllerTest extends WebTestCase
{
    /**
     * Test main home page
     * 
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
}
