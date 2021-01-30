<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\EventChronicle;
use App\Repository\EventChronicleRepository;
use App\Repository\EventRepository;
use App\Form\EventChronicleType;
use App\Form\SetDateType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;


/**
 * Stories about past events
 */
class EventChronicleController extends AbstractController
{

    /**
     * Show list of all years
     * 
     * @Route("/kronika", name="chronicle_show")
     * 
     * @return Symfony\Component\HttpFoundation\Response Show list of all years
     */
    public function index(): Response
    {
        return $this->render('event_chronicle/showChronicle.html.twig');
    }


    /**
     * Show chronicle
     * 
     * @Route("/kronika/{year}/{slug}", name="chronicle_show_by_Year_Slug", requirements={"year"="\d+"})
     * 
     * @param int $year Year
     * @param string $slug Event chronicle slug
     * @param App\Repository\EventChronicleRepository $eventChronicleRepository
     * @return Symfony\Component\HttpFoundation\Response Show chronicle
     */
    public function showChronicleByYearSlug(int $year, string $slug, EventChronicleRepository $eventChronicleRepository): Response
    {

        /** @var \App\Entity\EventChronicle $chronicle **/
        $chronicle = $eventChronicleRepository->findByYearSlug($year, $slug);
        if(!$chronicle) { // 404
            throw $this->createNotFoundException();
        }

  		return $this->render('event_chronicle/showChronicleByYearSlug.html.twig', [
            'chronicle' => $chronicle,
            'yearInUrl' => $year,
        ]);
    }


    /**
     * Show list of all chronicles in year
     * 
     * @Route("/kronika/{year}", name="chronicle_list_by_Year", requirements={"year"="\d+"})
     * 
     * @param int $year Year
     * @param App\Repository\EventChronicleRepository $eventChronicleRepository
     * @return Symfony\Component\HttpFoundation\Response Show all chronicle in year
     */
    public function showChroniclesByYear(int $year, EventChronicleRepository $eventChronicleRepository): Response
    {
        /** @var App\Entity\EventChronicle[] $chronicle */
        $chronicles = $eventChronicleRepository->getPreparedByYear($year);
        if(!$chronicles) { // 404
            throw $this->createNotFoundException();
        }


  		return $this->render('event_chronicle/showChroniclesByYear.html.twig', [
            'yearInUrl' => $year,
            'chronicles' => $chronicles,
        ]);
    }


