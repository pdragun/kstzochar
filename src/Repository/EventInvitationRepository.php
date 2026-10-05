<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EventInvitation;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<EventInvitation> */
class EventInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventInvitation::class);
    }

    /** @throws NonUniqueResultException */
    public function findByYearSlug(int $year, string $slug): ?EventInvitation
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.slug = :slug')
            ->andWhere('SUBSTRING(p.startDate, 1, 4) = :year')
            ->andWhere('p.publish = :publish')
            ->setParameter('slug', $slug)
            ->setParameter('year', $year)
            ->setParameter('publish', 1)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult(); 
    }

    /** @return list<EventInvitation> Up to 20 published upcoming invitations */
    public function findLatest(): array
    {
        $currentDate = new DateTimeImmutable();
        $startDateUntilMidnight = $currentDate->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('p')
            ->where('p.startDate >= :startDateUntilMidnight')
            ->andWhere('p.publish = 1')
            ->setParameter('startDateUntilMidnight', $startDateUntilMidnight)
            ->orderBy('p.startDate', 'ASC')
            ->setMaxResults(20)
            ->getQuery();
        return $qb->getResult();
    }

    /** @return list<array<string, mixed>> Published invitations from the year, hydrated as arrays */
    public function findByYear(int $year): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('SUBSTRING(p.startDate, 1, 4) = :year')
            ->andWhere('p.publish = :publish')
            ->setParameter('year', $year)
            ->setParameter('publish', 1)
            ->orderBy('p.startDate', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /** @return array<int, list<array<string, mixed>>> Invitations grouped by month (1-12), or an empty array */
    public function getPreparedByYear(int $year): array
    {
        $clearResults = [];
        for ($i = 1; $i <= 12; $i++) {
            $clearResults[$i] = [];
        }

        $res = $this->findByYear($year);
        if ($res === []) { //No records
            return [];
        }

        foreach ($res as $event) {
            $month = $event['startDate']->format('n');
            $clearResults[$month][] = $event;
        }

        return $clearResults;
    }
}
