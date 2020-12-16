<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\EventInvitation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EventInvitation|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventInvitation|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventInvitation[]    findAll()
 * @method EventInvitation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventInvitation::class);
    }


    /**
     * 
     */
    public function findByYearSlug($year, $slug): ?EventInvitation
    {
        $em = $this->getEntityManager()->getConfiguration();
        $em->addCustomDatetimeFunction('YEAR', 'DoctrineExtensions\Query\Mysql\Year');

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

    /**
     * 
     */
    public function findLatest()
    {
        $currentDate = $date = new \DateTime();
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

    /**
     * @return Event[] Returns an array of Event objects
     *
     */
    public function findByYear($year)
    {

        $em = $this->getEntityManager()->getConfiguration();
        $em->addCustomDatetimeFunction('YEAR', 'DoctrineExtensions\Query\Mysql\Year');

        return $this->createQueryBuilder('p')
            ->andWhere('SUBSTRING(p.startDate, 1, 4) = :year')
            ->andWhere('p.publish = :publish')
            ->setParameter('year', $year)
            ->setParameter('publish', 1)
            ->orderBy('p.startDate', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return Event[]|[] Returns an prepared array of Events for table
     *
     */
    public function getPreparedByYear($year)
    {

        $clearResults = [];
        for( $i = 1; $i <= 12; $i++) {
            $clearResults[$i] = [];
        }

        $res = $this->findByYear($year);
        if(!$res) { //No records
            return [];
        }


        foreach ($res as $event) {
            $month = $event['startDate']->format('n');
            $clearResults[$month][] = $event;
        }

        return $clearResults;
    }
}
