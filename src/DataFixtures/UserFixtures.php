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
    public const MEMBER_USER_REFERENCE = 'member-user';

    public function __construct(private readonly UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $userAdmin = new User();
        $userAdmin->setEmail('john.doe@example.com');
        $userAdmin->setRoles(['ROLE_ADMIN']);
        $userAdmin->setDisplayName('John Doe');
        $userAdmin->setNickName('John D.');

        $password = $this->hasher->hashPassword($userAdmin, 'pass_1234');
        $userAdmin->setPassword($password);

        $manager->persist($userAdmin);

        // a member who writes articles that an admin enters
        $userMember = new User();
        $userMember->setEmail('jana.novakova@example.com');
        $userMember->setRoles([]);
        $userMember->setDisplayName('Jana Nováková');
        $userMember->setNickName('Jana N.');
        $userMember->setPassword($this->hasher->hashPassword($userMember, 'pass_5678'));
        $manager->persist($userMember);

        $manager->flush();

        $this->addReference(self::ADMIN_USER_REFERENCE, $userAdmin);
        $this->addReference(self::MEMBER_USER_REFERENCE, $userMember);
    }
}
