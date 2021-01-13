<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\BlogRepository;
use App\Repository\BlogSectionRepository;
use App\Repository\EventChronicleRepository;
use App\Repository\EventInvitationRepository;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Home page
 */
class HomePageController extends AbstractController
{
    /**
     * Home page
     * 
     * @Route("/", name="home_page")
     * 
     * @param App\Repository\EventRepository $eventRepository
     * @param App\Repository\EventInvitationRepository $eventInvitationRepository
     * @param App\Repository\EventChronicleRepository $eventChronicleRepository
     * @param App\Repository\BlogRepository $blogRepository
     * @param App\Repository\BlogSectionRepository $blogSectionRepository
     * @return Symfony\Component\HttpFoundation\Response Show home page
     */
    public function index(EventRepository $eventRepository, EventInvitationRepository $eventInvitationRepository, EventChronicleRepository $eventChronicleRepository, BlogRepository $blogRepository, BlogSectionRepository $blogSectionRepository): Response
    {

        $latestEventPlanYear = $eventRepository->findMaxStartYear();

        /**
         * The latest Event Invitation
         * @var App\Entity\EventChronicle[] $latestInvitations
         */
        $latestInvitations = $eventInvitationRepository->findLatest();

        /**
         * The latest Event chronicle articles
         * @var App\Entity\EventChronicle $latestChronicle
         */
        $latestChronicle = $eventChronicleRepository->findLatest();

        /**
         * The latest blog articles from the section 'z klubovej kuchyne'
         * @var App\Entity\Blog $latestBlogSectionId1
         */
        $idFirstSection = $blogSectionRepository->findBySlug('z-klubovej-kuchyne');
        $latestBlogSectionId1 = null;
        if($idFirstSection) {
            $latestBlogSectionId1 = $blogRepository->findLatestByBlogSectionId($idFirstSection->getId());
        }

        /**
         * The latest blog articles from the section 'viacdňové akcie'
         * @var App\Entity\Blog $latestBlogSectionId2
         */
        $idSecondSection = $blogSectionRepository->findBySlug('viacdnove-akcie');
        $latestBlogSectionId2 = null;
        if($idSecondSection) {
            $latestBlogSectionId2 = $blogRepository->findLatestByBlogSectionIdStartDate($idSecondSection->getId());
        }

        /**
         * The latest blog articles from the section 'receptúry na túry'
         * @var App\Entity\Blog $latestBlogSectionId3
         */
        $idThirdSection = $blogSectionRepository->findBySlug('receptury-na-tury');
        $latestBlogSectionId3 = null;
        if($idThirdSection) {
            $latestBlogSectionId3 = $blogRepository->findLatestByBlogSectionId($idThirdSection->getId());
        }

        return $this->render('home_page/index.html.twig', [
            'latestEventPlanYear' => $latestEventPlanYear,
            'latestInvitations' => $latestInvitations,
            'latestChronicle' => $latestChronicle,
            'latestBlogSectionId1' => $latestBlogSectionId1,
            'latestBlogSectionId2' => $latestBlogSectionId2,
            'latestBlogSectionId3' => $latestBlogSectionId3
        ]);
    }

    /**
     * Redirect favicon.ico
     * 
     * @Route("/favicon.ico", name="favicon")
     * 
     * @return Symfony\Component\HttpFoundation\Response Redirect to real favicon
     */
    public function favicon(): Response {
        return $this->redirect('/build/images/favicon.svg');
    }



}
