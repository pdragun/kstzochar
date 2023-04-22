<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Blog;
use App\Entity\BlogSection;
use App\Entity\Event;
use App\Entity\EventChronicle;
use App\Entity\EventInvitation;
use App\Utils\SecondLevelCachePDO;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomePageController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $doctrine) {}

    /**
     * Home page
     * @throws InvalidArgumentException
     */
    #[Route('/', name: 'home_page', methods: ['GET'])]
    public function index(): Response
    {

        $cache = SecondLevelCachePDO::getInstance()->getCache();
        $cached = $cache->get('home-page', function (ItemInterface $item) {

            $fromDB = [];
            $fromDB['latestEventPlanYear'] = $this->doctrine->getRepository(Event::class)->findMaxStartYear();

             /** @var $fromDB['latestInvitations'] EventChronicle */
            $fromDB['latestInvitations'] = $this->doctrine->getRepository(EventInvitation::class)->findLatest();

            /** @var $fromDB['latestChronicle'] EventChronicle */
            $fromDB['latestChronicle'] = $this->doctrine->getRepository(EventChronicle::class)->findLatest();

            /** @var $fromDB['latestBlogSectionId1'] Blog */
            $idFirstSection = $this->doctrine->getRepository(BlogSection::class)->findBySlug('z-klubovej-kuchyne');
            $fromDB['latestBlogSectionId1'] = null;
            if ($idFirstSection !== null) {
                $fromDB['latestBlogSectionId1'] = $this->doctrine->getRepository(Blog::class)->findLatestByBlogSectionId($idFirstSection->getId());
            }

            /** @var $fromDB['latestBlogSectionId2'] Blog */
            $idSecondSection = $this->doctrine->getRepository(BlogSection::class)->findBySlug('viacdnove-akcie');
            $fromDB['latestBlogSectionId2'] = null;
            if ($idSecondSection !== null) {
                $fromDB['latestBlogSectionId2'] = $this->doctrine->getRepository(Blog::class)->findLatestByBlogSectionIdStartDate($idSecondSection->getId());
            }

            /** @var $fromDB['latestBlogSectionId3'] Blog */
            $idThirdSection = $this->doctrine->getRepository(BlogSection::class)->findBySlug('receptury-na-tury');
            $fromDB['latestBlogSectionId3'] = null;
            if ($idThirdSection !== null) {
                $fromDB['latestBlogSectionId3'] = $this->doctrine->getRepository(Blog::class)->findLatestByBlogSectionId($idThirdSection->getId());
            }

            return $fromDB;
        });

        return $this->render('home_page/index.html.twig', [
            'latestEventPlanYear' => $cached['latestEventPlanYear'],
            'latestInvitations' => $cached['latestInvitations'],
            'latestChronicle' => $cached['latestChronicle'],
            'latestBlogSectionId1' => $cached['latestBlogSectionId1'],
            'latestBlogSectionId2' => $cached['latestBlogSectionId2'],
            'latestBlogSectionId3' => $cached['latestBlogSectionId3'],
            'homepage' => true,
        ]);
    }

    /** Redirect favicon.ico */
    #[Route('/favicon.ico', name: 'favicon', methods: ['GET'])]
    public function favicon(): Response
    {
        return $this->redirect('/build/images/favicon.svg');
    }
}
