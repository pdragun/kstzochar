<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\EventChronicle;
use App\Repository\EventChronicleRepository;
use App\Repository\EventRepository;
use App\Form\EventChronicleType;
use App\Form\SetDateType;
use App\Utils\SecondLevelCachePDO;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
     * @return Response Show list of all years
     */
    #[Route('/kronika', name: 'chronicle_show', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('event_chronicle/showChronicle.html.twig');
    }

    /**
     * Show chronicle
     * @return Response Show chronicle
     * @throws NonUniqueResultException
     */
    #[Route(
        '/kronika/{year}/{slug}',
        name: 'chronicle_show_by_Year_Slug',
        requirements: ['year' => '\d+'],
        methods: ['GET']
    )]
    public function showChronicleByYearSlug(
        int $year,
        string $slug,
        EventChronicleRepository $eventChronicleRepository
    ): Response {
        $chronicle = $eventChronicleRepository->findByYearSlug($year, $slug);
        if ($chronicle === null) {
            throw $this->createNotFoundException();
        }

  		return $this->render('event_chronicle/showChronicleByYearSlug.html.twig', [
            'chronicle' => $chronicle,
            'yearInUrl' => $year,
        ]);
    }

    /**
     * Show list of all chronicles in year
     * @return Response Show all chronicle in year
     */
    #[Route(
        '/kronika/{year}',
        name: 'chronicle_list_by_Year',
        requirements: ['year' => '\d+'],
        methods: ['GET']
    )]
    public function showChroniclesByYear(
        int $year,
        EventChronicleRepository $eventChronicleRepository
    ): Response {
        $chronicles = $eventChronicleRepository->getPreparedByYear($year);
        if ($chronicles === []) {
            throw $this->createNotFoundException();
        }

  		return $this->render('event_chronicle/showChroniclesByYear.html.twig', [
            'yearInUrl' => $year,
            'chronicles' => $chronicles,
        ]);
    }

    /** Show form for chronicle start date or if is already set redirect to create chronicle */
    #[Route(
        '/kronika/{year}/pridat-novu/add',
        name: 'chronicle_create_from_date',
        requirements: ['year' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function createChronicleFromDate(
        int $year,
        Request $request
    ): RedirectResponse|Response {

        /** @var $form SetDateType */
        $form = $this->createForm(SetDateType::class, null, [
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
     * Take start date from previous form, check if exist Event (from plan), if yes set Event data to form.
     * @return RedirectResponse|Response Show form or redirect to new chronicle
     * @throws InvalidArgumentException
     */
    #[Route(
        '/kronika/{year}/pridat-novu/{date}/add',
        name: 'chronicle_create_from_event',
        requirements: ['year' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function createChronicleFromEvent(
        int $year,
        string $date,
        Request $request,
        EventRepository $eventRepository
    ): RedirectResponse|Response {
        $dateTime = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        $now = new DateTimeImmutable();
        $events = $eventRepository->findBy(['startDate' => $dateTime, 'eventChronicle' => NULL]);
        $chronicle = new EventChronicle();
        if (isset ($events[0])) { //Parent Event exist, get aditional info from it

            $firstEvent = $events[0];

            $chronicle->setTitle($firstEvent->getTitle());
            $chronicle->setEndDate($firstEvent->getEndDate());
            $chronicle->setStartDate($firstEvent->getStartDate());
            if ($firstEvent->getSportType() !== null) {
                foreach ($firstEvent->getSportType() as $key => $value) {
                    $chronicle->addSportType($firstEvent->getSportType()[$key]);        
                }
            }
            if ($firstEvent->getEventChronicle() !== null) {
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

        /* @var $form EventChronicleType */
        $form = $this->createForm(EventChronicleType::class, $chronicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /* @var $chronicle EventChronicle */
            $chronicle = $form->getData();

            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($chronicle->getTitle());
            $chronicle->setSlug($slug);
            $chronicle->setPublishedAt($now);
            $chronicle->setCreatedAt($now);
            $chronicle->setModifiedAt($now);
            $chronicle->setPublish(true);
            $chronicle->setCreatedBy($this->getUser());
            
            /** @var Doctrine\Persistence\ManagerRegistry $entityManager */
            $entityManager = $this->getDoctrine()->getManager();

            // remove or update SportTypes for Chronicle
            foreach ($originalSportTypes as $sportType) {
                if ($chronicle->getSportType()->contains($sportType) === false) {
                    $sportType->removeEventChronicle($chronicle);
                    $entityManager->persist($sportType);
                }
            }

            // remove or update Routes for Chronicle
            foreach ($originalRoutes as $route) {
                if ($chronicle->getRoutes()->contains($route) === false) {
                    $route->removeEventChronicle($chronicle);
                    $entityManager->persist($route);
                }
            }

            $entityManager->persist($chronicle);
            $entityManager->flush();

            $cache = SecondLevelCachePDO::getInstance();
            $cache->clearAllCache();
    
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
     * @return RedirectResponse|Response Show form or redirect to new chronicle
     * @throws NonUniqueResultException
     * @throws InvalidArgumentException
     */
    #[Route(
        '/kronika/{year}/{slug}/edit',
        name: 'chronicle_edit',
        requirements: ['year' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function editChronicle(
        int $year,
        string $slug,
        Request $request,
        EventChronicleRepository $eventChronicleRepository,
        EntityManagerInterface $entityManager
    ): RedirectResponse|Response {

        $chronicle = $eventChronicleRepository->findByYearSlug($year, $slug);
        if ($chronicle === null) {
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

        /** @var $form EventChronicleType  */
        $form = $this->createForm(EventChronicleType::class, $chronicle);
        $form->handleRequest($request);

        
        if ($form->isSubmitted() && $form->isValid()) {

            $chronicle = $form->getData();
            $chronicle->setModifiedAt(new DateTimeImmutable('now'));
            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($chronicle->getTitle());
            $chronicle->setSlug($slug);


            // remove or update SportTypes for Chronicle
            foreach ($originalSportTypes as $sportType) {
                if ($chronicle->getSportType()->contains($sportType) === false) {
                    $sportType->removeEventChronicle($chronicle);
                    $entityManager->persist($sportType);
                }
            }

            // remove or update Routes for Chronicle
            foreach ($originalRoutes as $route) {
                if ($chronicle->getRoutes()->contains($route) === false) {
                    $route->removeEventChronicle($chronicle);
                    $entityManager->persist($route);
                }
            }

            $entityManager->persist($chronicle);
            $entityManager->flush();

            $cache = SecondLevelCachePDO::getInstance();
            $cache->clearAllCache();

            $this->addFlash(
                'success',
                'Zmeny v kronike: „' . $chronicle->getTitle() . '“ boli uložené!'
            );

            return $this->redirectToRoute('chronicle_show_by_Year_Slug', [
                'year' => $chronicle->getStartDate()->format('Y'),
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
     * @return Response Show confirmation to delete chronicle
     * @throws NonUniqueResultException
     */
    #[Route(
        '/kronika/{year}/{slug}/delete',
        name: 'chronicle_delete',
        requirements: ['year' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function prepareDeleteChronicle(
        int $year,
        string $slug,
        EventChronicleRepository $eventChronicleRepository
    ): Response {
        $chronicle = $eventChronicleRepository->findByYearSlug($year, $slug);
        if ($chronicle === null) {
            throw $this->createNotFoundException();
        }

  		return $this->render('event_chronicle/delete.html.twig', [
            'chronicle' => $chronicle,
            'yearInUrl' => $year,
        ]);
    }

    /**
     * Delete chronicle
     * @return RedirectResponse Redirect to list of chronicles for year
     * @throws NonUniqueResultException
     * @throws InvalidArgumentException
     */
    #[Route(
        '/kronika/{year}/{slug}/delete/yes',
        name: 'chronicle_delete_yes',
        requirements: ['year' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteChronicle(
        int $year,
        string $slug,
        EventChronicleRepository $eventChronicleRepository,
        EntityManagerInterface $entityManager
    ): RedirectResponse {

        $chronicle = $eventChronicleRepository->findByYearSlug($year, $slug);
        if ($chronicle === null) {
            throw $this->createNotFoundException();
        }

        $chronicle->removeEvent();
        $chronicleTitle = $chronicle->getTitle();
        
        $entityManager->remove($chronicle);
        $entityManager->flush();

        $cache = SecondLevelCachePDO::getInstance();
        $cache->clearAllCache();

        $this->addFlash(
            'success',
            'Kronika: „' . $chronicleTitle . '“ bola zmazaná!'
        );  
    
        return $this->redirectToRoute('chronicle_list_by_Year', ['year' => $year]);
    }
}
