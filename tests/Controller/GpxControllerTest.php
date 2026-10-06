<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\EventRouteRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GpxControllerTest extends WebTestCase
{
    /** Test gpx file */
    public function testGetGPX(): void
    {
        $client = static::createClient();
        $invitationRouteRepository = static::getContainer()->get(EventRouteRepository::class);
        $invitationRoute = $invitationRouteRepository->findOneBy(['title' => 'Okolie Tesár, športové hry']);

        $client->request('GET', sprintf('/gpx/%d', $invitationRoute->getId()));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /** A route without a GPX track has no GPX file */
    public function testRouteWithoutGpx(): void
    {
        $client = static::createClient();
        $route = static::getContainer()->get(EventRouteRepository::class)->findOneBy(['title' => 'Test title']);
        $this->assertNull($route->getGpx());

        $client->request('GET', sprintf('/gpx/%d', $route->getId()));

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    #[DataProvider('provide404Urls')]
    public function test404(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    /** @return iterable<array{string}> */
    public static function provide404Urls(): iterable
    {
        yield ['/gpp'];
        yield ['/gpxx'];
    }
}
