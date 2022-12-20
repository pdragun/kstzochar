<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\EventChronicle;
use App\DataFixtures\EventRouteFixtures;
use App\DataFixtures\UserFixtures;
use DateTimeImmutable;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EventChronicleFixtures extends Fixture implements DependentFixtureInterface
{
    use SlugTrait;
    public function load(ObjectManager $manager): void
    {
        $now = new DateTimeImmutable();

        $chronicle1 = new EventChronicle();
        $chronicle1->setTitle('Jaskyne Úhradu');
        $chronicle1->setSlug($this->createSlug('jaskyne-uhradu'));
        $chronicle1->setSummary('Novoročný výstup na Úhrad sme spojili s návštevou jaskýň.');
        $chronicle1->setStartDate(new DateTimeImmutable('2010-01-02'));
        $chronicle1->setPublishedAt($now);
        $chronicle1->setCreatedAt($now);
        $chronicle1->setModifiedAt(null);
        $chronicle1->setPublish(true);
        $chronicle1->setContent('<p>Tradičn&yacute; v&yacute;stup na &Uacute;hrad sme si okorenili n&aacute;v&scaron;tevou troch jask&yacute;ň. Konečne ich m&aacute;me trochu zmapovan&eacute;. Osemn&aacute;sti sme pri&scaron;li do Podhradia. Op&aacute;len&aacute; jaskyňu sme nav&scaron;t&iacute;vili za &scaron;tyri dni po tret&iacute; kr&aacute;t. Viac necestou ako cestou sme pri&scaron;li na vrchol Vini&scaron;ťa. Tu sa vytvorila rojnica z najlep&scaron;&iacute;ch stop&aacute;rov. Za p&aacute;r min&uacute;t n&aacute;m piskotom ozn&aacute;mili n&aacute;jdenie jaskyne. Vchod je uzatvoren&yacute;, ale z dostupn&yacute;ch fotografi&iacute; je jasn&eacute;, že skr&yacute;va vo vn&uacute;tri pekn&uacute; kvapľov&uacute; v&yacute;zdobu. Čas trochu pokročil. Na &Uacute;hrade m&aacute; byť pripraven&eacute; varen&eacute; v&iacute;no. Aj preto vyhr&aacute;va n&aacute;pad, &iacute;sť najsk&ocirc;r k tepl&eacute;mu perliv&eacute;mu moku, ako k tretej jaskyni. Stopy v snehu hovorili, že niekto pred nami i&scaron;iel. Podľa hĺbky st&ocirc;p sa n&aacute;m nezdalo, že niesol ťažk&yacute; n&aacute;klad a aj pachov&eacute; stopy neboli jasn&eacute;. Napriek tomu sme nepochybovali, že tes&aacute;rčania už tancuj&uacute; tanec nad kotl&iacute;kom naplnen&yacute;m v&iacute;nom. Na&scaron;e sklamanie na vrchole bolo obrovsk&eacute;. Oheň žiadny. Psychol&oacute;govia museli rie&scaron;iť hlbok&eacute; stavy depresie. Doporučili n&aacute;m, aby sme si sami založili oheň. Podarilo sa. Plaziv&yacute; dym pritiahol na vrchol Bojňancov. Počastovali sme sa stvrdnut&yacute;m, ale st&aacute;le veľmi dobr&yacute;m, vianočn&yacute;m pečivom. Povin&scaron;ovali si v&scaron;etko dobr&eacute;. A tu ho m&aacute;&scaron;. Kde sa vzal, tu sa vzal, konečne sa objavil Maro&scaron; so svoj&iacute;m čarovn&yacute;m kotl&iacute;kom. Dočkali sme sa teda aj varen&eacute;ho v&iacute;na. Počas n&aacute;&scaron;ho pobytu hore sa spustila riadna metelica. Azda aj t&aacute; sp&ocirc;sobila, že veľa ľud&iacute; sa pon&aacute;hľalo na skor&scaron;&iacute;  autobus. T&iacute; vern&iacute; po vypr&aacute;zdnen&iacute; dvoch kotl&iacute;kov sa pobrali e&scaron;te k tretej jaskyni. Vo v&scaron;etk&yacute;ch sa pracuje. Nie intenz&iacute;vne, ale vidieť nov&eacute; k&ocirc;pky zeme. Pri n&aacute;vrate do Podhradia sme sa zastavili pri hrade. Vstup doň je uzatvoren&yacute;, čo je hor&scaron;ie, ako keď je zak&aacute;zan&yacute;. Opravuje sa. Autobus&aacute;r n&aacute;s r&aacute;d priv&iacute;tal. My mu totiž zabezpečujeme pr&aacute;cu. Spoj je veden&yacute; ako vyťažen&yacute;.</p>
<p>Zap&iacute;sal Peter.</p>');
        $chronicle1->setCreatedBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE));
        $chronicle1->setAuthorBy($this->getReference(UserFixtures::ADMIN_USER_REFERENCE));
        $chronicle1->addRoute($this->getReference(EventRouteFixtures::EVENT_ROUTE_FOR_CHRONICLE_REFERENCE));
        $chronicle1->addSportType($this->getReference(SportTypeFixtures::SPORT_TYPE_2_REFERENCE));
        $chronicle1->addSportType($this->getReference(SportTypeFixtures::SPORT_TYPE_5_REFERENCE));

        $manager->persist($chronicle1);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            EventRouteFixtures::class,
            UserFixtures::class,
        ];
    }
}