    /**
     * Show form for chronicle start date or if is already set redirect to create chronicle
     * 
     * @Route("/kronika/{year}/pridat-novu/add", name="chronicle_create_from_date", requirements={"year"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     * 
     * @param int $year Year
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\Response Show form for date or redirect
     */
    public function createChronicleFromDate(int $year, Request $request): Response
    {

        /** @var App\Form\SetDateType; $form */
        $form = $this->createForm(SetDateType::class, NULL, [
            'save_button_label' => 'Vytvor kroniku',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $startDate = $form->getData()['startDate'];

            return $this->redirectToRoute('chronicle_create_from_event', [
                'year' => $year,
                'date' => $startDate->format('Y-m-d')
            ]);
        }

  		return $this->render('event_chronicle/createFromDate.html.twig', [
            'form' => $form->createView(),
            'yearInUrl' => $year,
            'pageTitle' => 'Vytvoriť novú kroniku',
            'chronicleTitle' => 'Nová kronika',
            'actionName' => 'Pridať'
        ]);
    }


    /**
     * Create chronicle
     * 
     * Take start date from previous form, check if exist Event (from plan), if yes set Event data to form.
     * 
     * 
     * @Route("/kronika/{year}/pridat-novu/{date}/add", name="chronicle_create_from_event", requirements={"year"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     * 
     * @param int $year Year
     * @param string $date Event start date
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param App\Repository\EventRepository $eventRepository
     * @return Symfony\Component\HttpFoundation\Response Show form or redirect to new chronicle
     */
    public function createChronicleFromEvent(int $year, string $date, Request $request, EventRepository $eventRepository): Response
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
        $now = new \DateTime();
        
        /** @var App\Entity\Event[] $events */
        $events = $eventRepository->findBy(['startDate' => $dateTime, 'eventChronicle' => NULL]);

        /** @var App\Entity\EventChronicle $chronicle */
        $chronicle = new EventChronicle();
        if(isset ($events[0])) { //Parent Event exist, get aditionl info from it

            /** @var App\Entity\Event $firstEvent */
            $firstEvent = $events[0];

            $chronicle->setTitle($firstEvent->getTitle());
            $chronicle->setEndDate($firstEvent->getEndDate());
            $chronicle->setStartDate($firstEvent->getStartDate());
            if( NULL !== $firstEvent->getSportType() ){
                foreach ($firstEvent->getSportType() as $key => $value) {
                    $chronicle->addSportType($firstEvent->getSportType()[$key]);        
                }
            }
            if (NULL !== $firstEvent->getEventChronicle()) {
                foreach ($firstEvent->getEventChronicle()->getRoutes() as $key => $value) {
                    $chronicle->addRoute($firstEvent->getEventChronicle()->getRoutes()[$key]);
                }
            }
            $chronicle->setEvent($firstEvent);
        } else { //No parent Event = no additional information
            $chronicle->setStartDate($dateTime);
        }

        $originalSportTypes = new ArrayCollection();
        foreach ($chronicle->getSportType() as $sportType) {
            $originalSportTypes->add($sportType);
        }

        $originalRoutes = new ArrayCollection();
        foreach ($chronicle->getRoutes() as $route) {
            $originalRoutes->add($route);
        }

        /** @var App\Form\EventChronicleType; $form */
        $form = $this->createForm(EventChronicleType::class, $chronicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var App\Entity\EventChronicle $chronicle */
            $chronicle = $form->getData();

            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($chronicle->getTitle());
            $chronicle->setSlug(\strval($slug));
            $chronicle->setPublishedAt($now);
            $chronicle->setCreatedAt($now);
            $chronicle->setModifiedAt($now);
            $chronicle->setPublish(TRUE);
            $chronicle->setCreatedBy($this->getUser());
            
            /** @var Doctrine\Persistence\ManagerRegistry $entityManager */
            $entityManager = $this->getDoctrine()->getManager();

            // remove or update SportTypes for Chronicle
            foreach ($originalSportTypes as $sportType) {
                if (FALSE === $chronicle->getSportType()->contains($sportType)) {
                    $sportType->removeEventChronicle($chronicle);
                    $entityManager->persist($sportType);
                }
            }

            // remove or update Routes for Chronicle
            foreach ($originalRoutes as $route) {
                if (FALSE === $chronicle->getRoutes()->contains($route)) {
                    $route->removeEventChronicle($chronicle);
                    $entityManager->persist($route);
                }
            }

            $entityManager->persist($chronicle);
            $entityManager->flush();

            $this->deleteCache();
    
            $this->addFlash(
                'success',
                'Nová kronika: „' . $chronicle->getTitle() . '“ bola vytvorená a uložená!'
            );

            $chronicleYear = $chronicle->getStartDate()->format('Y');

            return $this->redirectToRoute('chronicle_show_by_Year_Slug', [
                'year' => $chronicleYear,
                'slug' => $chronicle->getSlug()
            ]);
        }

  		return $this->render('event_chronicle/createFromEvent.html.twig', [
            'form' => $form->createView(),
            'yearInUrl' => $year,
            'pageTitle' => 'Vytvoriť novú kroniku',
            'chronicleTitle' => 'Nová kronika',
            'dateTime' => $dateTime->format('Y-m-d'),
            'actionName' => 'Pridať'
        ]);

    }


