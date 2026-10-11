<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Blog;
use App\Entity\Event;
use App\Entity\EventChronicle;
use App\Entity\EventInvitation;
use App\Entity\User;
use App\Repository\BlogSectionRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\String\UnicodeString;

/** Content with publish = false is not shown to visitors; each test checks the published state first, so a 404 means the draft and not a wrong URL */
class UnpublishedContentTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testInvitation(): void
    {
        $invitation = new EventInvitation()
            ->setTitle('Koncept pozvánky')
            ->setSlug(new UnicodeString('koncept-pozvanky'))
            ->setSummary('Koncept')
            ->setContent('<p>Koncept</p>')
            ->setStartDate(new DateTimeImmutable('2009-05-01 08:00:00'))
            ->setCreatedAt(new DateTimeImmutable())
            ->setCreatedBy($this->admin());

        $this->assertHiddenWhenUnpublished($invitation, '/pozvanky/2009/koncept-pozvanky');
    }

    public function testChronicle(): void
    {
        $chronicle = new EventChronicle()
            ->setTitle('Koncept kroniky')
            ->setSlug(new UnicodeString('koncept-kroniky'))
            ->setSummary('Koncept')
            ->setContent('<p>Koncept</p>')
            ->setStartDate(new DateTimeImmutable('2009-05-01 08:00:00'))
            ->setCreatedAt(new DateTimeImmutable())
            ->setCreatedBy($this->admin());

        $this->assertHiddenWhenUnpublished($chronicle, '/kronika/2009/koncept-kroniky');
    }

    public function testBlog(): void
    {
        $section = static::getContainer()->get(BlogSectionRepository::class)->findBySlug('receptury-na-tury');
        $this->assertNotNull($section);
        $blog = new Blog()
            ->setTitle('Koncept článku')
            ->setSlug(new UnicodeString('koncept-clanku'))
            ->setSummary('Koncept')
            ->setContent('<p>Koncept</p>')
            ->setSection($section)
            ->setCreatedAt(new DateTimeImmutable('2009-05-01 08:00:00'))
            ->setCreatedBy($this->admin());

        $this->assertHiddenWhenUnpublished($blog, '/blog/receptury-na-tury/2009/koncept-clanku');
    }

    /** A year whose only event is unpublished is left out of the Plan menu */
    public function testPlanMenuYear(): void
    {
        $event = new Event()
            ->setTitle('Koncept plánu')
            ->setStartDate(new DateTimeImmutable('2005-05-01 08:00:00'))
            ->setCreatedAt(new DateTimeImmutable())
            ->setCreatedBy($this->admin());
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($event);
        $entityManager->flush();

        try {
            $this->assertCount(1, $this->planMenuLinks());

            $event->setPublish(false);
            $entityManager->flush();
            $this->assertCount(0, $this->planMenuLinks());
        } finally {
            $this->remove($event);
        }
    }

    /** @return list<string> Links to /plan/2005 in the main menu (the home page has none), with an empty content cache */
    private function planMenuLinks(): array
    {
        static::getContainer()->get('content.cache')->clear();
        $crawler = $this->client->request('GET', '/pozvanky');
        $this->assertResponseIsSuccessful();

        return $crawler->filter('nav a[href="/plan/2005"]')->each(static fn ($link): string => (string) $link->attr('href'));
    }

    private function assertHiddenWhenUnpublished(EventInvitation|EventChronicle|Blog $content, string $url): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($content);
        $entityManager->flush();

        try {
            $this->client->request('GET', $url);
            $this->assertResponseIsSuccessful();

            $content->setPublish(false);
            $entityManager->flush();
            $this->client->request('GET', $url);
            $this->assertResponseStatusCodeSame(404);
        } finally {
            $this->remove($content);
        }
    }

    private function remove(EventInvitation|EventChronicle|Blog|Event $entity): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($entityManager->getReference($entity::class, $entity->getId()));
        $entityManager->flush();
    }

    private function admin(): User
    {
        $user = static::getContainer()->get(UserRepository::class)->findOneByEmail('john.doe@example.com');
        $this->assertNotNull($user);

        return $user;
    }
}
