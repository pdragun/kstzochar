<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Blog;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Blog> */
class BlogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Blog::class);
    }

    /**
     * Get Blog from section with the lowest createdAt date
     * @throws NonUniqueResultException
     */
    public function findLatestByBlogSectionId(int $sectionId): ?Blog
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.publish = 1')
            ->andWhere('b.section = :sectionId')
            ->setParameter('sectionId', $sectionId)
            ->orderBy('b.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get Blog from section with the latest by start date
     * @throws NonUniqueResultException
     */
    public function findLatestByBlogSectionIdStartDate(int $sectionId): ?Blog
    {

        $currentDate = new DateTimeImmutable();
        $startDateUntilMidnight = $currentDate->setTime(23, 59, 59);

        return $this->createQueryBuilder('b')
            ->where('b.startDate >= :startDateUntilMidnight')
            ->andWhere('b.publish = 1')
            ->andWhere('b.section = :sectionId')
            ->setParameter('sectionId', $sectionId)
            ->setParameter('startDateUntilMidnight', $startDateUntilMidnight)
            ->orderBy('b.startDate', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<array<string, mixed>> */
    public function findAllByBlogSectionId(int $sectionId): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.publish = 1')
            ->andWhere('b.section = :sectionId')
            ->setParameter('sectionId', $sectionId)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /** @return list<array<string, mixed>> */
    public function findAllByBlogSectionIdOrderByStartDate(int $sectionId): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.publish = 1')
            ->andWhere('b.section = :sectionId')
            ->setParameter('sectionId', $sectionId)
            ->orderBy('b.startDate', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /** @return array<int, list<array<string, mixed>>> Blogs grouped by year of creation */
    public function getPreparedByYear(int $sectionId): array
    {
        $clearResults = [];

        $res = $this->findAllByBlogSectionId($sectionId);
        foreach ($res as $blog) {
            $year = $blog['createdAt']->format('Y');
            $clearResults[$year][] = $blog;
        }

        return $clearResults;
    }

    /** @return array<int, list<array<string, mixed>>> Blogs grouped by year of the start date (or of creation when there is no start date), newest first */
    public function getPreparedByYearStartDate(int $sectionId): array
    {
        $clearResults = [];
        $res = $this->findAllByBlogSectionIdOrderByStartDate($sectionId);
        foreach ($res as $blog) {
            $year = ($blog['startDate'] ?? $blog['createdAt'])->format('Y');
            $clearResults[$year][] = $blog;
        }
        krsort($clearResults);

        return $clearResults;
    }

    /** @throws NonUniqueResultException */
    public function findBySectionYearSlug(int $sectionId, int $year, string $slug): ?Blog
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.section = :sectionId')
            ->andWhere('b.slug = :slug')
            ->andWhere('SUBSTRING(b.createdAt, 1, 4) = :year')
            ->andWhere('b.publish = :publish')
            ->setParameter('sectionId', $sectionId)
            ->setParameter('slug', $slug)
            ->setParameter('year', $year)
            ->setParameter('publish', 1)
            ->orderBy('b.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
