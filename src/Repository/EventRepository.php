<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Event> */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Find list of events based on year
     * @return list<array<string, mixed>> Published events from the year, hydrated as arrays
     */
    public function findByYear(int $year): array
    {
        return $this->createQueryBuilder('e')
            ->select('e', 'st', 'b')
            ->leftJoin('e.sportType', 'st')
            ->leftJoin('e.blog', 'b')
            ->andWhere('SUBSTRING(e.startDate, 1, 4) = :year')
            ->andWhere('e.publish = :publish')
            ->setParameter('year', $year)
            ->setParameter('publish', 1)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Get list of event from one year ordered according to months
     * @return array<int, list<array<string, mixed>>> Events grouped by month (1-12), or an empty array
     */
    public function getPreparedByYear(int $year): array
    {
        $res = $this->findByYear($year);
        if ($res === []) {
            return [];
        }

        $clearResults = [];
        for ($i = 1; $i <= 12; $i++) {
            $clearResults[$i] = [];
        }

        foreach ($res as $event) {
            $month = $event['startDate']->format('n');
            $clearResults[$month][] = $event;
        }

        return $clearResults;
    }

    /** Find the latest year from event plan, or null when no event is published */
    public function findMaxStartYear(): ?int
    {
        $max = $this->createQueryBuilder('e')
            ->select('MAX(e.startDate)')
            ->where('e.publish = 1')
            ->getQuery()
            ->getSingleScalarResult();

        return $max === null ? null : (int) substr((string) $max, 0, 4);
    }

    /** @return list<array{y: string}> */
    public function getUniqueYearsFromDB(): array
    {
        $em = $this->getEntityManager();
        $query = $em->createQuery('SELECT DISTINCT SUBSTRING(e.startDate, 1, 4) AS y FROM App\Entity\Event AS e ORDER BY y DESC');

        return $query->getArrayResult();
    }

    /** @return list<string> Years with an event, newest first */
    public function findUniqueYears(): array
    {
        $years = $this->getUniqueYearsFromDB();
        $clearYears = [];
        foreach ($years as $year) {
            $clearYears[] = $year['y'];
        }

        return $clearYears;
    }
}
