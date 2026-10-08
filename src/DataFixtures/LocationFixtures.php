<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Location;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LocationFixtures extends Fixture
{
    public const LOCATION_TESARE_REFERENCE = 'location-tesare';

    public function load(ObjectManager $manager): void
    {
        // only the required fields and a region, used by an invitation
        $tesare = new Location();
        $tesare->setName('Tesáre, futbalové ihrisko');
        $tesare->setAddressLocality('Tesáre');
        $tesare->setAddressRegion('Nitriansky kraj');
        $tesare->setAddressCountry('SK');
        $manager->persist($tesare);

        // all fields, not used by any invitation (approximate coordinates, fixture data only)
        $topolcany = new Location();
        $topolcany->setName('Topoľčany, autobusová stanica');
        $topolcany->setAddressLocality('Topoľčany');
        $topolcany->setStreetAddress('Stummerova');
        $topolcany->setPostalCode('955 01');
        $topolcany->setAddressRegion('Nitriansky kraj');
        $topolcany->setAddressCountry('SK');
        $topolcany->setLatitude(48.5577);
        $topolcany->setLongitude(18.1748);
        $manager->persist($topolcany);

        $manager->flush();
        $this->addReference(self::LOCATION_TESARE_REFERENCE, $tesare);
    }
}
