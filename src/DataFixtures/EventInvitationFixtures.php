<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\EventInvitation;
use App\Entity\EventRoute;
use App\Entity\Location;
use App\Entity\SportType;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EventInvitationFixtures extends Fixture implements DependentFixtureInterface
{
    use SlugTrait;
    public const INVITATION_1_REFERENCE = 'invitation';
    public const INVITATION_FOR_EVENT_REFERENCE = 'invitation-for-event';

    public function load(ObjectManager $manager): void
    {
        $invitation1 = new EventInvitation();
        $invitation1->setTitle('Gulášové opojenie v Tesároch');
        $invitation1->setSlug($this->createSlug('gulasove-opojenie-v-tesaroch'));
        $invitation1->setSummary('Turisticko-športový deň 10.9. v Tesároch');
        $invitation1->setStartDate(new DateTimeImmutable('2011-09-10 10:00:00'));
        $invitation1->setPublishedAt(new DateTimeImmutable('2011-09-07 21:58:33'));
        $invitation1->setCreatedAt(new DateTimeImmutable('2011-09-04 15:55:39'));
        $invitation1->setModifiedAt(null);
        $invitation1->setPublish(true);
        $invitation1->setContent('<p>Dňa <strong>10. 9. 2011</strong> (sobota) na futbalovom ihrisku.</p>
        <p>Začiatok &ndash; <strong>10:oo</strong> hod.</p>
        <p><strong>Program:</strong></p>
        <ul>
            <li>Priv&iacute;tanie,</li>
            <li>Kr&aacute;tka vych&aacute;dzka pre z&aacute;ujemcov,</li>
            <li>Volejbalov&yacute; turnaj,</li>
            <li>Občerstvenie,</li>
            <li>&Scaron;portovo-z&aacute;bavn&eacute; s&uacute;ťaže pre deti a dospel&yacute;ch,</li>
            <li>Ďalej si bude možnosť vysk&uacute;&scaron;ať  svoje schopnosti v no-hejbale, stolnom tenise a vyb&iacute;janej.</li>
        </ul>
        <p>&Uacute;časť nahl&aacute;siť do 4. 9. 2011 na tel. č. 0908 433 515 alebo 0904566363.</p>
        <p>Pr&iacute;ďte načerpať nov&eacute; sily, zas&uacute;ťažiť si, zaspom&iacute;nať na pekn&eacute; podujatia, pripraviť nov&eacute; a str&aacute;viť pr&iacute;jemn&eacute; chv&iacute;le v kruhu svojich kamar&aacute;tov.</p>
        <p>Te&scaron;&iacute;me sa na spoločn&eacute; stretnutie v pr&iacute;jemnom prostred&iacute;.</p>
        <p>www.krokovelo.webnode.cz</p>');
        $invitation1->setCreatedBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE, User::class));
        $invitation1->setAuthorBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE, User::class));
        $invitation1->addRoute($this->getReference(EventRouteFixtures::EVENT_ROUTE_FOR_INVITATION_REFERENCE, EventRoute::class));
        $invitation1->addRoute($this->getReference(EventRouteFixtures::EVENT_ROUTE_FOR_CHRONICLE_REFERENCE, EventRoute::class));
        $invitation1->addSportType($this->getReference(SportTypeFixtures::SPORT_TYPE_1_REFERENCE, SportType::class));
        $invitation1->addSportType($this->getReference(SportTypeFixtures::SPORT_TYPE_3_REFERENCE, SportType::class));
        $invitation1->setLocation($this->getReference(LocationFixtures::LOCATION_TESARE_REFERENCE, Location::class));
        $manager->persist($invitation1);

        // new upcoming event
        $invitation2 = new EventInvitation();
        $invitation2->setTitle('Upcoming event');
        $invitation2->setSlug($this->createSlug('Upcoming-event'));
        $invitation2->setSummary('Upcoming test event');
        $invitation2->setStartDate(new DateTimeImmutable('tomorrow')); // start date always tomorrow
        $invitation2->setPublishedAt(new DateTimeImmutable());
        $invitation2->setCreatedAt(new DateTimeImmutable());
        $invitation2->setModifiedAt(null);
        $invitation2->setPublish(true);
        $invitation2->setContent('<p>Test upcoming event. Everyone is welcome.</p>');
        $invitation2->setCreatedBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE, User::class));
        $invitation2->setAuthorBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE, User::class));
        $invitation2->addRoute($this->getReference(EventRouteFixtures::EVENT_ROUTE_FOR_INVITATION_REFERENCE, EventRoute::class));
        $invitation2->addRoute($this->getReference(EventRouteFixtures::EVENT_ROUTE_FOR_CHRONICLE_REFERENCE, EventRoute::class));
        $invitation2->addSportType($this->getReference(SportTypeFixtures::SPORT_TYPE_1_REFERENCE, SportType::class));
        $invitation2->addSportType($this->getReference(SportTypeFixtures::SPORT_TYPE_3_REFERENCE, SportType::class));
        $manager->persist($invitation2);

        // invitation to a planned event, a new chronicle for the event copies its routes
        $invitation3 = new EventInvitation();
        $invitation3->setTitle('Zimný výstup na Javorový vrch');
        $invitation3->setSlug($this->createSlug('Zimny-vystup-na-Javorovy-vrch'));
        $invitation3->setSummary('Autobusom pod Javorový vrch');
        $invitation3->setStartDate(new DateTimeImmutable('2010-01-09 07:00:00'));
        $invitation3->setPublishedAt(new DateTimeImmutable());
        $invitation3->setCreatedAt(new DateTimeImmutable());
        $invitation3->setModifiedAt(null);
        $invitation3->setPublish(true);
        $invitation3->setContent('<p>Odchod autobusu o 7:00.</p>');
        $invitation3->setCreatedBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE, User::class));
        $invitation3->setAuthorBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE, User::class));
        $invitation3->addRoute($this->getReference(EventRouteFixtures::EVENT_ROUTE_FOR_INVITATION_REFERENCE, EventRoute::class));
        $invitation3->addRoute($this->getReference(EventRouteFixtures::EVENT_ROUTE_FOR_CHRONICLE_REFERENCE, EventRoute::class));
        $invitation3->addSportType($this->getReference(SportTypeFixtures::SPORT_TYPE_4_REFERENCE, SportType::class));
        $manager->persist($invitation3);

        $manager->flush();
        $this->addReference(self::INVITATION_1_REFERENCE, $invitation1);
        $this->addReference(self::INVITATION_FOR_EVENT_REFERENCE, $invitation3);
    }

    public function getDependencies(): array
    {
        return [
            EventRouteFixtures::class,
            UserFixtures::class,
            SportTypeFixtures::class,
            LocationFixtures::class,
        ];
    }
}
