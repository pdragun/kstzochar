<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\EventInvitation;
use App\Repository\EventInvitationRepository;
use App\Repository\LocationRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\String\UnicodeString;

/** The start location of an invitation: select, "new location" sub-form and h-card on the detail page */
class InvitationLocationTest extends WebTestCase
{
    private const string EDIT_URL = '/pozvanky/2010/Zimny-vystup-na-Javorovy-vrch/edit';
    private const string SHOW_URL = '/pozvanky/2010/Zimny-vystup-na-Javorovy-vrch';

    public function testShowLocation(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/pozvanky/2011/gulasove-opojenie-v-tesaroch');

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            'Miesto stretnutia: Tesáre, futbalové ihrisko (Tesáre, Nitriansky kraj, Slovensko).',
            $crawler->filter('#start-location')->text(),
        );
        $card = $crawler->filter('.h-event #start-location .p-location.h-card');
        $this->assertSame('Tesáre, futbalové ihrisko', $card->filter('.p-name')->text());
        $this->assertSame('Tesáre', $card->filter('.p-locality')->text());
        $this->assertSame('Nitriansky kraj', $card->filter('.p-region')->text());
        $this->assertSame('Slovensko', $card->filter('.p-country-name')->text());
        $this->assertCount(0, $card->filter('.p-street-address, .p-postal-code, .p-latitude, .p-longitude'));

