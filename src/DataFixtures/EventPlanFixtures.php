<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Event;
use App\DataFixtures\UserFixtures;
use App\DataFixtures\SportTypeFixtures;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EventPlanFixtures extends Fixture implements DependentFixtureInterface
{

    public function load(ObjectManager $manager): void
    {

        $event1 = new Event();
        $event1->setTitle('Zimný výstup na Úhrad');
        $event1->setStartDate(new DateTimeImmutable('2010-01-02'));
        $event1->setCreatedAt(new DateTimeImmutable('2011-02-05 23:05:18'));
        $event1->setCreatedBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE));
        $event1->setPublish(true);
        $event1->setShowDate(true);
        $event1->addSportType($this->getReference(SportTypeFixtures::SPORT_TYPE_1_REFERENCE));
        $event1->addSportType($this->getReference(SportTypeFixtures::SPORT_TYPE_2_REFERENCE));
        $manager->persist($event1);

        $event2 = new Event();
        $event2->setTitle('Zimný výstup na Javorový vrch');
        $event2->setStartDate(new DateTimeImmutable('2010-01-09'));
        $event2->setCreatedAt(new DateTimeImmutable('2011-02-05 23:05:18'));
        $event2->setCreatedBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE));
        $event2->setPublish(true);
        $event2->setShowDate(true);
        $event2->addSportType($this->getReference(SportTypeFixtures::SPORT_TYPE_4_REFERENCE));
        $manager->persist($event2);

        $event3 = new Event();
        $event3->setTitle('Výjazd za snehom');
        $event3->setStartDate(new DateTimeImmutable('2010-01-23'));
        $event3->setCreatedAt(new DateTimeImmutable('2011-02-05 23:05:18'));
        $event3->setCreatedBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE));
        $event3->setPublish(true);
        $event3->setShowDate(true);
        $event3->addSportType($this->getReference(SportTypeFixtures::SPORT_TYPE_5_REFERENCE));
        $event3->addSportType($this->getReference(SportTypeFixtures::SPORT_TYPE_6_REFERENCE));

        $manager->persist($event3);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            SportTypeFixtures::class,
        ];
    }
}
