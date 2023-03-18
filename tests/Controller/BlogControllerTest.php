<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BlogControllerTest extends WebTestCase
{
    /**
     * Test main blog page, where should be list of all blog categories
     */
    public function testShowAllBlogCategories(): void
    {
        $client = static::createClient();
        $client->request('GET', '/blog');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Vyberte si prosím niektorú z kategórií');
    }

    /**
     * Test list of blogs for each category
     */
    public function testShowBlogCategoriesOneByOne(): void
    {
        $client = static::createClient();
        $client->request('GET', '/blog/z-klubovej-kuchyne');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Z klubovej kuchyne');

        $client->request('GET', '/blog/viacdnove-akcie');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Viacdňové akcie');

        $client->request('GET', '/blog/receptury-na-tury');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Receptúry na túry');
    }

    /**
     * Test blog entries for each category
     */
    public function testShowBlogPostInEachCategory(): void
    {
        $client = static::createClient();
        $client->request('GET', '/blog/z-klubovej-kuchyne/2011/historia-turistiky');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'História turistiky');

        $client->request('GET', '/blog/viacdnove-akcie/2011/nizke-tatry');
 
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Nízke Tatry');

        $client->request('GET', '/blog/receptury-na-tury/2011/cergovske-susienky');
 
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Čergovské sušienky');
    }

    /**
     * Test list of wrong links
     * @dataProvider provide404Urls
     */
    public function test404(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public function provide404Urls(): iterable
    {
        yield ['/blo'];
        yield ['/bloga'];
        yield ['/blog/2000'];
        yield ['/blog/3000'];
        yield ['/blog/asdf'];
        yield ['/blog/z-klubovej-kuchyn'];
        yield ['/blog/z-klubovej-kuchynea'];
        yield ['/blog/z-klubovej-kuchyne/2000/'];
        yield ['/blog/z-klubovej-kuchyne/3000/'];
        yield ['/blog/z-klubovej-kuchyne/2asdf'];
        yield ['/blog/z-klubovej-kuchyne/2012/historia-turistiky'];
        yield ['/blog/z-klubovej-kuchyne/2011/historia-turistiky2'];
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

    public function provide302Urls(): iterable
    {
        yield ['/blog/2000/pridat-novy/add'];
        yield ['/blog/blog-slug/pridat-novy/add'];
        yield ['/blog/blog-slug/2020/article-slug/edit'];
        yield ['/blog/blog-slug/2020/article-slug/delete'];
        yield ['/blog/blog-slug/2020/article-slug/delete/yes'];
    }

    /**
     * Test if admin get correct 404
     * @dataProvider provideAdmin404Urls
     * @throws Exception
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

    public function provideAdmin404Urls(): iterable
    {
        yield ['/blog/viacdnove-akcie1/pridat-novy/add'];
        yield ['/blog/z-klubovej-kuchyne1/pridat-novy/add'];
        yield ['/blog/receptury-na-tury1/2011/cergovske-susienky/edit'];
        yield ['/blog/viacdnove-akcie1/2011/nizke-tatry/edit'];
        yield ['/blog/z-klubovej-kuchyne1/2011/Turisticka-historia/edit'];
        yield ['/blog/receptury-na-tury/2011/cergovske-susienky1/edit'];
        yield ['/blog/viacdnove-akcie/2011/nizke-tatry1/edit'];
        yield ['/blog/z-klubovej-kuchyne/2011/Turisticka-historia1/edit'];
        yield ['/blog/receptury-na-tury/2010/cergovske-susienky/edit'];
        yield ['/blog/viacdnove-akcie/2010/nizke-tatry/edit'];
        yield ['/blog/z-klubovej-kuchyne/2010/Turisticka-historia/edit'];
    }

    /**
     * Test edit Blog entry
     * Open, check values in form, edit values, save, check saved values
     * @throws Exception
     */
    public function testEditBlog(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        
        $testUser = $userRepository->findOneByEmail('john.doe@example.com');
        $client->loginUser($testUser); // login admin

        $crawler = $client->request('GET', '/blog/z-klubovej-kuchyne/2011/historia-turistiky');
        $this->assertResponseIsSuccessful();
        
        // test existing blog entry
        $this->assertSelectorTextContains('html h1', 'História turistiky');
        $this->assertSelectorTextContains('html h2', 'Začiatky turistiky v Európe a svete');

        $link = $crawler->selectLink('Upraviť tento')->link(); // go to edit page
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('html h1', 'História turistiky'); // edit page
        

        $buttonCrawlerNode = $crawler->selectButton('Uložiť článok');
        $form = $buttonCrawlerNode->form();
        $formName = $form->getName();

        $values = $form->getPhpValues();

        // test existing values from form
        $this->assertEquals('História turistiky', $values[$formName]['title']);
        $this->assertEquals('Krátky článok o začiatkoch turistiky vo svete, na slovensku a v regióne', $values[$formName]['summary']);
        $this->assertEquals('<h2>Začiatky turistiky v Eur&oacute;pe a svete</h2>
        <p>Franc&uacute;zsky spisovateľ Stendhal po prv&yacute;kr&aacute;t použil slovo turista v&nbsp;roku 1838 vo svojej povesti &bdquo;Memoires d&acute; un touriste (Spomienky turistu).</p>
        <p>Začiatky turistiky s&uacute; spojen&eacute; s&nbsp;vyhl&aacute;seniami viacer&yacute;ch mysliteľov 17. a&nbsp;18. stor. o&nbsp;potrebe n&aacute;vratu človeka k&nbsp;pr&iacute;rode, čo prispelo k&nbsp;rozvoju cestovania a&nbsp;služieb. V&nbsp;18. stor. nab&aacute;dal Jean Jacques Rousseau zv&yacute;&scaron;iť z&aacute;ujem o&nbsp;pr&iacute;rodu a&nbsp;využiť ju pri v&yacute;chove. Vzniklo niekoľko turistick&yacute;ch spoločnost&iacute;: 1857 Alpine Club, 1862 &Ouml;sterreichische Alpenverein&hellip; .</p>
        <h2>Začiatky turistiky na Slovensku</h2>
        <p>Uhorsk&yacute; karpatsk&yacute; spolok bol založen&yacute; 10. augusta 1873 v&nbsp;Starom Smokovci. Turistick&uacute; činnosť rozv&iacute;jal hlavne vo Vysok&yacute;ch Tatr&aacute;ch. Sem siahaj&uacute; aj začiatky ochrany pr&iacute;rody, sprievodcovskej a&nbsp;z&aacute;chran&aacute;rskej činnosti, značenia turistick&yacute;ch tr&aacute;s, budovania ch&aacute;t, &uacute;tuln&iacute;.</p>
        <h2>Hist&oacute;ria turistiky v regi&oacute;ne Topoľčany</h2>
        <p>Toto je pole neoran&eacute;. M&aacute;m len inform&aacute;cie o niektor&yacute;ch ľuďoch, ktor&iacute; sa venovali organizovanej turistike: J&aacute;n Vizner, Jir&aacute;nkovci, Petrovičovci, Lajko &Scaron;vec, Oldo Bohata, Karol Červeňansk&yacute;, Eva Čerevkov&aacute;, Bohu&scaron; Slov&aacute;k, Janko Koln&iacute;k, Cyril Mik&aacute;t, Stano Goga, Jožko Mal&iacute;ček, Du&scaron;an Stanček, Jožo Želiska, Inka Zn&aacute;&scaron;ikov&aacute;, Jožko Urban&hellip; . A isto mnoho ďal&scaron;&iacute;ch. Ak niekto vie viac o hist&oacute;rii, pros&iacute;m, aby sa ohl&aacute;sil.</p>
        <h2>S&uacute;časnosť</h2>
        <p>Stav turistiky v Topoľčianskom regi&oacute;ne koncom roka 2010: registrovan&yacute;ch je 13 klubov s 598 členmi. S&uacute; to kluby: TK Javor Bo&scaron;any, Spartak B&aacute;novce nad Bebravou, Kamar&aacute;t Partiz&aacute;nske, Alpin klub Jacovce, Kroko &ndash; Velo Tes&aacute;re, Horňan Praznovce, KST Bojn&aacute;, Ostr&aacute; Veľk&yacute; Kl&iacute;ž, Borina Nitrianska Streda, KLUT Urmince, KST Tribeč Kovarce, KST Solčany a topoľčiansky Žoch&aacute;r. Organizačne s&uacute; kluby registrovan&eacute; v region&aacute;lnej rade (RR) Topoľčany.</p>', $values[$formName]['content']);


        // edit values
        $values[$formName]['title'] = 'Turistická história';
        $values[$formName]['content'] = '<h2>Začiatky turistiky vo svete a v Európe</h2>
        <p>Začiatky boli ťažké &hellip; .</p>';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles()); // save

        $this->assertEquals('/blog/z-klubovej-kuchyne/2011/Turisticka-historia', $client->getResponse()->headers->get('location'));
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $crawler = $client->followRedirect();

        // test saved values
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Turistická história');
        $this->assertSelectorTextContains('html h2', 'Začiatky turistiky vo svete a v Európe');
    }

    /** Test create and delete blog entry */
    public function testCreateDeleteBlog(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        
        $testUser = $userRepository->findOneByEmail('john.doe@example.com');
        $client->loginUser($testUser); // login admin

        $crawler = $client->request('GET', '/blog/receptury-na-tury');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Pridať nový článok')->link();
        $crawler = $client->click($link); // go to create new blog page

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('html h1', 'Vytvoriť nový článok');

        $buttonCrawlerNode = $crawler->selectButton('Uložiť článok');
        $form = $buttonCrawlerNode->form();
        $formName = $form->getName();

        // new blog values
        $form[$formName . '[title]'] = 'Grlík';
        $form[$formName . '[summary]'] = 'Obľúbený koláč s netradičným názvom';
        $form[$formName . '[content]'] = '<h2>Pôvodný názov „rezy Orlík“ – pri nepozornom prepise vznikol Grlík.</h2>
        <p><strong>Na cesto:</strong></p>
        <ul>
        <li>500 g hladkej m&uacute;ky,</li>
        <li>200 g pr&aacute;&scaron;kov&eacute;ho cukru,</li>
        <li>2 vajcia,</li>
        <li>200 g masla,</li>
        <li>2 lyžice medu,</li>
        <li>2 lyžičky s&oacute;dy bikarb&oacute;ny rozmie&scaron;ať v 2 lyžičk&aacute;ch mlieka.</li>
        </ul>
        <p><strong>Na orechov&uacute; zmes:</strong></p>
        <ul>
        <li>300 až 350 g vla&scaron;sk&yacute;ch orechov nasekan&yacute;ch na &scaron;tvrtinky,</li>
        <li>80 g pr&aacute;&scaron;kov&eacute;ho cukru,</li>
        <li>120 g masla,</li>
        <li>3 lyžice medu.</li>
        </ul>
        <p><strong>Na kr&eacute;m:</strong></p>
        <ul>
        <li>3 dcl mlieka,</li>
        <li>1 a 1/2 lyžice hladkej m&uacute;ky,</li>
        <li>1 a 1/2 lyžice Solamylu,</li>
        <li>200 g cukru,</li>
        <li>250 g masla,</li>
        <li>1 vanilkov&yacute; cukor.</li>
        </ul>
        <p><strong>K tomu:</strong></p>
        <ul>
        <li>1 dcl rumu.</li>
        </ul>
        <p><strong>Cesto:</strong> v&scaron;etko vymie&scaron;ame na hladk&eacute; cesto. Rozdel&iacute;me na dve polovice. Polovicu cesta rozvaľk&aacute;me a&nbsp;d&aacute;me upiecť na vymasten&yacute; a&nbsp;m&uacute;kou vysypan&yacute; vy&scaron;&scaron;&iacute; plech.</p>
        <p><strong>Orechov&aacute; zmes:</strong> orechy nasek&aacute;me na drobno  nožom /alebo v&nbsp;s&aacute;čku rozdrv&iacute;me valčekom na cesto/. Na panvici nasucho trocho opraž&iacute;me, prid&aacute;me  cukor, maslo, med. Nech&aacute;me trochu vychladn&uacute;ť. Druh&eacute; cesto rozvaľk&aacute;me na pečiacom papieri a&nbsp;navrstv&iacute;me orechov&uacute; zmes. D&aacute;me upiesť.</p>
        <p><strong>Plnka:</strong> z&nbsp;mlieka hladkej m&uacute;ky a&nbsp;solamylu uvar&iacute;me hust&uacute; hladk&uacute; ka&scaron;u. Cukor maslo a&nbsp;vanilku vy&scaron;ľah&aacute;me a&nbsp;vmie&scaron;ame do vychladnutej ka&scaron;e spolu s&nbsp;trochou rumu.</p>
        <p>Na spodn&yacute; upečen&yacute; pl&aacute;t navrstv&iacute;me plnku, na ňu d&aacute;me druh&yacute; pl&aacute;t s&nbsp;orechovou zmesou a&nbsp;d&aacute;me do chladničky alebo &scaron;pajze. Na druh&yacute; deň kr&aacute;jame.</p>
        <p>Ulož&iacute;me do d&oacute;zy, vynesieme na kopec a&nbsp;mls&aacute;me &ndash; ved&uacute;ceho pon&uacute;kneme ako prv&eacute;ho aby sa mu u&scaron;lo.</p>
        <table style="width: auto;">
        <tbody>
        <tr>
        <td><a href="https://picasaweb.google.com/lh/photo/Nc9qo36bsdb9QvPM_Z56HdMTjNZETYmyPJy0liipFm0?feat=embedwebsite"><img src="https://lh3.googleusercontent.com/-KmyWiAeXWgw/VdqsKF-mV5I/AAAAAAAAMc4/QKFRPLUpbbc/s288-Ic42/IMG_7819.JPG" alt="" width="288" height="216" /></a></td>
        </tr>
        <tr>
        <td style="font-family: arial,sans-serif; font-size: 11px; text-align: right;">Od <a href="https://picasaweb.google.com/105572642168808318500/Rozne?authuser=0&amp;feat=embedwebsite">R&ocirc;zne</a></td>
        </tr>
        </tbody>
        </table>';
        $client->submit($form); // save new blog entry

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $crawler = $client->followRedirect();
        
        // new blog entry
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Grlík');
        $this->assertSelectorTextContains('html h2', 'Pôvodný názov „rezy Orlík“ – pri nepozornom prepise vznikol Grlík.');

        $link = $crawler->selectLink('Zmazať tento')->link();
        $crawler = $client->click($link); // click on delete button

        // show delete confirmation page
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Grlík');
        $this->assertSelectorTextContains('html h2', 'Pôvodný názov „rezy Orlík“ – pri nepozornom prepise vznikol Grlík.');

        $link = $crawler->selectLink('Chcem zmazať')->link(); // click on delete button
        $crawler = $client->click($link);
        
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $client->followRedirect();

        // test redirect to "upper" category page
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Receptúry na túry');
    }
}
