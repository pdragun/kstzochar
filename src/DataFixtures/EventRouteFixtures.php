<?php

namespace App\DataFixtures;

use App\Entity\EventRoute;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EventRouteFixtures extends Fixture
{

    public const EVENT_ROUTE_FOR_INVITATION_REFERENCE = 'event-route-for-invitation';
    public const EVENT_ROUTE_FOR_CHRONICLE_REFERENCE = 'event-route-for-chronicle';

        public function load(ObjectManager $manager)
    {
       
        $eventRoute1 = new EventRoute();
        $eventRoute1->setTitle('Okolie Tesár, športové hry');
        $eventRoute1->setLength(5);
        $eventRoute1->setCreatedAt(new \DateTime('2011-09-04 15:55:39'));
        
        $manager->persist($eventRoute1);

        $eventRoute2 = new EventRoute();
        $eventRoute2->setTitle('Podhradie – Opálená skala – Džimova spása – Úhrad – Podhradie');
        $eventRoute2->setLength(15);
        $eventRoute2->setCreatedAt(new \DateTime('2011-08-03 16:28:31'));
        
        $manager->persist($eventRoute2);

        $manager->flush();
        $this->addReference(self::EVENT_ROUTE_FOR_INVITATION_REFERENCE, $eventRoute1);
        $this->addReference(self::EVENT_ROUTE_FOR_CHRONICLE_REFERENCE, $eventRoute2);
    }
}
