<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Deletes must be POST requests with a valid CSRF token */
class DeleteCsrfTest extends WebTestCase
{
    /** Anonymous POST is redirected to the login page */
    #[DataProvider('provideContentUrls')]
    public function testAnonymousPostRequiresLogin(string $url): void
    {
        $client = static::createClient();
        $client->request('POST', $url . '/delete/yes');

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertSelectorTextContains('html h1', 'Prosím, prihlás sa:');
    }

    /** GET can no longer delete (links, prefetchers, <img src>) */
    #[DataProvider('provideContentUrls')]
    public function testAdminGetIsNotAllowed(string $url): void
    {
        $client = $this->createAdminClient();
        $client->request('GET', $url . '/delete/yes');

        $this->assertResponseStatusCodeSame(405);
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }

    /** POST without a token or with a wrong one is rejected and nothing is deleted */
    #[DataProvider('provideContentUrls')]
    public function testAdminPostWithoutValidTokenIsRejected(string $url): void
    {
        $client = $this->createAdminClient();

        $client->request('POST', $url . '/delete/yes');
        $this->assertResponseStatusCodeSame(403);

        $client->request('POST', $url . '/delete/yes', ['_token' => 'invalid']);
        $this->assertResponseStatusCodeSame(403);

        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }

    /** POST to content that does not exist is still 404 */
    public function testAdminPostToMissingContent(): void
    {
        $client = $this->createAdminClient();
        $client->request('POST', '/pozvanky/2011/neexistuje/delete/yes');

        $this->assertResponseStatusCodeSame(404);
    }

    /** The delete button opens the confirmation modal, which uses the Bootstrap 5 data attributes */
    #[DataProvider('provideContentUrls')]
    public function testDeleteModalUsesBootstrap5(string $url): void
    {
        $client = $this->createAdminClient();
        $client->request('GET', $url);

        $this->assertSelectorExists('a[data-bs-toggle="modal"][data-bs-target="#delete"]');
        $this->assertSelectorExists('#delete[aria-labelledby="deleteModalLabel"] #deleteModalLabel');
        $this->assertSelectorCount(2, '#delete [data-bs-dismiss="modal"]'); // the close button and "Nie!"
    }

    /** @return iterable<array{string}> */
    public static function provideContentUrls(): iterable
    {
        yield ['/pozvanky/2011/gulasove-opojenie-v-tesaroch'];
        yield ['/kronika/2010/jaskyne-uhradu'];
        yield ['/blog/receptury-na-tury/2011/cergovske-susienky'];
    }

    private function createAdminClient(): KernelBrowser
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $client->loginUser($userRepository->findOneByEmail('john.doe@example.com'));

        return $client;
    }
}
