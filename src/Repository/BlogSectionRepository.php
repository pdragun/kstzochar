<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BlogSection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<BlogSection> */
class BlogSectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogSection::class);
    }

    /** @throws NonUniqueResultException */
    public function findBySlug(string $slug): ?BlogSection
    {
        return $this->createQueryBuilder('p')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
