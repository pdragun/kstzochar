<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    /**
     * Test if login & logout page exist
     * Simple test mainly for http code 200
     */
    public function testLoginLogoutPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Prosím, prihlás sa:');

        $client->request('GET', '/logout');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());

    }

    /**
     * Test wrong links
     */
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
        yield ['/logi'];
        yield ['/logina'];
        yield ['/login/asdf'];
        yield ['/logou'];
        yield ['/logouta'];
    }

    /** Logged-in user sees a logout link on the login page */
    public function testLoginPageWhenLoggedIn(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $client->loginUser($userRepository->findOneByEmail('john.doe@example.com'));

        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSame('/logout', $crawler->filter('a[href="/logout"]')->attr('href'));
    }

    /** Test admin login */
    public function testLogIn(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        $testUser = $userRepository->findOneByEmail('john.doe@example.com');

        // simulate $testUser being logged in
        $client->loginUser($testUser);

        // test e.g. the profile page
        $client->request('GET', '/kronika/2010/jaskyne-uhradu/edit');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Upraviť túto kroniku');
    }
}
