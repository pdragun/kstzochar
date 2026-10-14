<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** SecurityHeadersListener adds the security headers to every response */
class SecurityHeadersTest extends WebTestCase
{
    /** @return iterable<array{string}> */
    public static function urlProvider(): iterable
    {
        yield ['/'];
        yield ['/pozvanky'];
        yield ['/kronika'];
    }

    #[DataProvider('urlProvider')]
    public function testHeaders(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertResponseHeaderSame('X-Frame-Options', 'DENY');
        $this->assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
        $this->assertResponseHeaderSame('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertResponseHeaderSame('Content-Security-Policy', "frame-ancestors 'none'");
    }

    /** In debug mode (the test env) Symfony's ErrorListener removes the CSP header from error pages, so only the rest is checked there */
    public function testHeadersOnErrorPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/neexistujuca-stranka');

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('X-Frame-Options', 'DENY');
        $this->assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
        $this->assertResponseHeaderSame('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
