<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    public const ADMIN_USER_REFERENCE = 'admin-user';

    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {

        $userAdmin = new User();
        $userAdmin->setEmail('john.doe@example.com');
        $userAdmin->setRoles(['ROLE_ADMIN']);
        $userAdmin->setDisplayName('John Doe');
        $userAdmin->setNickName('John D.');

        $password = $this->encoder->encodePassword($userAdmin, 'pass_1234');
        $userAdmin->setPassword($password);

        $manager->persist($userAdmin);
        $manager->flush();

        $this->addReference(self::ADMIN_USER_REFERENCE, $userAdmin);
    }
}
