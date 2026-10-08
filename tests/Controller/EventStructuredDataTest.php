<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\EventInvitation;
use App\Repository\LocationRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\String\UnicodeString;

/** schema.org Event JSON-LD and the h-event microformat on the invitation detail page */
class EventStructuredDataTest extends WebTestCase
{
    public function testEventWithLocation(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/pozvanky/2011/gulasove-opojenie-v-tesaroch');
        $this->assertResponseIsSuccessful();

        $event = $this->eventJsonLd($crawler);
        $this->assertSame('https://schema.org', $event['@context']);
        $this->assertSame('Event', $event['@type']);
        $this->assertSame('Gulášové opojenie v Tesároch', $event['name']);
        $this->assertSame('Turisticko-športový deň 10.9. v Tesároch', $event['description']);
        // the slug's case depends on whether EventInvitationControllerTest re-saved the invitation
        $this->assertEqualsIgnoringCase('http://localhost/pozvanky/2011/gulasove-opojenie-v-tesaroch', $event['url']);
        $this->assertSame('2011-09-10T10:00:00+02:00', $event['startDate']); // summer time
        $this->assertArrayNotHasKey('endDate', $event);
        $this->assertSame('https://schema.org/EventScheduled', $event['eventStatus']);
        $this->assertSame('https://schema.org/OfflineEventAttendanceMode', $event['eventAttendanceMode']);
        $this->assertSame(
            ['@type' => 'Organization', 'name' => 'KST Žochár Topoľčany', 'url' => 'http://localhost/'],
            $event['organizer'],
        );
        $this->assertSame(
            [
                '@type' => 'Place',
                'name' => 'Tesáre, futbalové ihrisko',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => 'Tesáre',
                    'addressRegion' => 'Nitriansky kraj',
                    'addressCountry' => 'SK',
                ],
            ],
            $event['location'],
        );

        $hEvent = $crawler->filter('.h-event');
        $this->assertSame('2011-09-10T10:00:00+02:00', $hEvent->filter('time.dt-start')->attr('datetime'));
        $this->assertCount(0, $hEvent->filter('.dt-end'));
        $this->assertSame($event['url'], $hEvent->filter('data.u-url')->attr('value'));
        $this->assertSame('Turisticko-športový deň 10.9. v Tesároch', $hEvent->filter('data.p-summary')->attr('value'));
        $this->assertCount(1, $hEvent->filter('.p-location.h-card'));
        $this->assertSame('Dátum konania: 10. 9. 2011.', $crawler->filter('#start-date')->text());
    }

    public function testWinterEventWithoutLocation(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/pozvanky/2010/Zimny-vystup-na-Javorovy-vrch');
        $this->assertResponseIsSuccessful();

        $event = $this->eventJsonLd($crawler);
        $this->assertSame('2010-01-09T07:00:00+01:00', $event['startDate']); // winter time
        $this->assertArrayNotHasKey('location', $event);
        $this->assertArrayNotHasKey('endDate', $event);
        $this->assertSame('2010-01-09T07:00:00+01:00', $crawler->filter('.h-event time.dt-start')->attr('datetime'));
    }

    public function testFullAddressEndDateAndEscaping(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $invitation = new EventInvitation()
            ->setTitle('Výlet </script><script>alert(1)</script>')
            ->setSlug(new UnicodeString('vylet-na-dva-dni'))
            ->setSummary('Dva dni <b>v horách</b>')
            ->setContent('<p>Výlet na dva dni.</p>')
            ->setStartDate(new DateTimeImmutable('2012-06-02 08:30:00'))
            ->setEndDate(new DateTimeImmutable('2012-06-03 00:00:00'))
            ->setCreatedAt(new DateTimeImmutable())
            ->setCreatedBy($container->get(UserRepository::class)->findOneByEmail('john.doe@example.com'))
            ->setLocation($container->get(LocationRepository::class)->findOneBy(['name' => 'Topoľčany, autobusová stanica']));
        $entityManager->persist($invitation);
        $entityManager->flush();

        $crawler = $client->request('GET', '/pozvanky/2012/vylet-na-dva-dni');
        $this->assertResponseIsSuccessful();

        // the title can't close the script element
        $this->assertStringNotContainsString('</script><script>alert(1)', (string) $client->getResponse()->getContent());
        $event = $this->eventJsonLd($crawler);
        $this->assertSame('Výlet </script><script>alert(1)</script>', $event['name']);
        $this->assertSame('Dva dni v horách', $event['description']);
        $this->assertSame('2012-06-02T08:30:00+02:00', $event['startDate']);
        $this->assertSame('2012-06-03', $event['endDate']); // 00:00 means no time was set
        $this->assertSame(
            [
                '@type' => 'Place',
                'name' => 'Topoľčany, autobusová stanica',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => 'Stummerova',
                    'addressLocality' => 'Topoľčany',
                    'postalCode' => '955 01',
                    'addressRegion' => 'Nitriansky kraj',
                    'addressCountry' => 'SK',
                ],
                'geo' => ['@type' => 'GeoCoordinates', 'latitude' => 48.5577, 'longitude' => 18.1748],
            ],
            $event['location'],
        );

        $this->assertSame('Dátum konania: 2. 6. 2012 – 3. 6. 2012.', $crawler->filter('#start-date')->text());
        $this->assertSame('2012-06-03', $crawler->filter('.h-event time.dt-end')->attr('datetime'));

        $entityManager->remove($entityManager->find(EventInvitation::class, $invitation->getId()));
        $entityManager->flush();
    }

    /** @return array<string, mixed> the Event JSON-LD in the page head */
    private function eventJsonLd(Crawler $crawler): array
    {
        $scripts = $crawler->filter('head script[type="application/ld+json"]');
        $this->assertCount(1, $scripts);
        $data = json_decode($scripts->text(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);

        return $data;
    }
}
