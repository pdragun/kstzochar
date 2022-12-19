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

    /** Home page
     * @throws InvalidArgumentException
     */
    #[Route('/', name: 'home_page', methods: ['GET'])]
    public function index(): Response
    {

        $cache = SecondLevelCachePDO::getInstance()->getCache();
        $cached = $cache->get('home-page', function (ItemInterface $item) {

            $fromDB = [];
            $fromDB['latestEventPlanYear'] = $this->doctrine->getRepository(Event::class)->findMaxStartYear();

            /**
             * The latest Event Invitation
             * @var EventChronicle[] $fromDB['latestInvitations']
             */
            $fromDB['latestInvitations'] = $this->doctrine->getRepository(EventInvitation::class)->findLatest();

            /**
             * The latest Event chronicle articles
             * @var EventChronicle $fromDB['latestChronicle']
             */
            $fromDB['latestChronicle'] = $this->doctrine->getRepository(EventChronicle::class)->findLatest();

            /**
             * The latest blog articles from the section 'z klubovej kuchyne'
             * @var Blog $fromDB['latestBlogSectionId1']
             */
            $idFirstSection = $this->doctrine->getRepository(BlogSection::class)->findBySlug('z-klubovej-kuchyne');
            $fromDB['latestBlogSectionId1'] = null;
            if ($idFirstSection) {
                $fromDB['latestBlogSectionId1'] = $this->doctrine->getRepository(Blog::class)->findLatestByBlogSectionId($idFirstSection->getId());
            }

            /**
             * The latest blog articles from the section 'viacdňové akcie'
             * @var Blog $fromDB['latestBlogSectionId2']
             */
            $idSecondSection = $this->doctrine->getRepository(BlogSection::class)->findBySlug('viacdnove-akcie');
            $fromDB['latestBlogSectionId2'] = null;
            if ($idSecondSection) {
                $fromDB['latestBlogSectionId2'] = $this->doctrine->getRepository(Blog::class)->findLatestByBlogSectionIdStartDate($idSecondSection->getId());
            }

            /**
             * The latest blog articles from the section 'receptúry na túry'
             * @var Blog $fromDB['latestBlogSectionId3']
             */
            $idThirdSection = $this->doctrine->getRepository(BlogSection::class)->findBySlug('receptury-na-tury');
            $fromDB['latestBlogSectionId3'] = null;
            if ($idThirdSection) {
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
