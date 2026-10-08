<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\HttpFoundation\UrlHelper;
use Twig\Attribute\AsTwigFunction;

/** schema.org structured data as JSON-LD */
class StructuredDataExtension
{
    public function __construct(private readonly UrlHelper $urlHelper)
    {
    }

    /**
     * BreadcrumbList for the breadcrumbs of the current page
     * @param list<array{label: string, uri: ?string}> $breadcrumbs from knp_menu_get_breadcrumbs_array()
     */
    #[AsTwigFunction('breadcrumb_json_ld', isSafe: ['html'])]
    public function breadcrumbJsonLd(array $breadcrumbs): string
    {
        $items = [];
        foreach ($breadcrumbs as $index => $breadcrumb) {
            $item = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $breadcrumb['label'],
            ];
            if (null !== $breadcrumb['uri'] && '' !== $breadcrumb['uri']) {
                $item['item'] = $this->urlHelper->getAbsoluteUrl($breadcrumb['uri']);
            }
            $items[] = $item;
        }

        return $this->encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ]);
    }

    /**
     * JSON safe to print inside a <script> element: JSON_HEX_TAG keeps a "</script>" in the data from closing it
     * @param array<string, mixed> $data
     */
    private function encode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_THROW_ON_ERROR);
    }
}
