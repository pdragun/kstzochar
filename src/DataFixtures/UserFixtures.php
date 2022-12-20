<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const ADMIN_USER_REFERENCE = 'admin-user';

    public function __construct(private readonly UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager)
    {

        $userAdmin = new User();
        $userAdmin->setEmail('john.doe@example.com');
        $userAdmin->setRoles(['ROLE_ADMIN']);
        $userAdmin->setDisplayName('John Doe');
        $userAdmin->setNickName('John D.');

        $password = $this->hasher->hashPassword($userAdmin, 'pass_1234');
        $userAdmin->setPassword($password);

        $manager->persist($userAdmin);
        $manager->flush();

        $this->addReference(self::ADMIN_USER_REFERENCE, $userAdmin);
    }
}
