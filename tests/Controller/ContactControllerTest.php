<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContactControllerTest extends WebTestCase
{
    /** Test contact page */
    public function testContactPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/kontakt');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Kontakt');
    }

    /** @dataProvider provide404Urls */
    public function test404(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function provide404Urls(): iterable
    {
        yield ['/kontak'];
        yield ['/kontakta'];
    }
}
