<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    
    /**
     * Find list of events based on year
     * 
     * @param $year int
     * @return Event[] Returns an array of Event objects
     */
    public function findByYear($year)
    {

        $em = $this->getEntityManager()->getConfiguration();
        $em->addCustomDatetimeFunction('YEAR', 'DoctrineExtensions\Query\Mysql\Year');

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
     * 
     * @param $year int
     * @return Event[]|[] Returns an prepared array of Events for table or empty arr
     */
    public function getPreparedByYear($year)
    {

        $clearResults = [];
        for( $i = 1; $i <= 12; $i++) {
            $clearResults[$i] = [];
        }

        $res = $this->findByYear($year);
        if(!$res) {
            return [];
        }

        foreach ($res as $event) {
            $month = $event['startDate']->format('n');
            $clearResults[$month][] = $event;
        }

        return $clearResults;
    }


    public function findMaxStartDate()
    {
        $query = $this->createQueryBuilder('e')
            ->select('e, MAX(e.startDate) as maxYear')
            ->Where('e.publish = 1')
            ->setMaxResults(1)
            ->getQuery();

        return $query->getSingleResult();
    }

    
    /**
     * Find the latest year from event plan
     * 
     * @return null|int $year
     */
    public function findMaxStartYear()
    {
        $res = $this->findMaxStartDate();
        if(!$res || $res[0] === null) {
            return null;
        }

        $dt = new \DateTime($res['maxYear']);
        return $dt->format('Y');
    }


    public function getUniqueYearsFromDB(){

        $em = $this->getEntityManager();
        $query = $em->createQuery('SELECT DISTINCT SUBSTRING(e.startDate, 1, 4) AS y FROM App\Entity\Event AS e ORDER BY y ASC');
        return $query->getArrayResult();
    }


    public function findUniqueYears() {

        $years = $this->getUniqueYearsFromDB();

        $clearYears = [];
        foreach( $years as $year ) {
            $clearYears[] = $year['y'];
        }

        return $clearYears;
    }
}