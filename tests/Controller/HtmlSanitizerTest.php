<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Blog;
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

/** Article HTML is cleaned by app.article_sanitizer: on render for content already in the database, and on save */
class HtmlSanitizerTest extends WebTestCase
{
    private const string DIRTY = '<p style="text-align:center">Ahoj <strong>turisti</strong></p>'
        . '<img src="x" onerror="alert(1)">'
        . '<script>alert(2)</script>'
        . '<a href="javascript:alert(3)">odkaz</a>'
        . '<table><tbody><tr><td>Trasa</td></tr></tbody></table>'
        . '<iframe src="https://example.com"></iframe>';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testInvitationIsSanitizedOnRender(): void
    {
        $invitation = new EventInvitation()
            ->setTitle('Pozvánka so skriptom')
            ->setSlug(new UnicodeString('pozvanka-so-skriptom'))
            ->setSummary('Skript')
            ->setContent(self::DIRTY)
            ->setStartDate(new DateTimeImmutable('2009-05-01 08:00:00'))
            ->setCreatedAt(new DateTimeImmutable())
            ->setCreatedBy($this->admin());

        $this->assertSanitizedOnRender($invitation, '/pozvanky/2009/pozvanka-so-skriptom');
    }

    public function testChronicleIsSanitizedOnRender(): void
    {
        $chronicle = new EventChronicle()
            ->setTitle('Kronika so skriptom')
            ->setSlug(new UnicodeString('kronika-so-skriptom'))
            ->setSummary('Skript')
            ->setContent(self::DIRTY)
            ->setStartDate(new DateTimeImmutable('2009-05-01 08:00:00'))
            ->setCreatedAt(new DateTimeImmutable())
            ->setCreatedBy($this->admin());

        $this->assertSanitizedOnRender($chronicle, '/kronika/2009/kronika-so-skriptom');
    }

    public function testBlogIsSanitizedOnRender(): void
    {
        $section = static::getContainer()->get(BlogSectionRepository::class)->findBySlug('receptury-na-tury');
        $this->assertNotNull($section);
        $blog = new Blog()
            ->setTitle('Článok so skriptom')
            ->setSlug(new UnicodeString('clanok-so-skriptom'))
            ->setSummary('Skript')
            ->setContent(self::DIRTY)
            ->setSection($section)
            ->setCreatedAt(new DateTimeImmutable('2009-05-01 08:00:00'))
            ->setCreatedBy($this->admin());

        $this->assertSanitizedOnRender($blog, '/blog/receptury-na-tury/2009/clanok-so-skriptom');
    }

    /** The edit form stores the cleaned HTML */
    public function testInvitationIsSanitizedOnSave(): void
    {
        $invitation = new EventInvitation()
            ->setTitle('Pozvánka na uloženie')
            ->setSlug(new UnicodeString('pozvanka-na-ulozenie'))
            ->setSummary('Uloženie')
            ->setContent('<p>Čistý text</p>')
            ->setStartDate(new DateTimeImmutable('2009-05-01 08:00:00'))
            ->setCreatedAt(new DateTimeImmutable())
            ->setCreatedBy($this->admin());
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($invitation);
        $entityManager->flush();
        $id = $invitation->getId();

        try {
            $this->client->loginUser($this->admin());
            $crawler = $this->client->request('GET', '/pozvanky/2009/pozvanka-na-ulozenie/edit');
            $this->assertResponseIsSuccessful();
            $form = $crawler->selectButton('Uložiť pozvánku')->form();
            $form[$form->getName() . '[content]'] = self::DIRTY;
            $this->client->submit($form);
            $this->assertResponseRedirects();

            $entityManager->clear();
            $saved = $entityManager->find(EventInvitation::class, $id);
            $this->assertNotNull($saved);
            $this->assertCleanContent($saved->getContent());
        } finally {
            $entityManager->remove($entityManager->getReference(EventInvitation::class, $id));
            $entityManager->flush();
        }
    }

    private function assertSanitizedOnRender(EventInvitation|EventChronicle|Blog $content, string $url): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($content);
        $entityManager->flush();

        try {
            $crawler = $this->client->request('GET', $url);
            $this->assertResponseIsSuccessful();
            $this->assertCleanContent($crawler->filter('.e-content')->html());
        } finally {
            $entityManager->remove($entityManager->getReference($content::class, $content->getId()));
            $entityManager->flush();
        }
    }

    private function assertCleanContent(string $html): void
    {
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('alert(', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('<iframe', $html);
        // formatting that CKEditor produces is kept
        $this->assertStringContainsString('<p style="text-align:center">', $html);
        $this->assertStringContainsString('<strong>turisti</strong>', $html);
        $this->assertStringContainsString('<td>Trasa</td>', $html);
        $this->assertStringContainsString('<img src="x"', $html);
        $this->assertStringContainsString('odkaz', $html);
    }

    private function admin(): User
    {
        $user = static::getContainer()->get(UserRepository::class)->findOneByEmail('john.doe@example.com');
        $this->assertNotNull($user);

        return $user;
    }
}
