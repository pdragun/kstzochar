<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EventChronicle;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<EventChronicle> */
class EventChronicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventChronicle::class);
    }

    /** @return list<array<string, mixed>> Published chronicles from the year, hydrated as arrays */
    public function findByYear(int $year): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('SUBSTRING(p.startDate, 1, 4) = :year')
            ->andWhere('p.publish = :publish')
            ->setParameter('year', $year)
            ->setParameter('publish', 1)
            ->orderBy('p.startDate', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /** @return array<int, list<array<string, mixed>>> Chronicles grouped by month */
    public function getPreparedByYear(int $year): array
    {
        $clearResults = [];
        $res = $this->findByYear($year);
        foreach ($res as $event) {
            $month = $event['startDate']->format('n');
            $clearResults[$month][] = $event;
        }

        return $clearResults;
    }

    /** @throws NonUniqueResultException */
    public function findByYearSlug(int $year, string $slug): ?EventChronicle
    {
        return $this->createQueryBuilder('p')
            ->where('p.slug = :slug')
            ->andWhere('SUBSTRING(p.startDate, 1, 4) = :year')
            ->andWhere('p.publish = :publish')
            ->setParameter('slug', $slug)
            ->setParameter('year', $year)
            ->setParameter('publish', 1)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<EventChronicle> The latest published chronicle that already started (at most one) */
    public function findLatest(): array
    {
        $currentDate = new DateTimeImmutable();
        $startDateUntilMidnight = $currentDate->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('p')
            ->where('p.startDate <= :startDateUntilMidnight')
            ->andWhere('p.publish = 1')
            ->setParameter('startDateUntilMidnight', $startDateUntilMidnight)
            ->orderBy('p.startDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        return $qb->getResult();
    }
}