        $crawler = $client->request('GET', self::SHOW_URL);
        $this->assertCount(0, $crawler->filter('#start-location'));
    }

    public function testSelectListsAllLocations(): void
    {
        $client = $this->loggedInClient();
        $crawler = $client->request('GET', self::EDIT_URL);

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            ['— bez miesta —', 'Tesáre, futbalové ihrisko', 'Topoľčany, autobusová stanica'],
            $crawler->filter('select[name="event_invitation[location]"] option')->each(static fn (Crawler $option): string => $option->text()),
        );
        $this->assertSame('', $crawler->filter('select[name="event_invitation[location]"] option')->first()->attr('value'));
        $this->assertCount(1, $crawler->filter('fieldset.new-location input[name="event_invitation[newLocation][name]"]'));
    }

    public function testPickAndRemoveExistingLocation(): void
    {
        $client = $this->loggedInClient();
        $locationCount = $this->locationCount();

        $values = $this->editValues($client);
        $values['event_invitation']['location'] = (string) $this->locationId('Topoľčany, autobusová stanica');
        $client->request('POST', self::EDIT_URL, $values);

        $this->assertResponseRedirects(self::SHOW_URL);
        $crawler = $client->followRedirect();
        $card = $crawler->filter('#start-location .p-location.h-card');
        $this->assertSame('Topoľčany, autobusová stanica', $card->filter('.p-name')->text());
        $this->assertSame('Stummerova', $card->filter('.p-street-address')->text());
        $this->assertSame('955 01', $card->filter('.p-postal-code')->text());
        $this->assertSame('Topoľčany', $card->filter('.p-locality')->text());
        $this->assertSame('Nitriansky kraj', $card->filter('.p-region')->text());
        $this->assertSame('48.5577', $card->filter('data.p-latitude')->attr('value'));
        $this->assertSame('18.1748', $card->filter('data.p-longitude')->attr('value'));
        $this->assertSame($locationCount, $this->locationCount());

        // the edit form shows the picked location, "— bez miesta —" removes it
        $values = $this->editValues($client);
        $this->assertSame((string) $this->locationId('Topoľčany, autobusová stanica'), $values['event_invitation']['location']);
        $values['event_invitation']['location'] = '';
        $client->request('POST', self::EDIT_URL, $values);

        $this->assertResponseRedirects(self::SHOW_URL);
        $crawler = $client->followRedirect();
        $this->assertCount(0, $crawler->filter('#start-location'));
        $this->assertSame($locationCount, $this->locationCount());
    }

    public function testUntouchedSubFormCreatesNoLocation(): void
    {
        $client = $this->loggedInClient();
        $locationCount = $this->locationCount();

        $client->request('POST', self::EDIT_URL, $this->editValues($client));

        $this->assertResponseRedirects(self::SHOW_URL);
        $this->assertSame($locationCount, $this->locationCount());
        $this->assertNull($this->javorovyVrch()->getLocation());
    }

    public function testNewLocationWinsOverSelect(): void
    {
        $client = $this->loggedInClient();
        $locationCount = $this->locationCount();

        $values = $this->editValues($client);
        $values['event_invitation']['location'] = (string) $this->locationId('Tesáre, futbalové ihrisko');
        $values['event_invitation']['newLocation']['name'] = '  Nitrianske   Pravno, parkovisko ';
        $values['event_invitation']['newLocation']['addressLocality'] = 'Nitrianske Pravno';
        $values['event_invitation']['newLocation']['addressCountry'] = 'SK';
        $client->request('POST', self::EDIT_URL, $values);

        $this->assertResponseRedirects(self::SHOW_URL);
        $crawler = $client->followRedirect();
        $this->assertSame(
            'Miesto stretnutia: Nitrianske Pravno, parkovisko (Nitrianske Pravno, Slovensko).',
            $crawler->filter('#start-location')->text(),
        );
        $this->assertSame($locationCount + 1, $this->locationCount());
        $this->assertSame('Nitrianske Pravno, parkovisko', $this->javorovyVrch()->getLocation()?->getName());
    }

    public function testNewLocationWithoutTown(): void
    {
        $client = $this->loggedInClient();
        $locationCount = $this->locationCount();

        $values = $this->editValues($client);
        $values['event_invitation']['newLocation']['name'] = 'Bez obce';
        $crawler = $client->request('POST', self::EDIT_URL, $values);

        $this->assertResponseStatusCodeSame(200); // the form again, not a redirect
        $this->assertCount(1, $crawler->filter('#event_invitation_newLocation_addressLocality.is-invalid'));
        $this->assertSame($locationCount, $this->locationCount());
        $this->assertNull($this->javorovyVrch()->getLocation());
    }

    public function testNewLocationWithExistingName(): void
    {
        $client = $this->loggedInClient();
        $locationCount = $this->locationCount();

        // the column collation ignores case and accents
        $values = $this->editValues($client);
        $values['event_invitation']['newLocation']['name'] = 'topolcany, autobusova stanica';
        $values['event_invitation']['newLocation']['addressLocality'] = 'Topoľčany';
        $crawler = $client->request('POST', self::EDIT_URL, $values);

        $this->assertResponseStatusCodeSame(200); // the form again, not a redirect
        $this->assertCount(1, $crawler->filter('#event_invitation_newLocation_name.is-invalid'));
        $this->assertStringContainsString('Toto miesto už existuje, vyber ho zo zoznamu.', $crawler->filter('fieldset.new-location')->text());
        $this->assertSame($locationCount, $this->locationCount());
    }

    public function testNewLocationWithOneCoordinate(): void
    {
        $client = $this->loggedInClient();
        $locationCount = $this->locationCount();

        $values = $this->editValues($client);
        $values['event_invitation']['newLocation']['name'] = 'Len šírka';
        $values['event_invitation']['newLocation']['addressLocality'] = 'Topoľčany';
        $values['event_invitation']['newLocation']['latitude'] = '48.5';
        $crawler = $client->request('POST', self::EDIT_URL, $values);

        $this->assertResponseStatusCodeSame(200); // the form again, not a redirect
        $this->assertCount(1, $crawler->filter('#event_invitation_newLocation_longitude.is-invalid'));
        $this->assertStringContainsString('Zadaj obe súradnice', $crawler->filter('fieldset.new-location')->text());
        $this->assertSame($locationCount, $this->locationCount());
    }

    public function testDeletingInvitationKeepsLocation(): void
    {
        $client = $this->loggedInClient();
        $container = static::getContainer();
        $user = $container->get(UserRepository::class)->findOneByEmail('john.doe@example.com');
        $invitation = new EventInvitation()
            ->setTitle('Pozvánka na zmazanie')
            ->setSlug(new UnicodeString('pozvanka-na-zmazanie'))
            ->setSummary('Pozvánka na zmazanie')
            ->setContent('<p>Pozvánka na zmazanie.</p>')
            ->setStartDate(new DateTimeImmutable('2012-05-01 08:00:00'))
            ->setCreatedAt(new DateTimeImmutable())
            ->setCreatedBy($user)
            ->setLocation($container->get(LocationRepository::class)->findOneBy(['name' => 'Topoľčany, autobusová stanica']));
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->persist($invitation);
        $entityManager->flush();
        $locationCount = $this->locationCount();

        $crawler = $client->request('GET', '/pozvanky/2012/pozvanka-na-zmazanie');
        $this->assertSame('Topoľčany, autobusová stanica', $crawler->filter('#start-location .p-name')->text());
        $client->submit($crawler->selectButton('Chcem zmazať')->form());

        $this->assertResponseRedirects();
        $entityManager->clear();
        $this->assertNull($container->get(EventInvitationRepository::class)->findOneBy(['slug' => 'pozvanka-na-zmazanie']));
        $this->assertSame($locationCount, $this->locationCount());
    }

    /** Logged-in admin; the Javorový vrch invitation starts without a location */
    private function loggedInClient(): KernelBrowser
    {
        $client = static::createClient();
        $client->loginUser(static::getContainer()->get(UserRepository::class)->findOneByEmail('john.doe@example.com'));
        $this->javorovyVrch()->setLocation(null);
        static::getContainer()->get(EntityManagerInterface::class)->flush();

        return $client;
    }

    /** @return array<string, mixed> values of the edit form of the Javorový vrch invitation */
    private function editValues(KernelBrowser $client): array
    {
        $crawler = $client->request('GET', self::EDIT_URL);
        $this->assertResponseIsSuccessful();

        return $crawler->selectButton('Uložiť pozvánku')->form()->getPhpValues();
    }

    private function locationId(string $name): int
    {
        return (int) static::getContainer()->get(LocationRepository::class)->findOneBy(['name' => $name])?->getId();
    }

    private function locationCount(): int
    {
        return static::getContainer()->get(LocationRepository::class)->count();
    }

    private function javorovyVrch(): EventInvitation
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        return $entityManager->getRepository(EventInvitation::class)->findOneBy(['slug' => 'Zimny-vystup-na-Javorovy-vrch']);
    }
}
