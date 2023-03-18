<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\SportType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Contracts\Translation\TranslatorInterface;

class SportTypeFixtures extends Fixture
{
    use SlugTrait;

    public const SPORT_TYPE_1_REFERENCE = 'hiking';
    public const SPORT_TYPE_2_REFERENCE = 'x-country-ski';
    public const SPORT_TYPE_3_REFERENCE = 'cycling';
    public const SPORT_TYPE_4_REFERENCE = 'bus';
    public const SPORT_TYPE_5_REFERENCE = 'ski';
    public const SPORT_TYPE_6_REFERENCE = 'water';
    public const SPORT_TYPE_7_REFERENCE = 'car';
    public const SPORT_TYPE_8_REFERENCE = 'meet';
    public const SPORT_TYPE_9_REFERENCE = 'scrambling';

    public function __construct(
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        $sportType1 = new SportType();
        $sportType1->setTitle($this->translator->trans('sportTypes.hiking.name'));
        $sportType1->setSlug($this->createSlug($this->translator->trans('sportTypes.hiking.slug')));
        $sportType1->setDescription($this->translator->trans('sportTypes.hiking.desc'));
        $sportType1->setShortcut($this->translator->trans('sportTypes.hiking.shortcut'));
        $manager->persist($sportType1);

        $sportType2 = new SportType();
        $sportType2->setTitle($this->translator->trans('sportTypes.xCountrySki.name'));
        $sportType2->setSlug($this->createSlug($this->translator->trans('sportTypes.xCountrySki.slug')));
        $sportType2->setDescription($this->translator->trans('sportTypes.xCountrySki.desc'));
        $sportType2->setShortcut($this->translator->trans('sportTypes.xCountrySki.shortcut'));
        $manager->persist($sportType2);

        $sportType3 = new SportType();
        $sportType3->setTitle($this->translator->trans('sportTypes.cycling.name'));
        $sportType3->setSlug($this->createSlug($this->translator->trans('sportTypes.cycling.slug')));
        $sportType3->setDescription($this->translator->trans('sportTypes.cycling.desc'));
        $sportType3->setShortcut($this->translator->trans('sportTypes.cycling.shortcut'));
        $manager->persist($sportType3);

        $sportType4 = new SportType();
        $sportType4->setTitle($this->translator->trans('sportTypes.bus.name'));
        $sportType4->setSlug($this->createSlug($this->translator->trans('sportTypes.bus.slug')));
        $sportType4->setDescription($this->translator->trans('sportTypes.bus.desc'));
        $sportType4->setShortcut($this->translator->trans('sportTypes.bus.shortcut'));
        $manager->persist($sportType4);

        $sportType5 = new SportType();
        $sportType5->setTitle($this->translator->trans('sportTypes.ski.name'));
        $sportType5->setSlug($this->createSlug($this->translator->trans('sportTypes.ski.slug')));
        $sportType5->setDescription($this->translator->trans('sportTypes.ski.desc'));
        $sportType5->setShortcut($this->translator->trans('sportTypes.ski.shortcut'));
        $manager->persist($sportType5);

        $sportType6 = new SportType();
        $sportType6->setTitle($this->translator->trans('sportTypes.water.name'));
        $sportType6->setSlug($this->createSlug($this->translator->trans('sportTypes.water.slug')));
        $sportType6->setDescription($this->translator->trans('sportTypes.water.desc'));
        $sportType6->setShortcut($this->translator->trans('sportTypes.water.shortcut'));
        $manager->persist($sportType6);

        $sportType7 = new SportType();
        $sportType7->setTitle($this->translator->trans('sportTypes.car.name'));
        $sportType7->setSlug($this->createSlug($this->translator->trans('sportTypes.car.slug')));
        $sportType7->setDescription($this->translator->trans('sportTypes.car.desc'));
        $sportType7->setShortcut($this->translator->trans('sportTypes.car.shortcut'));
        $manager->persist($sportType7);

        $sportType8 = new SportType();
        $sportType8->setTitle($this->translator->trans('sportTypes.meet.name'));
        $sportType8->setSlug($this->createSlug($this->translator->trans('sportTypes.meet.slug')));
        $sportType8->setDescription($this->translator->trans('sportTypes.meet.desc'));
        $sportType8->setShortcut($this->translator->trans('sportTypes.meet.shortcut'));
        $manager->persist($sportType8);

        $sportType9 = new SportType();
        $sportType9->setTitle($this->translator->trans('sportTypes.scrambling.name'));
        $sportType9->setSlug($this->createSlug($this->translator->trans('sportTypes.scrambling.slug')));
        $sportType9->setDescription($this->translator->trans('sportTypes.scrambling.desc'));
        $sportType9->setShortcut($this->translator->trans('sportTypes.scrambling.shortcut'));
        $manager->persist($sportType9);
        $manager->flush();

        $this->addReference(self::SPORT_TYPE_1_REFERENCE, $sportType1);
        $this->addReference(self::SPORT_TYPE_2_REFERENCE, $sportType2);
        $this->addReference(self::SPORT_TYPE_3_REFERENCE, $sportType3);
        $this->addReference(self::SPORT_TYPE_4_REFERENCE, $sportType4);
        $this->addReference(self::SPORT_TYPE_5_REFERENCE, $sportType5);
        $this->addReference(self::SPORT_TYPE_6_REFERENCE, $sportType6);
        $this->addReference(self::SPORT_TYPE_7_REFERENCE, $sportType7);
        $this->addReference(self::SPORT_TYPE_8_REFERENCE, $sportType8);
        $this->addReference(self::SPORT_TYPE_9_REFERENCE, $sportType9);
    }
}
