<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\EventInvitation;
use App\Form\EventInvitationType;
use App\Form\SetDateType;
use App\Repository\EventRepository;
use App\Repository\EventInvitationRepository;
use App\Utils\SecondLevelCachePDO;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Invitation to event
 */
class EventInvitationController extends AbstractController
{
    #[Route('/pozvanky', name: 'invitation_show', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('event_invitation/showEventInvitation.html.twig');
    }

    /**
     * Show list of all invitations in year
     * @return Response Show all invitations in year
     */
    #[Route(
        '/pozvanky/{year}',
        name: 'invitation_list_by_Year',
        requirements: ['year' => '\d+'],
        methods: ['GET']
    )]
    public function showEventInvitationsByYear(
        int $year,
        EventInvitationRepository $eventInvitationRepository
    ): Response {
        $invitations = $eventInvitationRepository->getPreparedByYear($year);
        if ($invitations === null) {
            throw $this->createNotFoundException();
        }
  
  		return $this->render('event_invitation/showEventInvitationByYear.html.twig', [
            'yearInUrl' => $year,
            'eventInvitations' => $invitations,
        ]);
    }

    /**
     * Show invitation
     * @return Response Show invitation
     * @throws NonUniqueResultException
     */
    #[Route(
        '/pozvanky/{year}/{slug}',
        name: 'invitation_show_by_Year_by_Slug',
        requirements: ['year' => '\d+'],
        methods: ['GET']
    )]
    public function showEventInvitationByYearBySlug(
        int $year,
        string $slug,
        EventInvitationRepository $eventInvitationRepository
    ): Response {
        $invitation = $eventInvitationRepository->findByYearSlug($year, $slug);
        if ($invitation === null) {
            throw $this->createNotFoundException();
        }

  		return $this->render('event_invitation/showEventInvitationByYearBySlug.html.twig', [
            'yearInUrl' => $year,
            'invitation' => $invitation
        ]);
    }

    /**
     * Show list of invitation with start date in future
     * @return Response Show list invitations
     */
    #[Route('/pozvanky/aktualne', name: 'invitation_list_upcoming', methods: ['GET'])]
    public function showEventInvitationUpcoming(
        EventInvitationRepository $eventInvitationRepository
    ): Response {
        $upcomingInvitations = $eventInvitationRepository->findLatest();

        return $this->render('event_invitation/showEventInvitationUpcoming', [
            'upcomingInvitations' => $upcomingInvitations,
        ]);

    }

    /**
     * Show form for invitation start date or if is already set redirect to create invitation
     * @return RedirectResponse|Response Show form for date or redirect
     */
    #[Route(
        '/pozvanky/{year}/pridat-novu/add"',
        name: 'invitation_create_from_date',
        requirements: ['year' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function createInvitationFromDate(int $year, Request $request): RedirectResponse|Response
    {
        /** @var $form SetDateType */
        $form = $this->createForm(SetDateType::class, null, [
            'save_button_label' => 'Vytvor pozvánku',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $startDate = $form->getData()['startDate'];

            return $this->redirectToRoute('invitation_create_from_event', [
                'year' => $year,
                'date' => $startDate->format('Y-m-d')
            ]);
        }

  		return $this->render('event_invitation/createFromDate.html.twig', [
            'form' => $form->createView(),
            'yearInUrl' => $year,
            'pageTitle' => 'Vytvoriť novú pozvánku',
            'invitationTitle' => 'Nová pozvánka',
            'actionName' => 'Pridať'
        ]);
    }


    /**
     * Create invitation
     * @return RedirectResponse|Response Show form or redirect to new invitation
     * @throws InvalidArgumentException
     */
    #[Route(
        '/pozvanky/{year}/pridat-novu/{date}/add',
        name: 'invitation_create_from_event',
        requirements: ['year' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function createInvitationFromEvent(
        int $year,
        string $date,
        Request $request,
        EventRepository $eventRepository,
        EntityManagerInterface $entityManager
    ): RedirectResponse|Response {
        $dateTime = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        $now = new DateTimeImmutable();
        $events = $eventRepository->findBy(['startDate' => $dateTime, 'eventInvitation' => null]);
        $invitation = new EventInvitation();
        if (isset($events[0])) { //Parent Event exist, get aditionl info from it
            $firstEvent = $events[0];

            $invitation->setTitle($firstEvent->getTitle());
            $invitation->setEndDate($firstEvent->getEndDate());
            $invitation->setStartDate($firstEvent->getStartDate());
            if ($firstEvent->getSportType() !== null) {
                foreach ($firstEvent->getSportType() as $key => $value) {
                    $invitation->addSportType($firstEvent->getSportType()[$key]);        
                }
            }
            if ($firstEvent->getEventInvitation() !== null) {
                foreach ($firstEvent->getEventInvitation()->getRoutes() as $key => $value) {
                    $invitation->addRoute($firstEvent->getEventInvitation()->getRoutes()[$key]);        
                }
            }
            $invitation->setEvent($firstEvent);
        } else { //No parent Event = no additional information
            $invitation->setStartDate($dateTime);
        }

        $originalSportTypes = new ArrayCollection();
        foreach ($invitation->getSportType() as $sportType) {
            $originalSportTypes->add($sportType);
        }

        $originalRoutes = new ArrayCollection();
        foreach ($invitation->getRoutes() as $route) {
            $originalRoutes->add($route);
        }

        /* @var $form EventInvitationType */
        $form = $this->createForm(EventInvitationType::class, $invitation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var $invitation EventInvitation */
            $invitation = $form->getData();

            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($invitation->getTitle());
            $invitation->setSlug($slug);
            $invitation->setPublishedAt($now);
            $invitation->setCreatedAt($now);
            $invitation->setModifiedAt($now);
            $invitation->setPublish(true);
            $invitation->setCreatedBy($this->getUser());
            
            // remove or update SportTypes for Invitation
            foreach ($originalSportTypes as $sportType) {
                if ($invitation->getSportType()->contains($sportType) === false) {
                    $sportType->removeEventInvitation($invitation);
                    $entityManager->persist($sportType);
                }
            }

            // remove or update Routes for Invitation
            foreach ($originalRoutes as $route) {
                if ($invitation->getRoutes()->contains($route) === false) {
                    $route->removeEventInvitation($invitation);
                    $entityManager->persist($route);
                }
            }

            $entityManager->persist($invitation);
            $entityManager->flush();

            $cache = SecondLevelCachePDO::getInstance();
            $cache->clearAllCache();

            $this->addFlash(
                'success',
                'Nová pozvánka: „' . $invitation->getTitle() . '“ bola vytvorená a uložená!'
            );

            $invitationYear = $invitation->getStartDate()->format('Y');

            return $this->redirectToRoute('invitation_show_by_Year_by_Slug', [
                'year' => $invitationYear,
                'slug' => $invitation->getSlug()
            ]);
        }

  		return $this->render('event_invitation/createFromEvent.html.twig', [
            'form' => $form->createView(),
            'yearInUrl' => $year,
            'invitationTitle' => 'Nová pozvánka',
            'dateTime' => $dateTime->format('Y-m-d'),
            'actionName' => 'Pridať'
        ]);

    }


    /**
     * Edit invitation
     * @return RedirectResponse|Response Show form or redirect to new invitation
     * @throws NonUniqueResultException|InvalidArgumentException
     */
    #[Route(
        '/pozvanky/{year}/{slug}/edit"',
        name: 'invitation_edit',
        requirements: ['year' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function editInvitation(
        int $year,
        string $slug,
        Request $request,
        EventInvitationRepository $eventInvitationRepository,
        EntityManagerInterface $entityManager
    ): RedirectResponse|Response {
        $invitation = $eventInvitationRepository->findByYearSlug($year, $slug);
        if ($invitation === null) {
            throw $this->createNotFoundException();
        }

        $originalSportTypes = new ArrayCollection();
        foreach ($invitation->getSportType() as $sportType) {
            $originalSportTypes->add($sportType);
        }

        $originalRoutes = new ArrayCollection();
        foreach ($invitation->getRoutes() as $route) {
            $originalRoutes->add($route);
        }

        /* @var $form EventInvitationType */
        $form = $this->createForm(EventInvitationType::class, $invitation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /* @var $invitation EventInvitation */
            $invitation = $form->getData();
            $invitation->setModifiedAt(new DateTimeImmutable());
            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($invitation->getTitle());
            $invitation->setSlug($slug);

            // remove or update SportTypes for Invitation
            foreach ($originalSportTypes as $sportType) {
                if ($invitation->getSportType()->contains($sportType) === false) {
                    $sportType->removeEventInvitation($invitation);
                    $entityManager->persist($sportType);
                }
            }

            // remove or update Routes for Invitation
            foreach ($originalRoutes as $route) {
                if ($invitation->getRoutes()->contains($route) === false) {
                    $route->removeEventInvitation($invitation);
                    $entityManager->persist($route);
                }
            }

            $entityManager->persist($invitation);
            $entityManager->flush();
    
            $cache = SecondLevelCachePDO::getInstance();
            $cache->clearAllCache();

            $this->addFlash(
                'success',
                'Zmeny v pozvánke: „' . $invitation->getTitle() . '“ boli uložené!'
            );

            return $this->redirectToRoute('invitation_show_by_Year_by_Slug', [
                'year' => $invitation->getStartDate()->format('Y'),
                'slug' => $invitation->getSlug()
            ]);
        }

  		return $this->render('event_invitation/createFromEvent.html.twig', [
            'form' => $form->createView(),
            'yearInUrl' => $year,
            'invitationTitle' => 'Upraviť túto pozvánku',
            'invitation' => $invitation,
            'dateTime' => $invitation->getStartDate()->format('Y-m-d'),
            'actionName' => 'Upraviť'
        ]);
    }

    /**
     * Confirmation to delete invitation
     * @return Response Show confirmation to delete invitation
     * @throws NonUniqueResultException
     */
    #[Route(
        '/pozvanky/{year}/{slug}/delete',
        name: 'invitation_delete',
        requirements: ['year' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function prepareDeleteInvitation(
        int $year,
        string $slug,
        EventInvitationRepository $eventInvitationRepository
    ): Response {
        $invitation = $eventInvitationRepository->findByYearSlug($year, $slug);
        if(!$invitation) { // 404
            throw $this->createNotFoundException();
        }

  		return $this->render('event_invitation/delete.html.twig', [
            'invitation' => $invitation,
            'yearInUrl' => $year,
        ]);
    }

    /**
     * Delete invitation
     * @return RedirectResponse Redirect to list of invitations for year
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     */
    #[Route(
        '/pozvanky/{year}/{slug}/delete/yes',
        name: 'invitation_delete_yes',
        requirements: ['year' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteInvitation(int $year, string $slug, EventInvitationRepository $eventInvitationRepository): RedirectResponse
    {
        $invitation = $eventInvitationRepository->findByYearSlug($year, $slug);
        if ($invitation === null) {
            throw $this->createNotFoundException();
        }

        $invitation->removeEvent();
        $invitationTitle = $invitation->getTitle();

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($invitation);
        $entityManager->flush();

        $cache = SecondLevelCachePDO::getInstance();
        $cache->clearAllCache();

        $this->addFlash(
            'success',
            'Pozvánka: „' . $invitationTitle . '“ bola zmazaná!'
        );

        return $this->redirectToRoute('invitation_list_by_Year', ['year' => $year]);
    }
}