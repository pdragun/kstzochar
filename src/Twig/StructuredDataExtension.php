<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Article;
use App\Entity\Blog;
use App\Entity\EventInvitation;
use App\Entity\Location;
use App\Entity\User;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

/** schema.org structured data as JSON-LD */
class StructuredDataExtension
{
    /** Event dates are stored as the wall-clock time the admin typed, in the club's timezone */
    private const string TIMEZONE = 'Europe/Bratislava';

    public function __construct(
        private readonly UrlHelper $urlHelper,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
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

    /** Event for the detail page of an invitation */
    #[AsTwigFunction('event_json_ld', isSafe: ['html'])]
    public function eventJsonLd(EventInvitation $invitation): string
    {
        $startDate = $invitation->getStartDate();
        $endDate = $invitation->getEndDate();
        $location = $invitation->getLocation();

        return $this->encode(self::withoutNulls([
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $invitation->getTitle(),
            'description' => trim(strip_tags((string) $invitation->getSummary())),
            'url' => $this->invitationUrl($invitation),
            'startDate' => $startDate === null ? null : $this->isoDate($startDate),
            'endDate' => $endDate === null ? null : $this->isoDate($endDate),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => $location === null ? null : $this->place($location),
            'organizer' => $this->club(),
        ]));
    }

    /** BlogPosting for a blog post, Article for a chronicle; $url is the absolute URL of its detail page */
    #[AsTwigFunction('article_json_ld', isSafe: ['html'])]
    public function articleJsonLd(Article $article, string $url): string
    {
        $published = self::publishedAt($article);
        $modified = $article->getModifiedAt();

        return $this->encode(self::withoutNulls([
            '@context' => 'https://schema.org',
            '@type' => $article instanceof Blog ? 'BlogPosting' : 'Article',
            'headline' => $article->getTitle(),
            'description' => trim(strip_tags((string) $article->getSummary())),
            'url' => $url,
            'mainEntityOfPage' => $url,
            'author' => ['@type' => 'Person', 'name' => $this->author($article)?->getDisplayName()],
            'datePublished' => $published === null ? null : $this->isoTimestamp($published),
            'dateModified' => $modified === null ? null : $this->isoTimestamp($modified),
            'publisher' => $this->club(),
        ]));
    }

    /** Who wrote the article: the author when set, otherwise who entered it */
    #[AsTwigFunction('article_author')]
    public function author(Article $article): ?User
    {
        return $article->getAuthorBy() ?? $article->getCreatedBy();
    }

    /** When the article was published; entries published before publishedAt existed fall back to createdAt */
    #[AsTwigFunction('article_published_at')]
    public static function publishedAt(Article $article): ?DateTimeImmutable
    {
        return $article->getPublishedAt() ?? $article->getCreatedAt();
    }

    /**
     * ISO 8601 date in the club's timezone (+01:00 in winter, +02:00 in summer);
     * only the date when the time is 00:00, which means no time was set
     */
    #[AsTwigFilter('event_iso_date')]
    public function isoDate(DateTimeInterface $date): string
    {
        if ($date->format('H:i:s') === '00:00:00') {
            return $date->format('Y-m-d');
        }

        return new DateTimeImmutable($date->format('Y-m-d H:i:s'), new DateTimeZone(self::TIMEZONE))->format(DATE_ATOM);
    }

    /**
     * ISO 8601 date-time in the club's timezone for a timestamp (createdAt, publishedAt, ...),
     * which PHP creates and stores in UTC
     */
    #[AsTwigFilter('iso_timestamp')]
    public function isoTimestamp(DateTimeInterface $timestamp): string
    {
        return new DateTimeImmutable($timestamp->format('Y-m-d H:i:s'), new DateTimeZone('UTC'))
            ->setTimezone(new DateTimeZone(self::TIMEZONE))
            ->format(DATE_ATOM);
    }

    /** Absolute URL of the invitation's detail page */
    #[AsTwigFunction('invitation_url')]
    public function invitationUrl(EventInvitation $invitation): string
    {
        return $this->urlGenerator->generate(
            'invitation_show_by_Year_by_Slug',
            ['year' => $invitation->getStartDate()?->format('Y'), 'slug' => $invitation->getSlug()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    /** @return array<string, string> */
    private function club(): array
    {
        return [
            '@type' => 'Organization',
            'name' => $this->translator->trans('base.title'),
            'url' => $this->urlGenerator->generate('home_page', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];
    }

    /** @return array<string, mixed> */
    private function place(Location $location): array
    {
        $geo = null;
        if ($location->getLatitude() !== null && $location->getLongitude() !== null) {
            $geo = [
                '@type' => 'GeoCoordinates',
                'latitude' => $location->getLatitude(),
                'longitude' => $location->getLongitude(),
            ];
        }

        return [
            '@type' => 'Place',
            'name' => $location->getName(),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $location->getStreetAddress(),
                'addressLocality' => $location->getAddressLocality(),
                'postalCode' => $location->getPostalCode(),
                'addressRegion' => $location->getAddressRegion(),
                'addressCountry' => $location->getAddressCountry(),
            ],
            'geo' => $geo,
        ];
    }

    /**
     * Leave out empty values, also in nested objects
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function withoutNulls(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $value = self::withoutNulls($value);
            }
            if ($value === null || $value === '') {
                unset($data[$key]);
            }
        }

        return $data;
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
