<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\EventInvitation;
use App\Entity\EventRoute;
use App\Repository\EventRouteRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\String\UnicodeString;

class GpxControllerTest extends WebTestCase
{
    /** An uploaded track as a GPS device writes it, with personal data in the metadata and the track */
    private const string RAW_GPX = <<<'GPX_WRAP'
    <?xml version="1.0" encoding="UTF-8"?>
    <gpx version="1.1" creator="Garmin" xmlns="http://www.topografix.com/GPX/1/1">
      <metadata>
        <name>Tajny nazov suboru</name>
        <desc>Tajny popis suboru</desc>
        <author>
          <name>Jozef Turista</name>
          <email id="jozef.turista" domain="example.org"/>
        </author>
      </metadata>
      <trk>
        <name>Jozef Turista (-1)</name>
        <desc>Ranna trasa Jozefa</desc>
        <trkseg>
          <trkpt lat="48.583265" lon="17.849698"><ele>174</ele></trkpt>
          <trkpt lat="48.583267" lon="17.849738"><ele>174.2</ele></trkpt>
        </trkseg>
      </trk>
    </gpx>
    GPX_WRAP;

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

    /** The served file has no metadata, author or track names, but keeps the track points */
    public function testPersonalDataIsRemoved(): void
    {
        $client = static::createClient();
        $route = $this->createRoute();
        $invitation = $this->createInvitation(true, $route);

        try {
            $client->request('GET', sprintf('/gpx/%d', $route->getId()));
            $this->assertResponseIsSuccessful();
            $xml = (string) $client->getResponse()->getContent();

            foreach (['Tajny nazov suboru', 'Tajny popis suboru', 'Jozef', 'jozef.turista', 'example.org'] as $personal) {
                $this->assertStringNotContainsString($personal, $xml);
            }
            $document = new DOMDocument();
            $this->assertTrue($document->loadXML($xml));
            $xpath = new DOMXPath($document);
            $xpath->registerNamespace('g', 'http://www.topografix.com/GPX/1/1');
            $this->assertSame(2.0, $xpath->evaluate('count(//g:trkpt)'));
            $this->assertSame('48.583265', $xpath->evaluate('string(//g:trkpt[1]/@lat)'));
        } finally {
            $this->remove($invitation, $route);
        }
    }

    /** The track of a draft is hidden from visitors, but an admin can download it */
    public function testDraftRoute(): void
    {
        $client = static::createClient();
        $route = $this->createRoute();
        $invitation = $this->createInvitation(false, $route);
        $url = sprintf('/gpx/%d', $route->getId());

        try {
            $client->request('GET', $url);
            $this->assertResponseStatusCodeSame(404);

            $client->loginUser(static::getContainer()->get(UserRepository::class)->findOneByEmail('john.doe@example.com'));
            $client->request('GET', $url);
            $this->assertResponseIsSuccessful();
        } finally {
            $this->remove($invitation, $route);
        }
    }

    /** A route that no content uses any more (removed from a form, or its content was deleted) */
    public function testOrphanedRoute(): void
    {
        $client = static::createClient();
        $route = $this->createRoute();

        try {
            $client->request('GET', sprintf('/gpx/%d', $route->getId()));
            $this->assertResponseStatusCodeSame(404);
        } finally {
            $this->remove($route);
        }
    }

    private function createRoute(): EventRoute
    {
        $route = new EventRoute();
        $route->setTitle('GPX test');
        $route->setLength(1);
        $route->setCreatedAt(new DateTimeImmutable());
        $route->setGpx(self::RAW_GPX);
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($route);
        $entityManager->flush();

        return $route;
    }

    private function createInvitation(bool $publish, EventRoute $route): EventInvitation
    {
        $invitation = new EventInvitation()
            ->setTitle('GPX test')
            ->setSlug(new UnicodeString('gpx-test'))
            ->setSummary('GPX test')
            ->setContent('<p>GPX test</p>')
            ->setStartDate(new DateTimeImmutable('2009-05-01 08:00:00'))
            ->setCreatedAt(new DateTimeImmutable())
            ->setCreatedBy(static::getContainer()->get(UserRepository::class)->findOneByEmail('john.doe@example.com'))
            ->setPublish($publish);
        $route->addEventInvitation($invitation); // updates both sides
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($invitation);
        $entityManager->flush();

        return $invitation;
    }

    private function remove(EventInvitation|EventRoute ...$entities): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        foreach ($entities as $entity) {
            $entityManager->remove($entityManager->getReference($entity::class, $entity->getId()));
        }
        $entityManager->flush();
    }
}
