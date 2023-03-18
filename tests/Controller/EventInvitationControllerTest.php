<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventInvitationControllerTest extends WebTestCase
{

    /** Test main invitation page where should be list of years */
    public function testShowAllYears(): void
    {
        $client = static::createClient();
        $client->request('GET', '/pozvanky');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Pozvánky');
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

    public function provide404Urls(): array
    {
        return [
            ['/pozvank'],
            ['/pozvankya'],
            ['/pozvanky/aktualn'],
            ['/pozvanky/aktualnea'],
            ['/pozvanky/2000'],
            ['/pozvanky/3000'],
            ['/pozvanky/2011/gulasove-opojenie-v-tesarochasdf'],
            ['/pozvanky/2015/gulasove-opojenie-v-tesaroch'],
            ['/pozvanky/2011/gulasove-opojenie-v-tesaroch/asdf'],
            ['/pozvanky/2011/asdf'],
            ['/pozvanky/2000/asdf'],
            ['/pozvanky/1asdf'],
            ['/pozvanky/asdf/200'],
        ];
    }

    /** Test list of existing invitations per year */
    public function testShowListInvitationPostInYear(): void
    {
        $client = static::createClient();
        $client->request('GET', '/pozvanky/2011');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Pozvánky z roku 2011');
    }

    /** Test existing invitation */
    public function testShowInvitation()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/pozvanky/2011/gulasove-opojenie-v-tesaroch');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Gulášové opojenie v Tesároch');
        $this->assertEquals('Dátum konania: 10. 9. 2011.', $crawler->filterXPath('//*[@id="start-date"]')->text());
        $this->assertEquals('Trasy:', $crawler->filterXPath('//*[@id="routes"]/p')->text());
        $this->assertEquals('Okolie Tesár, športové hry (dĺžka 5 km)', $crawler->filterXPath('//*[@id="routes"]/ul/li[1]')->text());
        $this->assertEquals('Podhradie – Opálená skala – Džimova spása – Úhrad – Podhradie (dĺžka 15 km)', $crawler->filterXPath('//*[@id="routes"]/ul/li[2]')->text());
    }

    /** Test upcoming invitations */
    public function testShowLatestInvitation()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/pozvanky/aktualne');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Aktuálne pozvánky na turistické podujatia');

        $tomorrow = new DateTimeImmutable('tomorrow');

        $this->assertEquals($tomorrow->format('j. n. Y'), $crawler->filterXPath('//*[@id="invitations-upcoming"]/table/tr/td[1]')->text());
        $this->assertEquals('Upcoming event', $crawler->filterXPath('//*[@id="invitations-upcoming"]/table/tr/td[2]')->text());

        $link = $crawler->filterXPath('//*[@id="invitations-upcoming"]/table/tr/td[2]/a')->link();
        $crawler = $client->click($link);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Upcoming event');
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
            ['/pozvanky/2000/pridat-novu/add'],
            ['/pozvanky/2000/pridat-novu/2020-10-25/add'],
            ['/pozvanky/2020/invitation-slug/edit'],
            ['/pozvanky/2020/invitation-slug/delete'],
            ['/pozvanky/2020/invitation-slug/delete/yes'],
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
        $client->loginUser($testUser); // login admin

        $client->request('GET', $url);
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function provideAdmin404Urls(): array
    {
        return [
            ['/pozvanky/2010/gulasove-opojenie-v-tesaroch/edit'],
            ['/pozvanky/2011/gulasove-opojenie-v-tesaroch1/edit'],
        ];
    }

    /**
     * Test edit existing invitation
     * Open existing, check values, change values, save, edit - set previous values, save, check values
     */
    public function testEdit(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        
        $testUser = $userRepository->findOneByEmail('john.doe@example.com');
        $client->loginUser($testUser); // login admin

        $crawler = $client->request('GET', '/pozvanky/2011/gulasove-opojenie-v-tesaroch/edit');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Upraviť túto pozvánku');


        $form = $crawler->selectButton('Uložiť pozvánku')->form();
        $values = $form->getPhpValues();
        $formName = $form->getName();

        // test existing values from form
        $content = '<p>Dňa <strong>10. 9. 2011</strong> (sobota) na futbalovom ihrisku.</p>
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
        <p>www.krokovelo.webnode.cz</p>';
        $this->assertEquals('Gulášové opojenie v Tesároch', $values[$formName]['title']);
        $this->assertEquals('Turisticko-športový deň 10.9. v Tesároch', $values[$formName]['summary']);
        $this->assertEquals($content, $values[$formName]['content']);
        $this->assertEquals('2011-09-10T10:00:00', $values[$formName]['startDate']);
        $this->assertEquals('peso', $values[$formName]['sportType'][0]);
        $this->assertEquals('cyklo', $values[$formName]['sportType'][2]);
        $this->assertEquals('Okolie Tesár, športové hry', $values[$formName]['routes'][0]['title']);
        $this->assertEquals(5, $values[$formName]['routes'][0]['length']);
        $this->assertEquals('Podhradie – Opálená skala – Džimova spása – Úhrad – Podhradie', $values[$formName]['routes'][1]['title']);
        $this->assertEquals(15, $values[$formName]['routes'][1]['length']);
        
        // edit values
        $values[$formName]['title'] = 'Gulášové opojenie v Tesároch1';
        $values[$formName]['summary'] = 'Turisticko-športový deň 10. 9. v Tesároch';
        $values[$formName]['content'] = '<p>Dňa <strong>10. 9. 2011</strong> (sobota) na futbalovom ihrisku.</p>';
        $values[$formName]['startDate'] = '2011-09-11T10:00:00';
        unset($values[$formName]['sportType'][0]);
        $values[$formName]['sportType'][1] = 'bezky';
        $values[$formName]['sportType'][2] = 'cyklo';
        $values[$formName]['routes'][0]['title'] = 'Okolie Tesár, športové hry1';
        $values[$formName]['routes'][0]['length'] = 20;
        unset($values[$formName]['routes'][1]); //Remove second route

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles()); // save


        $this->assertEquals('/pozvanky/2011/Gulasove-opojenie-v-Tesaroch1', $client->getResponse()->headers->get('location'));
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $crawler = $client->followRedirect();

        // checked if new values has been saved
        $this->assertSelectorTextContains('html h1', 'Gulášové opojenie v Tesároch1');
        $this->assertEquals('Dátum konania: 11. 9. 2011.', $crawler->filterXPath('//*[@id="start-date"]')->text());
        $this->assertEquals('Trasa: Okolie Tesár, športové hry1 (dĺžka 20 km).', $crawler->filterXPath('//*[@id="routes"]/p')->text());


        $link = $crawler->selectLink('Upraviť túto')->link();
        $crawler = $client->click($link); // clik on button Edit

        $this->assertResponseIsSuccessful(); // new page loaded
        $this->assertSelectorTextContains('html h1', 'Upraviť túto pozvánku');


        $form = $crawler->selectButton('Uložiť pozvánku')->form(); // select form
        $values = $form->getPhpValues();
        $formName = $form->getName();

        // set previous values
        $values[$formName]['title'] = 'Gulášové opojenie v Tesároch';
        $values[$formName]['summary'] = 'Turisticko-športový deň 10.9. v Tesároch';
        $values[$formName]['content'] = $content;
        $values[$formName]['startDate'] = '2011-09-10T10:00:00';
        $values[$formName]['sportType'][0] = 'peso';
        unset($values[$formName]['sportType'][1]);
        // $values[$formName]['sportType'][2] = 'cyklo'; //already in form, not need to touch
        $values[$formName]['routes'][0]['title'] = 'Okolie Tesár, športové hry';
        $values[$formName]['routes'][0]['length'] = 5;
        $values[$formName]['routes'][1]['title'] = 'Podhradie – Opálená skala – Džimova spása – Úhrad – Podhradie';
        $values[$formName]['routes'][1]['length'] = 15;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertEquals('/pozvanky/2011/Gulasove-opojenie-v-Tesaroch', $client->getResponse()->headers->get('location'));
        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $crawler = $client->followRedirect();
        $this->assertSelectorTextContains('html h1', 'Gulášové opojenie v Tesároch'); // chronicle has the same values as before test
        $this->assertEquals('Dátum konania: 10. 9. 2011.', $crawler->filterXPath('//*[@id="start-date"]')->text());
        $this->assertEquals('Okolie Tesár, športové hry (dĺžka 5 km)', $crawler->filterXPath('//*[@id="routes"]/ul/li[1]')->text());
        $this->assertEquals('Podhradie – Opálená skala – Džimova spása – Úhrad – Podhradie (dĺžka 15 km)', $crawler->filterXPath('//*[@id="routes"]/ul/li[2]')->text());
    }
}
