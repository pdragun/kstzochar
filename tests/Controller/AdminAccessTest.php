<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\LocationRepository;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Every write action needs ROLE_ADMIN: a logged-in member without it gets 403 */
class AdminAccessTest extends WebTestCase
{
    #[DataProvider('provideAdminUrls')]
    public function testMemberIsRefused(string $method, string $url): void
    {
        $client = $this->createClientAs('jana.novakova@example.com');
        $client->request($method, $this->resolve($url));

        $this->assertResponseStatusCodeSame(403);
    }

    /** The same pages work for an admin, so the 403 above comes from the role and not from a wrong URL */
    #[DataProvider('provideAdminGetUrls')]
    public function testAdminIsAllowed(string $url): void
    {
        $client = $this->createClientAs('john.doe@example.com');
        $client->request('GET', $this->resolve($url));

        $this->assertResponseIsSuccessful();
    }

    /** @return iterable<array{string}> Only the GET pages; DeleteCsrfTest and LocationAdminTest cover the POST actions */
    public static function provideAdminGetUrls(): iterable
    {
        foreach (self::provideAdminUrls() as [$method, $url]) {
            if ($method === 'GET') {
                yield [$url];
            }
        }
    }

    /** @return iterable<array{string, string}> Method and URL; %location% is replaced with a fixture location id */
    public static function provideAdminUrls(): iterable
    {
        yield ['GET', '/pozvanky/2010/pridat-novu/add'];
        yield ['GET', '/pozvanky/2010/pridat-novu/2010-01-09/add'];
        yield ['GET', '/pozvanky/2010/Zimny-vystup-na-Javorovy-vrch/edit'];
        yield ['GET', '/pozvanky/2010/Zimny-vystup-na-Javorovy-vrch/delete'];
        yield ['POST', '/pozvanky/2010/Zimny-vystup-na-Javorovy-vrch/delete/yes'];

        yield ['GET', '/kronika/2010/pridat-novu/add'];
        yield ['GET', '/kronika/2010/pridat-novu/2010-03-01/add'];
        yield ['GET', '/kronika/2010/jaskyne-uhradu/edit'];
        yield ['GET', '/kronika/2010/jaskyne-uhradu/delete'];
        yield ['POST', '/kronika/2010/jaskyne-uhradu/delete/yes'];

        yield ['GET', '/blog/receptury-na-tury/pridat-novy/add'];
        yield ['GET', '/blog/receptury-na-tury/2011/cergovske-susienky/edit'];
        yield ['GET', '/blog/receptury-na-tury/2011/cergovske-susienky/delete'];
        yield ['POST', '/blog/receptury-na-tury/2011/cergovske-susienky/delete/yes'];

        yield ['GET', '/miesta-stretnutia'];
        yield ['GET', '/miesta-stretnutia/%location%/edit'];
        yield ['GET', '/miesta-stretnutia/%location%/merge'];
        yield ['POST', '/miesta-stretnutia/%location%/delete'];
    }

    private function createClientAs(string $email): KernelBrowser
    {
        $client = static::createClient();
        $client->loginUser(static::getContainer()->get(UserRepository::class)->findOneByEmail($email));

        return $client;
    }

    private function resolve(string $url): string
    {
        if (!str_contains($url, '%location%')) {
            return $url;
        }
        $location = static::getContainer()->get(LocationRepository::class)->findOneBy([]);
        $this->assertNotNull($location, 'The fixtures have no location');

        return str_replace('%location%', (string) $location->getId(), $url);
    }
}
