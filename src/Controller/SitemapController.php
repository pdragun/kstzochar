<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SitemapBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** sitemap.xml for search engines, and robots.txt that points to it */
class SitemapController extends AbstractController
{
    /** @throws InvalidArgumentException */
    #[Route('/sitemap.xml', name: 'sitemap', format: 'xml', methods: ['GET'])]
    public function sitemap(SitemapBuilder $sitemapBuilder): Response
    {
        return $this->render(
            'sitemap/sitemap.xml.twig',
            ['entries' => $sitemapBuilder->entries()],
            new Response(headers: ['Content-Type' => 'application/xml; charset=UTF-8']),
        );
    }

    #[Route('/robots.txt', name: 'robots', format: 'txt', methods: ['GET'])]
    public function robots(): Response
    {
        return $this->render(
            'sitemap/robots.txt.twig',
            [],
            new Response(headers: ['Content-Type' => 'text/plain; charset=UTF-8']),
        );
    }
}
