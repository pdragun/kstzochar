<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Log in through the real form_login (CSRF token, password check, throttling), not loginUser() */
class LoginFormTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // failed attempts are counted in the filesystem cache, which survives between test runs
        /** @var CacheItemPoolInterface $limiterCache */
        $limiterCache = static::getContainer()->get('cache.rate_limiter');
        $limiterCache->clear();
    }

    public function testAdminLogsIn(): void
    {
        $this->logIn('john.doe@example.com', 'pass_1234');

        $this->assertResponseRedirects();
        $this->client->request('GET', '/miesta-stretnutia');
        $this->assertResponseIsSuccessful();
    }

    /** A member can log in, but gets no admin pages */
    public function testMemberLogsInWithoutAdminAccess(): void
    {
        $this->logIn('jana.novakova@example.com', 'pass_5678');

        $this->assertResponseRedirects();
        $this->client->request('GET', '/miesta-stretnutia');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testWrongPassword(): void
    {
        $this->logIn('john.doe@example.com', 'wrong');

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'Neplatné prihlasovacie údaje.');
        $this->assertNotLoggedIn();
    }

    public function testInvalidCsrfToken(): void
    {
        $this->logIn('john.doe@example.com', 'pass_1234', 'invalid');

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'Neplatný CSRF token.');
        $this->assertNotLoggedIn();
    }

    /** After 5 failed attempts even the right password is refused */
    public function testThrottling(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->logIn('john.doe@example.com', 'wrong');
        }
        $this->logIn('john.doe@example.com', 'pass_1234');

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'neúspešných pokusov o prihlásenie');
        $this->assertNotLoggedIn();
    }

    private function logIn(string $email, string $password, ?string $csrfToken = null): void
    {
        $crawler = $this->client->request('GET', '/login');
        $values = ['_username' => $email, '_password' => $password];
        if ($csrfToken !== null) {
            $values['_csrf_token'] = $csrfToken;
        }
        $this->client->submit($crawler->selectButton('Prihlásiť sa')->form($values));
    }

    private function assertNotLoggedIn(): void
    {
        $this->client->request('GET', '/miesta-stretnutia');
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorTextContains('html h1', 'Prosím, prihlás sa:');
    }
}
