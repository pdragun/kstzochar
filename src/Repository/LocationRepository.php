<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Location> */
class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    /** @return list<array{location: Location, invitationCount: int}> all locations by name, with the number of invitations using each */
    public function findAllWithInvitationCount(): array
    {
        /** @var list<array{location: Location, invitationCount: int|string}> $rows */
        $rows = $this->createQueryBuilder('l')
            ->select('l AS location', 'COUNT(i.id) AS invitationCount')
            ->leftJoin('l.eventInvitations', 'i')
            ->groupBy('l.id')
            ->orderBy('l.name', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (array $row): array => ['location' => $row['location'], 'invitationCount' => (int) $row['invitationCount']],
            $rows,
        );
    }
}
