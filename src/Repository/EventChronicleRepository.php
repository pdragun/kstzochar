<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EventChronicle;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EventChronicle|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventChronicle|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventChronicle[]    findAll()
 * @method EventChronicle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventChronicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventChronicle::class);
    }


    /** @return EventChronicle[] Returns an array of EventChronicle objects */
    public function findByStartDate($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.startDate = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return EventChronicle[] EventChronicle an array of EventChronicle objects */
    public function findByYear($year): array
    {
        $em = $this->getEntityManager()->getConfiguration();
        $em->addCustomDatetimeFunction('YEAR', 'DoctrineExtensions\Query\Mysql\Year');

        return $this->createQueryBuilder('p')
            ->andWhere('SUBSTRING(p.startDate, 1, 4) = :year')
            ->andWhere('p.publish = :publish')
            ->setParameter('year', $year)
            ->setParameter('publish', 1)
            ->orderBy('p.startDate', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /** @return array<int, <int, EventChronicle>> Returns prepared array of Events for table */
    public function getPreparedByYear($year): array
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
        $em = $this->getEntityManager()->getConfiguration();
        $em->addCustomDatetimeFunction('YEAR', 'DoctrineExtensions\Query\Mysql\Year');

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

    public function findLatest(): ?array
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

    public function getUniqueYearsFromDB(): array
    {
        $em = $this->getEntityManager();
        $query = $em->createQuery('SELECT DISTINCT SUBSTRING(e.startDate, 1, 4) AS y FROM App\Entity\Event AS e ORDER BY y ASC');

        return $query->getArrayResult();
    }

    public function findUniqueYears(): array {

        $years = $this->getUniqueYearsFromDB();
        $clearYears = [];
        foreach( $years as $year ) {
            $clearYears[] = $year['y'];
        }

        return $clearYears;
    }
}
