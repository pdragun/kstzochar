<?php

namespace App\DataFixtures;

use App\Entity\EventInvitation;
use App\DataFixtures\EventRouteFixtures;
use App\DataFixtures\SportTypeFixtures;
use App\DataFixtures\UserFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EventInvitationFixtures extends Fixture implements DependentFixtureInterface
{

    public const INVITATION_1_REFERENCE = 'invitation';

    public function load(ObjectManager $manager)
    {
        $invitation1 = new EventInvitation();
        $invitation1->setTitle('Gulášové opojenie v Tesároch');
        $invitation1->setSlug('gulasove-opojenie-v-tesaroch');
        $invitation1->setSummary('Turisticko-športový deň 10.9. v Tesároch');
        $invitation1->setStartDate(new \DateTime('2011-09-10 10:00:00'));
        $invitation1->setPublishedAt(new \DateTime('2011-09-07 21:58:33'));
        $invitation1->setCreatedAt(new \DateTime('2011-09-04 15:55:39'));
        $invitation1->setModifiedAt(\null);
        $invitation1->setPublish(TRUE);
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
        $invitation1->setCreatedBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE));
        $invitation1->setAuthorBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE));
        $invitation1->addRoute($this->getReference(EventRouteFixtures::EVENT_ROUTE_FOR_INVITATION_REFERENCE));
        $invitation1->addRoute($this->getReference(EventRouteFixtures::EVENT_ROUTE_FOR_CHRONICLE_REFERENCE));
        $invitation1->addSportType($this->getReference(SportTypeFixtures::SPORT_TYPE_1_REFERENCE));
        $invitation1->addSportType($this->getReference(SportTypeFixtures::SPORT_TYPE_3_REFERENCE));

        $manager->persist($invitation1);

        // new upcomming event
        $invitation2 = new EventInvitation();
        $invitation2->setTitle('Upcomming event');
        $invitation2->setSlug('Upcomming-event');
        $invitation2->setSummary('Upcomming test event');
        $invitation2->setStartDate(new \DateTime('tomorrow')); // start date always tomorrow
        $invitation2->setPublishedAt(new \DateTime()); // now
        $invitation2->setCreatedAt(new \DateTime()); // now
        $invitation2->setModifiedAt(\null);
        $invitation2->setPublish(TRUE);
        $invitation2->setContent('<p>Test upcomming event. Everyone is welcome.</p>');
        $invitation2->setCreatedBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE));
        $invitation2->setAuthorBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE));
        $invitation2->addRoute($this->getReference(EventRouteFixtures::EVENT_ROUTE_FOR_INVITATION_REFERENCE));
        $invitation2->addRoute($this->getReference(EventRouteFixtures::EVENT_ROUTE_FOR_CHRONICLE_REFERENCE));
        $invitation2->addSportType($this->getReference(SportTypeFixtures::SPORT_TYPE_1_REFERENCE));
        $invitation2->addSportType($this->getReference(SportTypeFixtures::SPORT_TYPE_3_REFERENCE));

        $manager->persist($invitation2);

        $manager->flush();
        $this->addReference(self::INVITATION_1_REFERENCE, $invitation1);
    }

    public function getDependencies()
    {
        return array(
            EventRouteFixtures::class,
            UserFixtures::class,
            SportTypeFixtures::class,
        );
    }
}
