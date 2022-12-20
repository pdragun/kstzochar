<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Blog;
use App\DataFixtures\BlogSectionFixtures;
use App\DataFixtures\UserFixtures;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class BlogFixtures extends Fixture implements DependentFixtureInterface
{
    use SlugTrait;
    public function load(ObjectManager $manager)
    {
       
        $blog1 = new Blog();
        $blog1->setTitle('História turistiky');
        $blog1->setSlug($this->createSlug('historia-turistiky'));
        $blog1->setSummary('Krátky článok o začiatkoch turistiky vo svete, na slovensku a v regióne');
        $blog1->setStartDate(null);
        $blog1->setPublishedAt(null);
        $blog1->setCreatedAt(new DateTimeImmutable('2011-08-23 14:32:50'));
        $blog1->setModifiedAt(null);
        $blog1->setStartDate(null);
        $blog1->setPublish(true);
        $blog1->setContent('<h2>Začiatky turistiky v Eur&oacute;pe a svete</h2>
        <p>Franc&uacute;zsky spisovateľ Stendhal po prv&yacute;kr&aacute;t použil slovo turista v&nbsp;roku 1838 vo svojej povesti &bdquo;Memoires d&acute; un touriste (Spomienky turistu).</p>
        <p>Začiatky turistiky s&uacute; spojen&eacute; s&nbsp;vyhl&aacute;seniami viacer&yacute;ch mysliteľov 17. a&nbsp;18. stor. o&nbsp;potrebe n&aacute;vratu človeka k&nbsp;pr&iacute;rode, čo prispelo k&nbsp;rozvoju cestovania a&nbsp;služieb. V&nbsp;18. stor. nab&aacute;dal Jean Jacques Rousseau zv&yacute;&scaron;iť z&aacute;ujem o&nbsp;pr&iacute;rodu a&nbsp;využiť ju pri v&yacute;chove. Vzniklo niekoľko turistick&yacute;ch spoločnost&iacute;: 1857 Alpine Club, 1862 &Ouml;sterreichische Alpenverein&hellip; .</p>
        <h2>Začiatky turistiky na Slovensku</h2>
        <p>Uhorsk&yacute; karpatsk&yacute; spolok bol založen&yacute; 10. augusta 1873 v&nbsp;Starom Smokovci. Turistick&uacute; činnosť rozv&iacute;jal hlavne vo Vysok&yacute;ch Tatr&aacute;ch. Sem siahaj&uacute; aj začiatky ochrany pr&iacute;rody, sprievodcovskej a&nbsp;z&aacute;chran&aacute;rskej činnosti, značenia turistick&yacute;ch tr&aacute;s, budovania ch&aacute;t, &uacute;tuln&iacute;.</p>
        <h2>Hist&oacute;ria turistiky v regi&oacute;ne Topoľčany</h2>
        <p>Toto je pole neoran&eacute;. M&aacute;m len inform&aacute;cie o niektor&yacute;ch ľuďoch, ktor&iacute; sa venovali organizovanej turistike: J&aacute;n Vizner, Jir&aacute;nkovci, Petrovičovci, Lajko &Scaron;vec, Oldo Bohata, Karol Červeňansk&yacute;, Eva Čerevkov&aacute;, Bohu&scaron; Slov&aacute;k, Janko Koln&iacute;k, Cyril Mik&aacute;t, Stano Goga, Jožko Mal&iacute;ček, Du&scaron;an Stanček, Jožo Želiska, Inka Zn&aacute;&scaron;ikov&aacute;, Jožko Urban&hellip; . A isto mnoho ďal&scaron;&iacute;ch. Ak niekto vie viac o hist&oacute;rii, pros&iacute;m, aby sa ohl&aacute;sil.</p>
        <h2>S&uacute;časnosť</h2>
        <p>Stav turistiky v Topoľčianskom regi&oacute;ne koncom roka 2010: registrovan&yacute;ch je 13 klubov s 598 členmi. S&uacute; to kluby: TK Javor Bo&scaron;any, Spartak B&aacute;novce nad Bebravou, Kamar&aacute;t Partiz&aacute;nske, Alpin klub Jacovce, Kroko &ndash; Velo Tes&aacute;re, Horňan Praznovce, KST Bojn&aacute;, Ostr&aacute; Veľk&yacute; Kl&iacute;ž, Borina Nitrianska Streda, KLUT Urmince, KST Tribeč Kovarce, KST Solčany a topoľčiansky Žoch&aacute;r. Organizačne s&uacute; kluby registrovan&eacute; v region&aacute;lnej rade (RR) Topoľčany.</p>');
        $blog1->setCreatedBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE));
        $blog1->setSection($this->getReference(BlogSectionFixtures::BLOG_SECTION_1_REFERENCE));
        $manager->persist($blog1);

        $blog2 = new Blog();
        $blog2->setTitle('Nízke Tatry');
        $blog2->setSlug($this->createSlug('nizke-tatry'));
        $blog2->setSummary('Bulharsko sklamalo, Nízke Tatry potešia. V termíne 27. 8. až 2. 9. sa pozrieme do Nízkych Tatier.');
        $blog2->setStartDate(new DateTimeImmutable('2011-08-27'));
        $blog2->setPublishedAt(null);
        $blog2->setCreatedAt(new DateTimeImmutable('2011-08-24 12:33:30'));
        $blog2->setModifiedAt(null);
        $blog2->setPublish(true);
        $blog2->setContent('<p>Bulharsko sklamalo, N&iacute;zke Tatry pote&scaron;ia. V term&iacute;ne 27. 8. až 2. 9. sa pozrieme do N&iacute;zkych Tatier.</p>
        <p>Odchod v sobotu 27. 8. o 8,00 z parkoviska Tesca v Topoľčanoch na aut&aacute;ch. Zastav&iacute;me sa na Sklabiňskom hrade odtiaľ si urob&iacute;me t&uacute;ru na  Katovu skala a sp&auml;ť. Je to vo Veľkej Fatre. Po ubytovan&iacute; konzum&aacute;cia dom&aacute;cich surov&iacute;n. Program na ďal&scaron;ie dni &ndash; nez&aacute;v&auml;zn&yacute;, poradie meniteľn&eacute;:</p>
        <ol>
        <li>Lanovkou na Chopok &ndash; Ďumbier &ndash; Krakova hoľa &ndash; Dem&auml;novsk&aacute; dolina cca 8 hod</li>
        <li>Lanovkou na Chopok &ndash; Dere&scaron;e &ndash; Poľana &ndash; Bory &ndash; Sin&aacute; &ndash; Dem&auml;novsk&aacute; 	dolina cca 8 hod</li>
        <li>Symbolick&yacute; cintor&iacute;n &ndash; Vrbick&eacute; pleso &ndash; n&aacute;v&scaron;teva jaskyne - relax v popoludňaj&scaron;&iacute;ch hodin&aacute;ch</li>
        <li>Iľanovo &ndash; Poludnica &ndash; K&uacute;peľ &ndash; Iľanovo cca 6 hod</li>
        <li>K&uacute;panie v Tatralandii (m&ocirc;že byť v spojen&iacute; s kr&aacute;tkou t&uacute;rou)</li>
        <li>Ľadov&aacute; jaskyňa - dolina Vyvierania &ndash; Dem&auml;novsk&aacute; dolina &ndash; relax v popoludňaj&scaron;&iacute;ch hodin&aacute;ch</li>
        </ol>
        <p>Pod pojmom relax m&ocirc;že byť k&uacute;panie v Liptovskom J&aacute;ne. Plavky bezpodmienečne nutn&eacute;. Nafukovacie koleso nemus&iacute;, ak bude polystyr&eacute;n v bruchu.</p>
        <p>Pros&iacute;m o z&aacute;v&auml;zn&eacute; prihl&aacute;senie do 10.8. V tomto term&iacute;ne s&uacute; dva dni voľn&eacute;. M&ocirc;že byť n&aacute;val na ubytovanie. Ukončenie pobytu v piatok 2. 9. s n&aacute;vratom večer. Ubytovanie v chat&aacute;ch, penzi&oacute;ne... Čo bude voľn&eacute; pre na&scaron;u skupinu. Cenu ubytovania predpoklad&aacute;m do 15 &euro;. Strava m&ocirc;že byť aj vlastn&aacute;, ale možnosti stravovania s&uacute; veľk&eacute;. Vybavenie ako do vysok&yacute;ch h&ocirc;r (vetrovka, čapica, rukavice...).</p>');
        $blog2->setCreatedBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE));
        $blog2->setSection($this->getReference(BlogSectionFixtures::BLOG_SECTION_2_REFERENCE));
        $manager->persist($blog2);

        $blog3 = new Blog();
        $blog3->setTitle('Čergovské sušienky');
        $blog3->setSlug($this->createSlug('cergovske-susienky'));
        $blog3->setSummary('Recept na chutné suché a zdravé koláčiky.');
        $blog3->setStartDate(new DateTimeImmutable('2011-08-27'));
        $blog3->setPublishedAt(null);
        $blog3->setCreatedAt(new DateTimeImmutable('2011-08-24 12:33:30'));
        $blog3->setModifiedAt(null);
        $blog3->setStartDate(null);
        $blog3->setPublish(true);
        $blog3->setContent('<p>Turistami vysk&uacute;&scaron;an&eacute; v m&aacute;ji 2015 na Čergove.</p>
        <p>Potrebujeme:</p>
        <ul>
        <li>300 g polohrubej m&uacute;ky,</li>
        <li>200 g ovsen&yacute;ch vločiek,</li>
        <li>400 g kry&scaron;t&aacute;lov&eacute;ho cukru,</li>
        <li>200 g mlet&yacute;ch orechov,</li>
        <li>150 g masla alebo rastl. tuku,</li>
        <li>2 lyžice medu,</li>
        <li>50-100 ml teplej vody,</li>
        <li>1,5 lyžičky s&oacute;dy bikarbony.</li>
        </ul>
        <p>V mise zmie&scaron;ame m&uacute;ku, vločky, cukor, orechy. Vo vodnom k&uacute;peli rozpust&iacute;me maslo, prid&aacute;me med, tepl&uacute; vodu, s&oacute;du bikarbonu a premie&scaron;ame. Spoj&iacute;me so sypkou zmesou a vypracujeme cesto. Podľa potreby opatrne po lyžičk&aacute;ch prid&aacute;vame tepl&uacute; vodu, aby sa cesto dalo dobre tvarovať. Z cesta vypracujeme guľky a ulož&iacute;me ich na plech vyložen&yacute; papierom na pečenie. Guľky umiestnime ďalej od seba, pretože cesto sa mierne roztečie. Vlož&iacute;me do vyhriatej r&uacute;ry a pečieme cca 12 min&uacute;t pri teplote 160 stupňov.</p>
        <p>Dobr&uacute; chuť.</p>');
        $blog3->setCreatedBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE));
        $blog3->setSection($this->getReference(BlogSectionFixtures::BLOG_SECTION_3_REFERENCE));
        $manager->persist($blog3);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            BlogSectionFixtures::class,
            UserFixtures::class,
        ];
    }
}
