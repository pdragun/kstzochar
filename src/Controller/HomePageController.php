<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Contracts\Cache\ItemInterface;
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
    public function index(): Response
    {

        $cached = [];
        $cache = new PdoAdapter($_ENV['DATABASE_URL'], 'app');

        $cached = $cache->get('home-page', function (ItemInterface $item) {
            
            $entityManager = $this->getDoctrine()->getManager();
            
            $fromDB = [];
            $fromDB['latestEventPlanYear'] = $entityManager->getRepository(\App\Entity\Event::class)->findMaxStartYear();

            /**
             * The latest Event Invitation
             * @var App\Entity\EventChronicle[] $fromDB['latestInvitations']
             */
            $fromDB['latestInvitations'] = $entityManager->getRepository(\App\Entity\EventInvitation::class)->findLatest();

            /**
             * The latest Event chronicle articles
             * @var App\Entity\EventChronicle $fromDB['latestChronicle']
             */
            $fromDB['latestChronicle'] = $entityManager->getRepository(\App\Entity\EventChronicle::class)->findLatest();

            /**
             * The latest blog articles from the section 'z klubovej kuchyne'
             * @var App\Entity\Blog $fromDB['latestBlogSectionId1']
             */
            $idFirstSection = $entityManager->getRepository(\App\Entity\BlogSection::class)->findBySlug('z-klubovej-kuchyne');
            $fromDB['latestBlogSectionId1'] = null;
            if($idFirstSection) {
                $fromDB['latestBlogSectionId1'] = $entityManager->getRepository(\App\Entity\Blog::class)->findLatestByBlogSectionId($idFirstSection->getId());
            }

            /**
             * The latest blog articles from the section 'viacdňové akcie'
             * @var App\Entity\Blog $fromDB['latestBlogSectionId2']
             */
            $idSecondSection = $entityManager->getRepository(\App\Entity\BlogSection::class)->findBySlug('viacdnove-akcie');
            $fromDB['latestBlogSectionId2'] = null;
            if($idSecondSection) {
                $fromDB['latestBlogSectionId2'] = $entityManager->getRepository(\App\Entity\Blog::class)->findLatestByBlogSectionIdStartDate($idSecondSection->getId());
            }

            /**
             * The latest blog articles from the section 'receptúry na túry'
             * @var App\Entity\Blog $fromDB['latestBlogSectionId3']
             */
            $idThirdSection = $entityManager->getRepository(\App\Entity\BlogSection::class)->findBySlug('receptury-na-tury');
            $fromDB['latestBlogSectionId3'] = null;
            if($idThirdSection) {
                $fromDB['latestBlogSectionId3'] = $entityManager->getRepository(\App\Entity\Blog::class)->findLatestByBlogSectionId($idThirdSection->getId());
            }

            return $fromDB;
        });


        return $this->render('home_page/index.html.twig', [
            'latestEventPlanYear' => $cached['latestEventPlanYear'],
            'latestInvitations' => $cached['latestInvitations'],
            'latestChronicle' => $cached['latestChronicle'],
            'latestBlogSectionId1' => $cached['latestBlogSectionId1'],
            'latestBlogSectionId2' => $cached['latestBlogSectionId2'],
            'latestBlogSectionId3' => $cached['latestBlogSectionId3']
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