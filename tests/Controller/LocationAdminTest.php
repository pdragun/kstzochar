<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\EventInvitation;
use App\Entity\Location;
use App\Repository\LocationRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\String\UnicodeString;

/** Admin pages for locations: list, edit, merge, delete; the tests create their own locations and leave the fixtures alone */
class LocationAdminTest extends WebTestCase
{
    /** @var list<int> */
    private array $locationIds = [];

    /** @var list<string> */
    private array $invitationSlugs = [];

    /** Remove what the test created, so other tests see only the fixtures */
    protected function tearDown(): void
    {
        $entityManager = $this->entityManager();
        $entityManager->createQuery('DELETE FROM ' . EventInvitation::class . ' i WHERE i.slug IN (:slugs)')
            ->setParameter('slugs', $this->invitationSlugs ?: [''])
            ->execute();
        $entityManager->createQuery('DELETE FROM ' . Location::class . ' l WHERE l.id IN (:ids)')
            ->setParameter('ids', $this->locationIds ?: [0])
            ->execute();

        parent::tearDown();
    }

    #[DataProvider('provideAdminUrls')]
    public function testAnonymousIsRedirectedToLogin(string $method, string $url): void
    {
        $client = static::createClient();
        $name = sprintf('Anonym, %s %s', $method, $url);
        $id = $this->createLocation($name)->getId();
        $client->request($method, sprintf($url, $id));

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertSelectorTextContains('html h1', 'Prosím, prihlás sa:');
        $this->assertNotNull($this->findLocation($name));
    }

    /** @return iterable<array{string, string}> */
    public static function provideAdminUrls(): iterable
    {
        yield ['GET', '/miesta-stretnutia'];
        yield ['GET', '/miesta-stretnutia/%d/edit'];
        yield ['GET', '/miesta-stretnutia/%d/merge'];
        yield ['POST', '/miesta-stretnutia/%d/delete'];
    }

    public function testMissingLocation(): void
    {
        $client = $this->adminClient();

        foreach (['GET' => ['edit', 'merge'], 'POST' => ['delete']] as $method => $actions) {
            foreach ($actions as $action) {
                $client->request($method, '/miesta-stretnutia/999999/' . $action);
                $this->assertResponseStatusCodeSame(404);
            }
        }
    }

    public function testListAndLinks(): void
    {
        $client = $this->adminClient();
        $crawler = $client->request('GET', '/miesta-stretnutia');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('html h1', 'Miesta stretnutia');
        $rows = $crawler->filter('#locations tbody tr')->each(
            static fn (Crawler $row): array => $row->filter('td')->each(static fn (Crawler $cell): string => $cell->text()),
        );
        $this->assertContains(['Tesáre, futbalové ihrisko', 'Tesáre', '1', 'Upraviť'], $rows);
        $this->assertContains(['Topoľčany, autobusová stanica', 'Topoľčany', '0', 'Upraviť'], $rows);
        $names = array_column($rows, 0);
        $sorted = $names;
        sort($sorted);
        $this->assertSame($sorted, $names);

        // reachable from the home page and from the invitation form
        $crawler = $client->request('GET', '/');
        $this->assertSame('/miesta-stretnutia', $crawler->selectLink('Miesta stretnutia')->attr('href'));
        $crawler = $client->request('GET', '/pozvanky/2010/Zimny-vystup-na-Javorovy-vrch/edit');
        $this->assertSame('/miesta-stretnutia', $crawler->selectLink('Upraviť alebo zlúčiť uložené miesta')->attr('href'));
    }

