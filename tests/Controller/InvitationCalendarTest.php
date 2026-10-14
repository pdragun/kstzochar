<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\EventInvitation;
use App\Entity\User;
use App\Repository\EventInvitationRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\String\UnicodeString;

/** The .ics export of an invitation and the calendar feed of upcoming invitations */
class InvitationCalendarTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testSingleInvitation(): void
    {
        $invitation = $this->findInvitation(2011, 'gulasove-opojenie-v-tesaroch');

        $this->client->request('GET', '/pozvanky/2011/gulasove-opojenie-v-tesaroch/kalendar.ics');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'text/calendar; charset=utf-8');
        $this->assertResponseHeaderSame('Content-Disposition', 'attachment; filename=Gulasove-opojenie-v-Tesaroch.ics');
        $ics = (string) $this->client->getResponse()->getContent();
        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\n", $ics);
        $this->assertSame(1, substr_count($ics, "BEGIN:VEVENT\r\n"));
        $this->assertStringContainsString("UID:invitation-{$invitation->getId()}@kst.zochar.sk\r\n", $ics);
        $this->assertStringContainsString("SUMMARY:Gulášové opojenie v Tesároch\r\n", $ics);
        // 10:00 in Bratislava summer time
        $this->assertStringContainsString("DTSTART:20110910T080000Z\r\n", $ics);
        $this->assertStringContainsString("DTEND:20110910T160000Z\r\n", $ics);
        $this->assertStringContainsString("LOCATION:", $ics);
    }

    /** @return iterable<string, array{string}> */
    public static function notFoundProvider(): iterable
    {
        yield 'unknown slug' => ['/pozvanky/2011/neexistuje/kalendar.ics'];
        yield 'wrong year' => ['/pozvanky/2012/gulasove-opojenie-v-tesaroch/kalendar.ics'];
    }

    #[DataProvider('notFoundProvider')]
    public function testNotFound(string $url): void
    {
        $this->client->request('GET', $url);

        $this->assertResponseStatusCodeSame(404);
    }

    /** Upcoming invitations are in the feed, see testUnpublishedIsNotExported(); trips older than 90 days are not */
    public function testFeed(): void
    {
        $old = $this->findInvitation(2011, 'gulasove-opojenie-v-tesaroch');

        $ics = $this->feed();

        $this->assertResponseHeaderSame('Content-Type', 'text/calendar; charset=utf-8');
        $this->assertStringStartsWith('inline;', (string) $this->client->getResponse()->headers->get('Content-Disposition'));
        $this->assertStringContainsString('public', (string) $this->client->getResponse()->headers->get('Cache-Control'));
        $this->assertStringContainsString("X-WR-CALNAME:KST Žochár Topoľčany – pozvánky\r\n", $ics);
        $this->assertStringNotContainsString("UID:invitation-{$old->getId()}@kst.zochar.sk\r\n", $ics);
    }

    /** A draft is neither downloadable nor in the feed; checked as published first, so the 404 means the draft */
    public function testUnpublishedIsNotExported(): void
    {
        $invitation = new EventInvitation()
            ->setTitle('Koncept do kalendára')
            ->setSlug(new UnicodeString('koncept-do-kalendara'))
            ->setSummary('Koncept')
            ->setContent('<p>Koncept</p>')
            ->setStartDate(new DateTimeImmutable('tomorrow 09:00'))
            ->setCreatedAt(new DateTimeImmutable())
            ->setCreatedBy($this->admin());
        $url = '/pozvanky/' . $invitation->getStartDate()?->format('Y') . '/koncept-do-kalendara/kalendar.ics';
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($invitation);
        $entityManager->flush();
        $uid = "UID:invitation-{$invitation->getId()}@kst.zochar.sk\r\n";

        try {
            $this->client->request('GET', $url);
            $this->assertResponseIsSuccessful();
            $this->assertStringContainsString($uid, $this->feed());

            // Each request reboots the kernel, which clears the entity manager, so load the draft again
            $entityManager = static::getContainer()->get(EntityManagerInterface::class);
            $entityManager->find(EventInvitation::class, $invitation->getId())?->setPublish(false);
            $entityManager->flush();
            $this->client->request('GET', $url);
            $this->assertResponseStatusCodeSame(404);
            $this->assertStringNotContainsString($uid, $this->feed());
        } finally {
            $entityManager = static::getContainer()->get(EntityManagerInterface::class);
            $entityManager->remove($entityManager->getReference(EventInvitation::class, $invitation->getId()));
            $entityManager->flush();
        }
    }

    public function testLinks(): void
    {
        $crawler = $this->client->request('GET', '/pozvanky/2011/gulasove-opojenie-v-tesaroch');
        $this->assertSame(
            '/pozvanky/2011/Gulasove-opojenie-v-Tesaroch/kalendar.ics',
            $crawler->filter('#add-to-calendar')->attr('href'),
        );

        $crawler = $this->client->request('GET', '/pozvanky/aktualne');
        $this->assertSame('webcal://localhost/pozvanky/kalendar.ics', $crawler->filter('#calendar-feed a')->attr('href'));
        $this->assertSame('http://localhost/pozvanky/kalendar.ics', $crawler->filter('#calendar-feed code')->text());
        $this->assertSame(
            'http://localhost/pozvanky/kalendar.ics',
            $crawler->filter('link[rel="alternate"][type="text/calendar"]')->attr('href'),
        );
    }

    private function feed(): string
    {
        $this->client->request('GET', '/pozvanky/kalendar.ics');
        $this->assertResponseIsSuccessful();

        return (string) $this->client->getResponse()->getContent();
    }

    /** Slugs match case-insensitively, the fixtures store them with capitals */
    private function findInvitation(int $year, string $slug): EventInvitation
    {
        $invitation = static::getContainer()->get(EventInvitationRepository::class)->findByYearSlug($year, $slug);
        $this->assertNotNull($invitation);

        return $invitation;
    }

    private function admin(): User
    {
        $user = static::getContainer()->get(UserRepository::class)->findOneByEmail('john.doe@example.com');
        $this->assertNotNull($user);

        return $user;
    }
}
