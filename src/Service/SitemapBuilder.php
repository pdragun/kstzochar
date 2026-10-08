<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Blog;
use App\Entity\Event;
use App\Entity\EventChronicle;
use App\Entity\EventInvitation;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The public pages for sitemap.xml: static pages, year lists and every published invitation, chronicle and blog.
 * Entries hold route names and parameters, not URLs, so the cache does not depend on the request's host.
 */
final readonly class SitemapBuilder
{
    /** Pages without parameters, in the order of the main menu */
    private const array STATIC_ROUTES = [
        'home_page',
        'invitation_show',
        'invitation_list_upcoming',
        'chronicle_show',
        'plan',
        'blog',
        'contact',
        'cookies',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheInterface $contentCache,
    ) {
    }

    /**
     * @return list<array{route: string, params: array<string, string|int>, lastmod: ?DateTimeImmutable}>
     * @throws InvalidArgumentException
     */
    public function entries(): array
    {
        return $this->contentCache->get('sitemap', fn (): array => [
            ...array_map(self::entry(...), self::STATIC_ROUTES),
            ...$this->eventContent(EventInvitation::class, 'invitation_list_by_Year', 'invitation_show_by_Year_by_Slug'),
            ...$this->eventContent(EventChronicle::class, 'chronicle_list_by_Year', 'chronicle_show_by_Year_Slug'),
            ...$this->planYears(),
            ...$this->blogs(),
        ]);
    }

    /**
     * Year lists and detail pages of invitations or chronicles; the URL year is the start date's
     * @param class-string<EventInvitation|EventChronicle> $class
     * @return list<array{route: string, params: array<string, string|int>, lastmod: ?DateTimeImmutable}>
     */
    private function eventContent(string $class, string $yearRoute, string $showRoute): array
    {
        /** @var list<array{slug: string, startDate: DateTimeImmutable, createdAt: DateTimeImmutable, modifiedAt: ?DateTimeImmutable}> $rows */
        $rows = $this->entityManager->createQuery(
            'SELECT c.slug, c.startDate, c.createdAt, c.modifiedAt FROM ' . $class . ' c
             WHERE c.publish = 1 ORDER BY c.startDate DESC, c.id DESC',
        )->getResult();

        $years = [];
        $details = [];
        foreach ($rows as $row) {
            $year = (int) $row['startDate']->format('Y');
            $years[$year] = self::entry($yearRoute, ['year' => $year]);
            $details[] = self::entry($showRoute, ['year' => $year, 'slug' => $row['slug']], self::lastModified($row));
        }

        return [...array_values($years), ...$details];
    }

    /** @return list<array{route: string, params: array<string, string|int>, lastmod: ?DateTimeImmutable}> */
    private function planYears(): array
    {
        /** @var list<array{startDate: DateTimeImmutable}> $rows */
        $rows = $this->entityManager->createQuery(
            'SELECT e.startDate FROM ' . Event::class . ' e WHERE e.publish = 1 ORDER BY e.startDate DESC',
        )->getResult();

        $years = [];
        foreach ($rows as $row) {
            $year = (int) $row['startDate']->format('Y');
            $years[$year] = self::entry('plan_show_by_Year', ['year' => $year]);
        }

        return array_values($years);
    }

    /**
     * Blog sections and blogs; the URL year is the creation date's
     * @return list<array{route: string, params: array<string, string|int>, lastmod: ?DateTimeImmutable}>
     */
    private function blogs(): array
    {
        /** @var list<array{slug: string, sectionSlug: string, createdAt: DateTimeImmutable, modifiedAt: ?DateTimeImmutable}> $rows */
        $rows = $this->entityManager->createQuery(
            'SELECT b.slug, s.slug AS sectionSlug, b.createdAt, b.modifiedAt FROM ' . Blog::class . ' b
             JOIN b.section s WHERE b.publish = 1 ORDER BY s.id ASC, b.createdAt DESC, b.id DESC',
        )->getResult();

        $sections = [];
        $details = [];
        foreach ($rows as $row) {
            $sections[$row['sectionSlug']] = self::entry('blog_list_by_BlogSectionSlug', ['blogSectionSlug' => $row['sectionSlug']]);
            $details[] = self::entry(
                'blog_show_by_BlogSectionSlug_Year_Slug',
                ['blogSectionSlug' => $row['sectionSlug'], 'year' => (int) $row['createdAt']->format('Y'), 'slug' => $row['slug']],
                self::lastModified($row),
            );
        }

        return [...array_values($sections), ...$details];
    }

    /**
     * The last change of the page; publishedAt is left out, it can be set to a future date
     * @param array{createdAt: DateTimeImmutable, modifiedAt: ?DateTimeImmutable} $row
     */
    private static function lastModified(array $row): DateTimeImmutable
    {
        return max($row['createdAt'], $row['modifiedAt'] ?? $row['createdAt']);
    }

    /**
     * @param array<string, string|int> $params
     * @return array{route: string, params: array<string, string|int>, lastmod: ?DateTimeImmutable}
     */
    private static function entry(string $route, array $params = [], ?DateTimeImmutable $lastmod = null): array
    {
        return ['route' => $route, 'params' => $params, 'lastmod' => $lastmod];
    }
}
