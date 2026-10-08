<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Location;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LocationTest extends KernelTestCase
{
    public function testSettersNormalizeWhitespace(): void
    {
        $location = new Location()
            ->setName("  Tesáre,\t futbalové   ihrisko ")
            ->setAddressLocality(' Tesáre ')
            ->setStreetAddress('   ')
            ->setPostalCode(null);

        $this->assertSame('Tesáre, futbalové ihrisko', $location->getName());
        $this->assertSame('Tesáre', $location->getAddressLocality());
        $this->assertNull($location->getStreetAddress());
        $this->assertNull($location->getPostalCode());
        $this->assertSame('SK', $location->getAddressCountry());
    }

    public function testCoordinatesComeInPairs(): void
    {
        $location = new Location()->setName('Nové miesto')->setAddressLocality('Topoľčany');
        $this->assertCount(0, $this->validate($location));

        $location->setLatitude(48.5);
        $violations = $this->validate($location);
        $this->assertCount(1, $violations);
        $this->assertSame('longitude', $violations[0]->getPropertyPath());

        $location->setLatitude(null)->setLongitude(18.1);
        $violations = $this->validate($location);
        $this->assertCount(1, $violations);
        $this->assertSame('latitude', $violations[0]->getPropertyPath());

        $location->setLatitude(48.5);
        $this->assertCount(0, $this->validate($location));
    }

    public function testRequiredFieldsAndRanges(): void
    {
        $location = new Location()->setAddressCountry('XX')->setLatitude(91.0)->setLongitude(-181.0);

        $paths = [];
        foreach ($this->validate($location) as $violation) {
            $paths[] = $violation->getPropertyPath();
        }
        sort($paths);

        $this->assertSame(['addressCountry', 'addressLocality', 'latitude', 'longitude', 'name'], $paths);
    }

    private function validate(Location $location): ConstraintViolationListInterface
    {
        self::bootKernel();

        return static::getContainer()->get(ValidatorInterface::class)->validate($location);
    }
}
