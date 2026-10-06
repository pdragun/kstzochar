<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** A new entry with the title of an existing one in the same year (and section) gets its own URL */
class UniqueSlugTest extends WebTestCase
{
    private function createAdminClient(): KernelBrowser
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $client->loginUser($userRepository->findOneByEmail('john.doe@example.com'));

        return $client;
    }

    /**
     * Submit the form of the page with the button, overriding some of its values
     * @param array<string, string> $fields
     */
    private function submitForm(KernelBrowser $client, string $url, string $button, array $fields): void
    {
        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton($button)->form();
        $values = $form->getPhpValues();
        $values[$form->getName()] = array_replace($values[$form->getName()], $fields);
        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
    }

    public function testDuplicateInvitationTitle(): void
    {
        $client = $this->createAdminClient();

        $this->submitForm($client, '/pozvanky/2011/pridat-novu/2011-06-01/add', 'Uložiť pozvánku', [
            'title' => 'Gulášové opojenie v Tesároch',
            'summary' => 'Druhé opojenie',
            'content' => '<p>Druhé opojenie</p>',
        ]);
        $this->assertResponseRedirects('/pozvanky/2011/Gulasove-opojenie-v-Tesaroch-2');

        $crawler = $client->request('GET', '/pozvanky/2011/Gulasove-opojenie-v-Tesaroch');
        $this->assertEquals('Dátum konania: 10. 9. 2011.', $crawler->filterXPath('//*[@id="start-date"]')->text());
        $crawler = $client->request('GET', '/pozvanky/2011/Gulasove-opojenie-v-Tesaroch-2');
        $this->assertEquals('Dátum konania: 1. 6. 2011.', $crawler->filterXPath('//*[@id="start-date"]')->text());

        // saving the copy again keeps its slug instead of colliding with itself
        $this->submitForm($client, '/pozvanky/2011/Gulasove-opojenie-v-Tesaroch-2/edit', 'Uložiť pozvánku', []);
        $this->assertResponseRedirects('/pozvanky/2011/Gulasove-opojenie-v-Tesaroch-2');
    }

    public function testDuplicateChronicleTitle(): void
    {
        $client = $this->createAdminClient();

        $this->submitForm($client, '/kronika/2010/pridat-novu/2010-03-01/add', 'Uložiť kroniku', [
            'title' => 'Jaskyne Úhradu',
            'summary' => 'Druhá návšteva jaskýň',
            'content' => '<p>Druhá návšteva jaskýň</p>',
        ]);
        $this->assertResponseRedirects('/kronika/2010/Jaskyne-Uhradu-2');

        $crawler = $client->request('GET', '/kronika/2010/jaskyne-uhradu');
        $this->assertEquals('2. 1. 2010', $crawler->filterXPath('//*[@id="start-date"]')->text());
        $crawler = $client->request('GET', '/kronika/2010/Jaskyne-Uhradu-2');
        $this->assertEquals('1. 3. 2010', $crawler->filterXPath('//*[@id="start-date"]')->text());
    }

    public function testDuplicateBlogTitle(): void
    {
        $client = $this->createAdminClient();
        $year = new DateTimeImmutable()->format('Y');

        foreach (['Prvý recept', 'Druhý recept'] as $summary) {
            $this->submitForm($client, '/blog/receptury-na-tury/pridat-novy/add', 'Uložiť článok', [
                'title' => 'Rovnaký recept',
                'summary' => $summary,
                'content' => '<p>' . $summary . '</p>',
            ]);
        }
        $this->assertResponseRedirects('/blog/receptury-na-tury/' . $year . '/Rovnaky-recept-2');

        $client->request('GET', '/blog/receptury-na-tury/' . $year . '/Rovnaky-recept');
        $this->assertAnySelectorTextContains('body', 'Prvý recept');
        $client->request('GET', '/blog/receptury-na-tury/' . $year . '/Rovnaky-recept-2');
        $this->assertAnySelectorTextContains('body', 'Druhý recept');
    }
}
