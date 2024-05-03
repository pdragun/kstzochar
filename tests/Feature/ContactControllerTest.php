<?php

declare(strict_types=1);

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
uses(WebTestCase::class);

test('contact page', function () {

    $response = $this->get('GET', '/kontakt');

    expect($response->getStatusCode())->toEqual(200);
    $this->assertSelectorTextContains('html h1', 'Kontakt');
});

test('404', function (string $url) {
    $response = $this->get('GET', $url);

    expect($response->getStatusCode())->toEqual(404);
})->with([
    '/kontak',
    '/kontakta',
]);
