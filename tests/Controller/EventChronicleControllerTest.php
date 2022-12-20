<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventChronicleControllerTest extends WebTestCase
{
    /** Test main chronicle page where should be list of years  */
    public function testShowAllYears(): void
    {
        $client = static::createClient();
        $client->request('GET', '/kronika');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Kronika');
    }

    /**
     * Test list of wrong links
     * @dataProvider provide404Urls
     */
    public function test404(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    /**
     * Get list of links
     * @return array List of links to check
     */
    public function provide404Urls(): array
    {
        return [
            ['/kronik'],
            ['/kronikaa'],
            ['/kronika/2000'],
            ['/kronika/3000'],
            ['/kronika/2010/jaskyne-uhradusdf'],
            ['/kronika/2012/jaskyne-uhradu'],
            ['/kronika/2010/jaskyne-uhradu/asdf'],
            ['/kronika/2011/asdf'],
            ['/kronika/2000/asdf'],
            ['/kronika/1asdf'],
            ['/kronika/asdf/asdf'],
        ];
    }

    /** Test list of existing chronicles per year */
    public function testShowListChroniclePostInYear(): void
    {
        $client = static::createClient();
        $client->request('GET', '/kronika/2010');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Klubová kronika z roku 2010');
    }

    /** Test existing chronicle */
    public function testShowChronicle(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/kronika/2010/jaskyne-uhradu');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Jaskyne Úhradu');
        $this->assertEquals('Trasa:', $crawler->filter('#routes > p > b')->text());
        $this->assertEquals('Podhradie – Opálená skala – Džimova spása – Úhrad – Podhradie (15 km).', $crawler->filterXPath('//*[@id="routes"]/p/text()')->text());
    }

    /**
     * Test links which required login
     * @dataProvider provide302Urls
     */
    public function testRequiredLogin(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $client->followRedirect();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Prosím, prihlás sa:');
    }

    public function provide302Urls(): array
    {
        return [
            ['/kronika/2000/pridat-novu/add'],
            ['/kronika/2000/pridat-novu/2020-10-25/add'],
            ['/kronika/2020/invitation-slug/edit'],
            ['/kronika/2020/invitation-slug/delete'],
            ['/kronika/2020/invitation-slug/delete/yes'],
        ];
    }

    /**
     * Test if admin get correct 404
     * @dataProvider provideAdmin404Urls
     */
    public function testAdmin404(string $url): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        
        $testUser = $userRepository->findOneByEmail('john.doe@example.com');
        $client->loginUser($testUser);
        $client->request('GET', $url);

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function provideAdmin404Urls(): array
    {
        return [
            ['/kronika/2011/jaskyne-uhradu/edit'],
            ['/kronika/2010/jaskyne-uhradu1/edit'],
        ];
    }

    /**
     * Test edit Event Chronicle
     * Open existing, check values, change values, save, edit - set previous values, save, check values
     */
    public function testEdit(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        
        $testUser = $userRepository->findOneByEmail('john.doe@example.com');
        $client->loginUser($testUser); // login admin

        $crawler = $client->request('GET', '/kronika/2010/jaskyne-uhradu/edit');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Upraviť túto kroniku');


        $form = $crawler->selectButton('Uložiť kroniku')->form();
        $values = $form->getPhpValues();
        $formName = $form->getName();

        // test existing values from form
        $content = '<p>Tradičn&yacute; v&yacute;stup na &Uacute;hrad sme si okorenili n&aacute;v&scaron;tevou troch jask&yacute;ň. Konečne ich m&aacute;me trochu zmapovan&eacute;. Osemn&aacute;sti sme pri&scaron;li do Podhradia. Op&aacute;len&aacute; jaskyňu sme nav&scaron;t&iacute;vili za &scaron;tyri dni po tret&iacute; kr&aacute;t. Viac necestou ako cestou sme pri&scaron;li na vrchol Vini&scaron;ťa. Tu sa vytvorila rojnica z najlep&scaron;&iacute;ch stop&aacute;rov. Za p&aacute;r min&uacute;t n&aacute;m piskotom ozn&aacute;mili n&aacute;jdenie jaskyne. Vchod je uzatvoren&yacute;, ale z dostupn&yacute;ch fotografi&iacute; je jasn&eacute;, že skr&yacute;va vo vn&uacute;tri pekn&uacute; kvapľov&uacute; v&yacute;zdobu. Čas trochu pokročil. Na &Uacute;hrade m&aacute; byť pripraven&eacute; varen&eacute; v&iacute;no. Aj preto vyhr&aacute;va n&aacute;pad, &iacute;sť najsk&ocirc;r k tepl&eacute;mu perliv&eacute;mu moku, ako k tretej jaskyni. Stopy v snehu hovorili, že niekto pred nami i&scaron;iel. Podľa hĺbky st&ocirc;p sa n&aacute;m nezdalo, že niesol ťažk&yacute; n&aacute;klad a aj pachov&eacute; stopy neboli jasn&eacute;. Napriek tomu sme nepochybovali, že tes&aacute;rčania už tancuj&uacute; tanec nad kotl&iacute;kom naplnen&yacute;m v&iacute;nom. Na&scaron;e sklamanie na vrchole bolo obrovsk&eacute;. Oheň žiadny. Psychol&oacute;govia museli rie&scaron;iť hlbok&eacute; stavy depresie. Doporučili n&aacute;m, aby sme si sami založili oheň. Podarilo sa. Plaziv&yacute; dym pritiahol na vrchol Bojňancov. Počastovali sme sa stvrdnut&yacute;m, ale st&aacute;le veľmi dobr&yacute;m, vianočn&yacute;m pečivom. Povin&scaron;ovali si v&scaron;etko dobr&eacute;. A tu ho m&aacute;&scaron;. Kde sa vzal, tu sa vzal, konečne sa objavil Maro&scaron; so svoj&iacute;m čarovn&yacute;m kotl&iacute;kom. Dočkali sme sa teda aj varen&eacute;ho v&iacute;na. Počas n&aacute;&scaron;ho pobytu hore sa spustila riadna metelica. Azda aj t&aacute; sp&ocirc;sobila, že veľa ľud&iacute; sa pon&aacute;hľalo na skor&scaron;&iacute;  autobus. T&iacute; vern&iacute; po vypr&aacute;zdnen&iacute; dvoch kotl&iacute;kov sa pobrali e&scaron;te k tretej jaskyni. Vo v&scaron;etk&yacute;ch sa pracuje. Nie intenz&iacute;vne, ale vidieť nov&eacute; k&ocirc;pky zeme. Pri n&aacute;vrate do Podhradia sme sa zastavili pri hrade. Vstup doň je uzatvoren&yacute;, čo je hor&scaron;ie, ako keď je zak&aacute;zan&yacute;. Opravuje sa. Autobus&aacute;r n&aacute;s r&aacute;d priv&iacute;tal. My mu totiž zabezpečujeme pr&aacute;cu. Spoj je veden&yacute; ako vyťažen&yacute;.</p>
<p>Zap&iacute;sal Peter.</p>';
        $this->assertEquals('Jaskyne Úhradu', $values[$formName]['title']);
        $this->assertEquals('Novoročný výstup na Úhrad sme spojili s návštevou jaskýň.', $values[$formName]['summary']);
        $this->assertEquals($content, $values[$formName]['content']);
        $this->assertEquals('2010-01-02T00:00:00', $values[$formName]['startDate']);
        $this->assertEquals('bezky', $values[$formName]['sportType'][1]);
        $this->assertEquals('zjazdove-lyzovanie', $values[$formName]['sportType'][4]);
        $this->assertEquals('Podhradie – Opálená skala – Džimova spása – Úhrad – Podhradie', $values[$formName]['routes'][0]['title']);
        $this->assertEquals(15, $values[$formName]['routes'][0]['length']);

        // edit values
        $values[$formName]['title'] = 'Jaskyne Úhradu1';
        $values[$formName]['summary'] = 'Novoročný výstup na Úhrad sme spojili s návštevou jaskýň1.';
        $values[$formName]['content'] = '<p>Test</p>';
        $values[$formName]['startDate'] = '2010-01-02T00:00:00';
        unset($values[$formName]['sportType'][1]);
        $values[$formName]['sportType'][3] = 'bus';
        $values[$formName]['sportType'][4] = 'zjazdove-lyzovanie';
        $values[$formName]['routes'][0]['title'] = 'Podhradie – Opálená skala – Džimova spása – Úhrad – Podhradie1'; //Edit Route #1
        $values[$formName]['routes'][0]['length'] = 20; // edit Route #1
        $values[$formName]['routes'][1]['title'] = 'Podhradie – Opálená skala – Džimova spása – Podhradie'; // Add new Route #2
        $values[$formName]['routes'][1]['length'] = 10; // add new Route #2

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles()); // save
        

        $this->assertEquals('/kronika/2010/Jaskyne-Uhradu1', $client->getResponse()->headers->get('location'));
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $crawler = $client->followRedirect();

        // checked if new values has been saved
        $this->assertSelectorTextContains('html h1', 'Jaskyne Úhradu1');
        $this->assertEquals('Trasy:', $crawler->filter('#routes > p')->text());
        $this->assertEquals('Podhradie – Opálená skala – Džimova spása – Úhrad – Podhradie1 (20 km)', $crawler->filterXPath('//*[@id="routes"]/ul/li[1]')->text());
        $this->assertEquals('Podhradie – Opálená skala – Džimova spása – Podhradie (10 km)', $crawler->filterXPath('//*[@id="routes"]/ul/li[2]')->text());

        $link = $crawler->selectLink('Upraviť túto')->link();
        $crawler = $client->click($link); // clik on button Edit

        $this->assertResponseIsSuccessful(); // new page loaded
        $this->assertSelectorTextContains('html h1', 'Upraviť túto kroniku');


        $form = $crawler->selectButton('Uložiť kroniku')->form(); // select form
        $values = $form->getPhpValues();
        $formName = $form->getName();

        // test if content has been changed
        $this->assertEquals('<p>Test</p>', $values[$formName]['content']);

        // set previous values
        $values[$formName]['title'] = 'Jaskyne Úhradu';
        $values[$formName]['summary'] = 'Novoročný výstup na Úhrad sme spojili s návštevou jaskýň.';
        $values[$formName]['content'] = $content;
        $values[$formName]['startDate'] = '2010-01-02T00:00:00';
        $values[$formName]['sportType'][1] = 'bezky';
        unset($values[$formName]['sportType'][3]); // remove one sportType
        $values[$formName]['sportType'][4] = 'zjazdove-lyzovanie';
        $values[$formName]['routes'][0]['title'] = 'Podhradie – Opálená skala – Džimova spása – Úhrad – Podhradie';
        $values[$formName]['routes'][0]['length'] = 15;
        unset($values[$formName]['routes'][1]); // delete second route (both title and length)
      
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertEquals('/kronika/2010/Jaskyne-Uhradu', $client->getResponse()->headers->get('location'));
        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $crawler = $client->followRedirect();
        $this->assertSelectorTextContains('html h1', 'Jaskyne Úhradu'); // chronicle has the same values as before test
    }

    public function testCreateDelete(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        
        $testUser = $userRepository->findOneByEmail('john.doe@example.com');
        $client->loginUser($testUser); // login admin

        $crawler = $client->request('GET', '/kronika/2010');
        
        
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Pridať novú kroniku')->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('html h1', 'Pridanie novej kroniky');
        
        $buttonCrawlerNode = $crawler->selectButton('Vytvor kroniku');

        $formStartDate = $buttonCrawlerNode->form();
        $formNameStartDate = $formStartDate->getName();

        // set start date in form
        $formStartDate[$formNameStartDate . '[startDate]'] = '2010-01-23';

        $client->submit($formStartDate);


        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $crawler = $client->followRedirect();
        
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Vytvoriť novú kroniku');


        // select the form that contains this button
        $form = $crawler->selectButton('Uložiť kroniku')->form();

        $formName = $form->getName();
      
       // $form[$formName . '[sportType][0]']->tick();
       // $form[$formName . '[sportType][3]']->tick();

        $values = $form->getPhpValues();
        $values[$formName]['summary'] = 'Autobusový výlet za snehom. Bežky, zjazdové lyžovanie';
        $values[$formName]['content'] = '<p>Išli sme na bežky, zjazdové lyžovanie.</p><p><b>Odchod:</b> 7,00 od Tesca<br/><b>Príchod:</b> 19,00.</p>';
        $values[$formName]['endDate'] = '2010-01-24T00:00:00';
        $values[$formName]['sportType'][0] = 'peso';
        $values[$formName]['sportType'][1] = 'bus';
        $values[$formName]['routes'][0]['title'] = 'Donovaly - Chopok - Ďumbier a späť';
        $values[$formName]['routes'][0]['length'] = 10;
        $values[$formName]['routes'][1]['title'] = 'Donovaly - Krížna a späť';
        $values[$formName]['routes'][1]['length'] = 20;

        // submits the form with the existing and new values
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertEquals('/kronika/2010/Vyjazd-za-snehom', $client->getResponse()->headers->get('location'));
        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $crawler = $client->followRedirect();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Výjazd za snehom');

        // Check if start date is the same as was set at the beginning
        $this->assertEquals('23. 1. 2010', $crawler->filterXPath('//*[@id="start-date"]')->text());
        $this->assertEquals('24. 1. 2010', $crawler->filterXPath('//*[@id="end-date"]')->text());
        $this->assertEquals('Trasy:', $crawler->filter('#routes > p')->text());
        $this->assertEquals('Donovaly - Chopok - Ďumbier a späť (10 km)', $crawler->filterXPath('//*[@id="routes"]/ul/li[1]')->text());
        $this->assertEquals('Donovaly - Krížna a späť (20 km)', $crawler->filterXPath('//*[@id="routes"]/ul/li[2]')->text());

        $link = $crawler->selectLink('Chcem zmazať')->link();
        $crawler = $client->click($link);
        
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $client->followRedirect();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Klubová kronika z roku 2010');
    }
}
