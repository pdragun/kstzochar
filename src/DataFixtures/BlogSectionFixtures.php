<?php

namespace App\DataFixtures;

use App\Entity\BlogSection;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\AbstractUnicodeString;
use Symfony\Component\String\Slugger\AsciiSlugger;

class BlogSectionFixtures extends Fixture
{
    public const BLOG_SECTION_1_REFERENCE = 'blog-section-z-klubovej';
    public const BLOG_SECTION_2_REFERENCE = 'blog-section-viacdnove';
    public const BLOG_SECTION_3_REFERENCE = 'blog-section-receptury';

    public function load(ObjectManager $manager)
    {

        $blogSection1 = new BlogSection();
        $blogSection1->setTitle('z klubovej kuchyne');
        $blogSection1->setSlug($this->createSlug('z-klubovej-kuchyne'));

        $manager->persist($blogSection1);

        $blogSection2 = new BlogSection();
        $blogSection2->setTitle('viacdňové akcie');
        $blogSection2->setSlug($this->createSlug('viacdnove-akcie'));

        $manager->persist($blogSection2);

        $blogSection3 = new BlogSection();
        $blogSection3->setTitle('receptúry na túry');
        $blogSection3->setSlug($this->createSlug('receptury-na-tury'));

        $manager->persist($blogSection3);
        $manager->flush();

        $this->addReference(self::BLOG_SECTION_1_REFERENCE, $blogSection1);
        $this->addReference(self::BLOG_SECTION_2_REFERENCE, $blogSection2);
        $this->addReference(self::BLOG_SECTION_3_REFERENCE, $blogSection3);
    }

    private function createSlug(string $slug): AbstractUnicodeString
    {
        $slugger = new AsciiSlugger();

        return $slugger->slug($slug);
    }
}
