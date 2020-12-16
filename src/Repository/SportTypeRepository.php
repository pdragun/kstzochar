<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\SportType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SportType|null find($id, $lockMode = null, $lockVersion = null)
 * @method SportType|null findOneBy(array $criteria, array $orderBy = null)
 * @method SportType[]    findAll()
 * @method SportType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SportTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SportType::class);
    }

}