    /**
     * Edit chronicle
     * 
     * 
     * @Route("/kronika/{year}/{slug}/edit", name="chronicle_edit", requirements={"year"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     * 
     * @param int $year Year
     * @param string $slug Event chronicle slug
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param App\Repository\EventChronicleRepository $eventChronicleRepository
     * @param Doctrine\ORM\EntityManagerInterface $entityManager
     * @return Symfony\Component\HttpFoundation\Response Show form or redirect to new chronicle
     */
    public function editChronicle(int $year, string $slug, Request $request, EventChronicleRepository $eventChronicleRepository, EntityManagerInterface $entityManager): Response
    {

        /** @var App\Entity\EventChronicle $chronicle */
        if (null === $chronicle = $eventChronicleRepository->findByYearSlug($year, $slug)) {
            throw $this->createNotFoundException();
        }


        $originalSportTypes = new ArrayCollection();
        foreach ($chronicle->getSportType() as $sportType) {
            $originalSportTypes->add($sportType);
        }


        $originalRoutes = new ArrayCollection();
        foreach ($chronicle->getRoutes() as $route) {
            $originalRoutes->add($route);
        }


        /** @var App\Form\EventChronicleType $form */
        $form = $this->createForm(EventChronicleType::class, $chronicle);
        $form->handleRequest($request);

        
        if ($form->isSubmitted() && $form->isValid()) {

            $chronicle = $form->getData();
            $chronicle->setModifiedAt(new \DateTime('now'));
            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($chronicle->getTitle());
            $chronicle->setSlug(\strval($slug));


            // remove or update SportTypes for Chronicle
            foreach ($originalSportTypes as $sportType) {
                if (FALSE === $chronicle->getSportType()->contains($sportType)) {
                    $sportType->removeEventChronicle($chronicle);
                    $entityManager->persist($sportType);
                }
            }

            // remove or update Routes for Chronicle
            foreach ($originalRoutes as $route) {
                if (FALSE === $chronicle->getRoutes()->contains($route)) {
                    $route->removeEventChronicle($chronicle);
                    $entityManager->persist($route);
                }
            }

            $entityManager->persist($chronicle);
            $entityManager->flush();

            $this->deleteCache();

            $this->addFlash(
                'success',
                'Zmeny v kronike: „' . $chronicle->getTitle() . '“ boli uložené!'
            );

            return $this->redirectToRoute('chronicle_show_by_Year_Slug', [
                'year' => $year,
                'slug' => $chronicle->getSlug()
            ]);
        }

  		return $this->render('event_chronicle/createFromEvent.html.twig', [
            'form' => $form->createView(),
            'yearInUrl' => $year,
            'pageTitle' => 'Upraviť túto kroniku',
            'chronicleTitle' => $chronicle->getTitle(),
            'actionName' => 'Upraviť',
            'dateTime' => $chronicle->getStartDate()->format('Y-m-d'),
        ]);
    }


    /**
     * Confirmation to delete chronicle
     * 
     * @Route("/kronika/{year}/{slug}/delete", name="chronicle_delete", requirements={"year"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     * 
     * @param int $year Year
     * @param string $slug Event chronicle slug
     * @param App\Repository\EventChronicleRepository $eventChronicleRepository
     * @return Symfony\Component\HttpFoundation\Response Show confirmation to delete chronicle
     */
    public function prepareDeleteChronicle(int $year, string $slug, EventChronicleRepository $eventChronicleRepository): Response
    {

        /** @var App\Entity\EventChronicle $chronicle */
        $chronicle = $eventChronicleRepository->findByYearSlug($year, $slug);
        if(!$chronicle) { // 404
            throw $this->createNotFoundException();
        }

  		return $this->render('event_chronicle/delete.html.twig', [
            'chronicle' => $chronicle,
            'yearInUrl' => $year,
        ]);
    }


    /**
     * Delete chronicle
     * 
     * @Route("/kronika/{year}/{slug}/delete/yes", name="chronicle_delete_yes", requirements={"year"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     * 
     * @param int $year Year
     * @param string $slug Event chronicle slug
     * @param App\Repository\EventChronicleRepository $eventChronicleRepository
     * @param Doctrine\ORM\EntityManagerInterface $entityManager
     * @return Symfony\Component\HttpFoundation\Response Redirect to list of chronicles for year
     */
    public function deleteChronicle(int $year, string $slug, EventChronicleRepository $eventChronicleRepository, EntityManagerInterface $entityManager): Response
    {

        /** @var App\Entity\EventChronicle $chronicle */
        $chronicle = $eventChronicleRepository->findByYearSlug($year, $slug);
        if(!$chronicle) { // 404
            throw $this->createNotFoundException();
        }

        $chronicle->removeEvent();
        $chronicleTitle = $chronicle->getTitle();
        
        $entityManager->remove($chronicle);
        $entityManager->flush();

        $this->deleteCache();

        $this->addFlash(
            'success',
            'Kronika: „' . $chronicleTitle . '“ bola zmazaná!'
        );  
    
        return $this->redirectToRoute('chronicle_list_by_Year', ['year' => $year]);
    }


    /**
     * Delete cache
     * 
     * @return void
     */
    private function deleteCache() {
        $cache = new PdoAdapter($_ENV['DATABASE_URL'], 'app');
        $cache->delete('home-page');
    }

}
