<?php

namespace App\DataFixtures;

use App\Entity\SportType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SportTypeFixtures extends Fixture
{
    public const SPORT_TYPE_1_REFERENCE = 'pešo';
    public const SPORT_TYPE_2_REFERENCE = 'bežky';
    public const SPORT_TYPE_3_REFERENCE = 'bicyklom';
    public const SPORT_TYPE_4_REFERENCE = 'autobus';
    public const SPORT_TYPE_5_REFERENCE = 'zjazdové lyžovanie';
    public const SPORT_TYPE_6_REFERENCE = 'vodná turistika';
    public const SPORT_TYPE_7_REFERENCE = 'auto';
    public const SPORT_TYPE_8_REFERENCE = 'sedí sa';
    public const SPORT_TYPE_9_REFERENCE = 'vht';

    public function load(ObjectManager $manager)
    {
       
        $sportType1 = new SportType();
        $sportType1->setTitle('pešo');
        $sportType1->setSlug('peso');
        $sportType1->setDescription('po nohách');
        $sportType1->setShortcut('P');
        
        $manager->persist($sportType1);
        
        
        $sportType2 = new SportType();
        $sportType2->setTitle('bežky');
        $sportType2->setSlug('bezky');
        $sportType2->setDescription('lyžiarska turistika');
        $sportType2->setShortcut('L');
        
        $manager->persist($sportType2);
        
        
        $sportType3 = new SportType();
        $sportType3->setTitle('bicyklom');
        $sportType3->setSlug('cyklo');
        $sportType3->setDescription('cyklo');
        $sportType3->setShortcut('C');
        
        $manager->persist($sportType3);
        
        
        $sportType4 = new SportType();
        $sportType4->setTitle('autobus');
        $sportType4->setSlug('bus');
        $sportType4->setDescription('výlet s objednaným autobusom');
        $sportType4->setShortcut('BUS');
        
        $manager->persist($sportType4);
        
        
        $sportType5 = new SportType();
        $sportType5->setTitle('zjazdové l.');
        $sportType5->setSlug('zjazdove-lyzovanie');
        $sportType5->setDescription('zjazdové lyžovanie');
        $sportType5->setShortcut('Z');
        
        $manager->persist($sportType5);
        
        
        $sportType6 = new SportType();
        $sportType6->setTitle('voda');
        $sportType6->setSlug('voda');
        $sportType6->setDescription('vodná turistika');
        $sportType6->setShortcut('V');
        
        $manager->persist($sportType6);
        
        
        $sportType7 = new SportType();
        $sportType7->setTitle('auto');
        $sportType7->setSlug('auto');
        $sportType7->setDescription('ideme na výlet autami');
        $sportType7->setShortcut('A');
        
        $manager->persist($sportType7);
        
        
        $sportType8 = new SportType();
        $sportType8->setTitle('sedí sa');
        $sportType8->setSlug('sedi-sa');
        $sportType8->setDescription('podujatia typu schôdza a pod.');
        $sportType8->setShortcut('S');
        
        $manager->persist($sportType8);


        $sportType9 = new SportType();
        $sportType9->setTitle('VhT');
        $sportType9->setSlug('vht');
        $sportType9->setDescription('vysokohorská turistika');
        $sportType9->setShortcut('VhT');

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