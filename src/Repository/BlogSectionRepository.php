<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\BlogSection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BlogSection|null find($id, $lockMode = null, $lockVersion = null)
 * @method BlogSection|null findOneBy(array $criteria, array $orderBy = null)
 * @method BlogSection[]    findAll()
 * @method BlogSection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BlogSectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogSection::class);
    }


    public function findBySlug($slug): ?BlogSection
    {
        return $this->createQueryBuilder('p')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
