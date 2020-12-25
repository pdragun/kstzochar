<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Blog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
     * @var int $sectionId
     * @return Blog
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
     * @var int $sectionId
     */
    public function findAllByBlogSectionId(int $sectionId)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.publish = 1')
            ->andWhere('b.section = :sectionId')
            ->setParameter('sectionId', $sectionId)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }


    /**
     * @var int $sectionId
     */
    public function findAllByBlogSectionIdOrderByStartDate(int $sectionId)
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
     * @var int $sectionId
     * @return Blog[] Returns an prepared array of Blogs for table
     */
    public function getPreparedByYear(int $sectionId)
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
     * @var int $sectionId
     * @return Blog[] Returns an prepared array of Blogs for table
     */
    public function getPreparedByYearStartDate(int $sectionId)
    {
        $clearResults = [];

        $res = $this->findAllByBlogSectionIdOrderByStartDate($sectionId);
        foreach ($res as $blog) {
            $year = $blog['startDate']->format('Y');
            $clearResults[$year][] = $blog;
        }

        return $clearResults;
    }



    /**
     * 
     */
    public function findBySectionYearSlug($sectionId, $year, $slug): ?Blog
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