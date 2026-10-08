<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** The cookie page and the consent banner, which exists only when Google Analytics is configured */
class CookiesControllerTest extends WebTestCase
{
    /** @var array{0: mixed, 1: mixed}|null GA_MEASUREMENT_ID from $_ENV and $_SERVER before createClientWithGa() */
    private ?array $originalGaId = null;

    protected function tearDown(): void
    {
        if ($this->originalGaId !== null) {
            [$_ENV['GA_MEASUREMENT_ID'], $_SERVER['GA_MEASUREMENT_ID']] = $this->originalGaId;
        }
        parent::tearDown();
    }

    /** A client whose container reads this GA ID (env vars are resolved when a new kernel boots) */
    private function createClientWithGa(string $id): KernelBrowser
    {
        $this->originalGaId = [$_ENV['GA_MEASUREMENT_ID'] ?? '', $_SERVER['GA_MEASUREMENT_ID'] ?? ''];
        $_ENV['GA_MEASUREMENT_ID'] = $_SERVER['GA_MEASUREMENT_ID'] = $id;

        return static::createClient();
    }

    public function testWithoutAnalytics(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/cookies');
        $this->assertResponseIsSuccessful();

        $this->assertSame('Cookies - KST Žochár Topoľčany', $crawler->filter('title')->text());
        $this->assertSelectorTextContains('.wrapper', 'PHPSESSID');
        $this->assertSelectorTextContains('.wrapper', 'Iné cookies web nepoužíva.');
        $this->assertSelectorTextNotContains('.wrapper', '_ga');
        $this->assertSelectorNotExists('#cookie-consent');
        $this->assertSelectorNotExists('[data-cookie-settings]');
        $this->assertSelectorExists('footer a[href="/cookies"]');
        $this->assertStringNotContainsString('cookieConsent.js', (string) $client->getResponse()->getContent());
    }

    public function testWithAnalytics(): void
    {
        $client = $this->createClientWithGa('G-TEST123');
        $crawler = $client->request('GET', '/cookies');
        $this->assertResponseIsSuccessful();

        $banner = $crawler->filter('#cookie-consent');
        $this->assertCount(1, $banner);
        $this->assertSame('G-TEST123', $banner->attr('data-ga-id'));
        // hidden until cookieConsent.js finds no stored choice
        $this->assertNotNull($banner->attr('hidden'));
        $this->assertSame(['granted', 'denied'], $banner->filter('button')->extract(['data-cookie-consent']));
        $this->assertCount(1, $crawler->filter('script[src="/build/cookieConsent.js"]'));
        // Google Analytics is loaded only by the script, after consent
        $this->assertStringNotContainsString('googletagmanager', (string) $client->getResponse()->getContent());

        $this->assertCount(1, $crawler->filter('footer [data-cookie-settings]'));
        $this->assertCount(1, $crawler->filter('.wrapper [data-cookie-settings]'));
        $this->assertSelectorTextContains('.wrapper', 'cookie_consent');
        $this->assertSelectorTextContains('.wrapper', '_ga_TEST123');
    }

    public function testBannerOnPagesThatOverrideJavascripts(): void
    {
        $client = $this->createClientWithGa('G-TEST123');
        $client->loginUser(static::getContainer()->get(UserRepository::class)->findOneByEmail('john.doe@example.com'));
        $crawler = $client->request('GET', '/pozvanky/2010/pridat-novu/2010-01-09/add');
        $this->assertResponseIsSuccessful();

        $this->assertCount(1, $crawler->filter('#cookie-consent'));
        $this->assertCount(1, $crawler->filter('script[src="/build/cookieConsent.js"]'));
        $this->assertCount(1, $crawler->filter('script[src="/build/newLocation.js"]'));
    }
}
