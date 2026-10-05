<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\BlogRepository;
use App\Repository\BlogSectionRepository;
use App\Repository\EventChronicleRepository;
use App\Repository\EventInvitationRepository;
use App\Repository\EventRepository;
use App\Utils\SecondLevelCachePDO;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomePageController extends AbstractController
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly EventInvitationRepository $eventInvitationRepository,
        private readonly EventChronicleRepository $eventChronicleRepository,
        private readonly BlogSectionRepository $blogSectionRepository,
        private readonly BlogRepository $blogRepository,
    ) {}

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
            $fromDB['latestEventPlanYear'] = $this->eventRepository->findMaxStartYear();

            $fromDB['latestInvitations'] = $this->eventInvitationRepository->findLatest();

            $fromDB['latestChronicle'] = $this->eventChronicleRepository->findLatest();

            $idFirstSection = $this->blogSectionRepository->findBySlug('z-klubovej-kuchyne');
            $fromDB['latestBlogSectionId1'] = null;
            if ($idFirstSection !== null) {
                $fromDB['latestBlogSectionId1'] = $this->blogRepository->findLatestByBlogSectionId($idFirstSection->getId());
            }

            $idSecondSection = $this->blogSectionRepository->findBySlug('viacdnove-akcie');
            $fromDB['latestBlogSectionId2'] = null;
            if ($idSecondSection !== null) {
                $fromDB['latestBlogSectionId2'] = $this->blogRepository->findLatestByBlogSectionIdStartDate($idSecondSection->getId());
            }

            $idThirdSection = $this->blogSectionRepository->findBySlug('receptury-na-tury');
            $fromDB['latestBlogSectionId3'] = null;
            if ($idThirdSection !== null) {
                $fromDB['latestBlogSectionId3'] = $this->blogRepository->findLatestByBlogSectionId($idThirdSection->getId());
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
