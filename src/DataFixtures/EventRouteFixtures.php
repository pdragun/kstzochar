<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\EventRoute;
use App\Service\Gpx;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use phpGPX\Models\Metadata;
use phpGPX\phpGPX;
use Symfony\Contracts\Translation\TranslatorInterface;

class EventRouteFixtures extends Fixture
{

    public const EVENT_ROUTE_FOR_INVITATION_REFERENCE = 'event-route-for-invitation';
    public const EVENT_ROUTE_FOR_CHRONICLE_REFERENCE = 'event-route-for-chronicle';

    public function __construct(
        private readonly TranslatorInterface $translator,
    )
    {}

    public function load(ObjectManager $manager): void
    {
        $gpx = new phpGPX();
        $file = $gpx->load(__DIR__ . '/gpx/garmin.gpx');

        $gpx = Gpx::transform($file);
        $title = 'Okolie Tesár, športové hry';
        $gpx->setMetaTitle($title);
        $gpx->setAuthor(
            author: $this->translator->trans('gpx.metadata.author'),
            emailId: $this->translator->trans('gpx.metadata.emailId'),
            emailDomain: $this->translator->trans('gpx.metadata.emailDomain'),
        );

        $eventRoute1 = new EventRoute();
        $eventRoute1->setTitle($title);
        $eventRoute1->setLength(5);
        $eventRoute1->setElevation(10);
        $eventRoute1->setCreatedAt(new DateTimeImmutable('2011-09-04 15:55:39'));
        $eventRoute1->setGpx($file->toXML()->saveXML());
        $manager->persist($eventRoute1);

        $eventRoute2 = new EventRoute();
        $eventRoute2->setTitle('Podhradie – Opálená skala – Džimova spása – Úhrad – Podhradie');
        $eventRoute2->setLength(15);
        $eventRoute2->setElevation(11);
        $eventRoute2->setCreatedAt(new DateTimeImmutable('2011-08-03 16:28:31'));
        $manager->persist($eventRoute2);

        //Only for testing creating entity
        $eventRoute3 = new EventRoute();
        $eventRoute3->setTitle('Test title');
        $eventRoute3->setLength(15);
        $eventRoute3->setCreatedAt(new DateTimeImmutable('2021-08-03 16:28:31'));
        $manager->persist($eventRoute3);

        $manager->flush();
        $this->addReference(self::EVENT_ROUTE_FOR_INVITATION_REFERENCE, $eventRoute1);
        $this->addReference(self::EVENT_ROUTE_FOR_CHRONICLE_REFERENCE, $eventRoute2);
    }
}
