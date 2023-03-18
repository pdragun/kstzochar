<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\EventRouteRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GpxControllerTest extends WebTestCase
{
    /** Test gpx file */
    public function testGetGPX(): void
    {
        $client = static::createClient();
        $invitationRouteRepository = static::getContainer()->get(EventRouteRepository::class);
        $invitationRoute = $invitationRouteRepository->findOneBy(['title'=>'Okolie Tesár, športové hry']);

        $client->request('GET', sprintf('/gpx/%d', $invitationRoute->getId()));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        //$this->assertSelectorTextContains('html h1', 'Kontakt');
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
        yield ['/gpp'];
        yield ['/gpxx'];
    }
}
