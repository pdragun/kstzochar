<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Blog;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Blog|null find($id, $lockMode = null, $lockVersion = null)
 * @method Blog|null findOneBy(array $criteria, array $orderBy = null)
 * @method Blog[]    findAll()
 * @method Blog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
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

    public function findAllByBlogSectionId(int $sectionId): float|int|array|string
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.publish = 1')
            ->andWhere('b.section = :sectionId')
            ->setParameter('sectionId', $sectionId)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    public function findAllByBlogSectionIdOrderByStartDate(int $sectionId): array|float|int|string
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.publish = 1')
            ->andWhere('b.section = :sectionId')
            ->setParameter('sectionId', $sectionId)
            ->orderBy('b.startDate', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return array<int <int, Blog>> Returns prepared array of Blogs for table
     */
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

    /**
     * @return array<int, Blog> Returns prepared array of Blogs for table
     */
    public function getPreparedByYearStartDate(int $sectionId): array
    {
        $clearResults = [];
        $res = $this->findAllByBlogSectionIdOrderByStartDate($sectionId);
        foreach ($res as $blog) {
            $year = $blog['startDate']->format('Y');
            $clearResults[$year][] = $blog;
        }

        return $clearResults;
    }

    /** @throws NonUniqueResultException */
    public function findBySectionYearSlug(int $sectionId, int $year, string $slug): ?Blog
    {
        $em = $this->getEntityManager()->getConfiguration();
        $em->addCustomDatetimeFunction('YEAR', 'DoctrineExtensions\Query\Mysql\Year');

        return $this->createQueryBuilder('b')
            ->andWhere('b.slug = :slug')
            ->andWhere('SUBSTRING(b.createdAt, 1, 4) = :year')
            ->andWhere('b.publish = :publish')
            ->setParameter('slug', $slug)
            ->setParameter('year', $year)
            ->setParameter('publish', 1)
            ->orderBy('b.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();        
    }
}