    public function testEdit(): void
    {
        $client = $this->adminClient();
        $location = $this->createLocation('Úprava, test');
        $this->createInvitation('pozvanka-uprava-miesta', $location);
        $url = sprintf('/miesta-stretnutia/%d/edit', $location->getId());

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
        $this->assertSame('Úprava, test', $crawler->filter('#location_name')->attr('value'));
        $this->assertSame('required', $crawler->filter('#location_name')->attr('required'));
        $this->assertStringContainsString('(1)', $crawler->filter('#location-invitations')->text());
        $this->assertSame('/pozvanky/2012/pozvanka-uprava-miesta', $crawler->filter('#location-invitations a')->attr('href'));
        $this->assertCount(0, $crawler->filter('form[action$="/delete"]')); // used, so it can't be deleted

        $form = $crawler->selectButton('Uložiť miesto')->form([
            'location[name]' => 'Úprava, test opravené',
            'location[postalCode]' => '956 01',
            'location[latitude]' => '48.1',
            'location[longitude]' => '18.2',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/miesta-stretnutia');
        $crawler = $client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Zmeny v mieste „Úprava, test opravené“ boli uložené!');

        // the invitation shows the corrected location
        $crawler = $client->request('GET', '/pozvanky/2012/pozvanka-uprava-miesta');
        $card = $crawler->filter('#start-location .p-location.h-card');
        $this->assertSame('Úprava, test opravené', $card->filter('.p-name')->text());
        $this->assertSame('956 01', $card->filter('.p-postal-code')->text());
        $this->assertSame('48.1', $card->filter('data.p-latitude')->attr('value'));
    }

    public function testEditInvalid(): void
    {
        $client = $this->adminClient();
        $location = $this->createLocation('Neplatné, test');
        $url = sprintf('/miesta-stretnutia/%d/edit', $location->getId());

        // the column collation ignores case and accents
        $crawler = $client->request('GET', $url);
        $crawler = $client->submit($crawler->selectButton('Uložiť miesto')->form([
            'location[name]' => 'topolcany, autobusova stanica',
            'location[addressLocality]' => '',
        ]));

        $this->assertResponseStatusCodeSame(200); // the form again, not a redirect
        $this->assertCount(1, $crawler->filter('#location_name.is-invalid'));
        $this->assertCount(1, $crawler->filter('#location_addressLocality.is-invalid'));
        $this->assertStringContainsString('Miesto s týmto názvom už existuje.', $crawler->text());
        $this->assertSame('Test', $this->findLocation('Neplatné, test')?->getAddressLocality());
    }

    public function testMerge(): void
    {
        $client = $this->adminClient();
        $source = $this->createLocation('Zlúčenie, duplikát');
        $target = $this->createLocation('Zlúčenie, správne');
        $this->createInvitation('pozvanka-zlucenie-1', $source);
        $this->createInvitation('pozvanka-zlucenie-2', $source);
        $url = sprintf('/miesta-stretnutia/%d/merge', $source->getId());

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
        $options = $crawler->filter('#location_merge_target option')->each(static fn (Crawler $option): string => $option->text());
        $this->assertSame('— vyber miesto —', $options[0]);
        $this->assertContains('Zlúčenie, správne', $options);
        $this->assertNotContains('Zlúčenie, duplikát', $options);

        // no target picked
        $crawler = $client->submit($crawler->selectButton('Zlúčiť a zmazať toto miesto')->form());
        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(1, $crawler->filter('#location_merge_target.is-invalid'));
        $this->assertNotNull($this->findLocation('Zlúčenie, duplikát'));

        $client->submit($crawler->selectButton('Zlúčiť a zmazať toto miesto')->form([
            'location_merge[target]' => (string) $target->getId(),
        ]));

        $this->assertResponseRedirects(sprintf('/miesta-stretnutia/%d/edit', $target->getId()));
        $crawler = $client->followRedirect();
        $this->assertSelectorTextContains(
            '.alert-success',
            'Miesto „Zlúčenie, duplikát“ bolo zlúčené s miestom „Zlúčenie, správne“ (presunuté pozvánky: 2).',
        );
        $this->assertCount(2, $crawler->filter('#location-invitations li'));
        $this->assertNull($this->findLocation('Zlúčenie, duplikát'));
        foreach (['pozvanka-zlucenie-1', 'pozvanka-zlucenie-2'] as $slug) {
            $this->assertSame('Zlúčenie, správne', $this->findInvitation($slug)->getLocation()?->getName());
        }
    }

    public function testDeleteUnused(): void
    {
        $client = $this->adminClient();
        $location = $this->createLocation('Zmazanie, nepoužité');

        $crawler = $client->request('GET', sprintf('/miesta-stretnutia/%d/edit', $location->getId()));
        $this->assertStringContainsString('Toto miesto nepoužíva žiadna pozvánka.', $crawler->filter('#location-invitations')->text());
        $client->submit($crawler->selectButton('Chcem zmazať')->form());

        $this->assertResponseRedirects('/miesta-stretnutia');
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Miesto „Zmazanie, nepoužité“ bolo zmazané!');
        $this->assertNull($this->findLocation('Zmazanie, nepoužité'));
    }

    public function testDeleteUsedIsRefused(): void
    {
        $client = $this->adminClient();
        $location = $this->createLocation('Zmazanie, použité');
        $id = $location->getId();

        // the page offers no delete button for a used location, so take the form while it is still unused
        $crawler = $client->request('GET', sprintf('/miesta-stretnutia/%d/edit', $id));
        $form = $crawler->selectButton('Chcem zmazať')->form();
        $this->createInvitation('pozvanka-zmazanie-miesta', $location);
        $client->submit($form);

        $this->assertResponseRedirects(sprintf('/miesta-stretnutia/%d/edit', $id));
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'Miesto „Zmazanie, použité“ používajú pozvánky, preto sa nedá zmazať.');
        $this->assertNotNull($this->findLocation('Zmazanie, použité'));
        $this->assertSame('Zmazanie, použité', $this->findInvitation('pozvanka-zmazanie-miesta')->getLocation()?->getName());
    }

    public function testDeleteWithoutValidToken(): void
    {
        $client = $this->adminClient();
        $id = $this->createLocation('Zmazanie, bez tokenu')->getId();

        $client->request('GET', sprintf('/miesta-stretnutia/%d/delete', $id));
        $this->assertResponseStatusCodeSame(405);
        $client->request('POST', sprintf('/miesta-stretnutia/%d/delete', $id));
        $this->assertResponseStatusCodeSame(403);
        $client->request('POST', sprintf('/miesta-stretnutia/%d/delete', $id), ['_token' => 'invalid']);
        $this->assertResponseStatusCodeSame(403);
        $this->assertNotNull($this->findLocation('Zmazanie, bez tokenu'));
    }

    private function adminClient(): KernelBrowser
    {
        $client = static::createClient();
        $client->loginUser(static::getContainer()->get(UserRepository::class)->findOneByEmail('john.doe@example.com'));

        return $client;
    }

    private function createLocation(string $name): Location
    {
        $location = new Location()
            ->setName($name)
            ->setAddressLocality('Test')
            ->setAddressCountry('SK');
        $entityManager = $this->entityManager();
        $entityManager->persist($location);
        $entityManager->flush();
        $this->locationIds[] = (int) $location->getId();

        return $location;
    }

    private function createInvitation(string $slug, Location $location): void
    {
        $this->invitationSlugs[] = $slug;
        $invitation = new EventInvitation()
            ->setTitle($slug)
            ->setSlug(new UnicodeString($slug))
            ->setSummary($slug)
            ->setContent('<p>' . $slug . '</p>')
            ->setStartDate(new DateTimeImmutable('2012-05-01 08:00:00'))
            ->setCreatedAt(new DateTimeImmutable())
            ->setCreatedBy(static::getContainer()->get(UserRepository::class)->findOneByEmail('john.doe@example.com'))
            // a reference, because $location may be detached by an earlier clear() and the relation cascades persist
            ->setLocation($this->entityManager()->getReference(Location::class, $location->getId()));
        $entityManager = $this->entityManager();
        $entityManager->persist($invitation);
        $entityManager->flush();
        $entityManager->clear(); // the location's invitation collection is loaded fresh on the next request
    }

    private function findLocation(string $name): ?Location
    {
        $this->entityManager()->clear();

        return static::getContainer()->get(LocationRepository::class)->findOneBy(['name' => $name]);
    }

    private function findInvitation(string $slug): EventInvitation
    {
        $this->entityManager()->clear();

        return $this->entityManager()->getRepository(EventInvitation::class)->findOneBy(['slug' => $slug]);
    }

    private function entityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}
