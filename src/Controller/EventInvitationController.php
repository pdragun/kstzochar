<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\EventInvitation;
use App\Form\EventInvitationType;
use App\Form\SetDateType;
use App\Repository\EventRepository;
use App\Repository\EventInvitationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;


/**
 * Invitation to event
 */
class EventInvitationController extends AbstractController
{

    /**
     * @Route("/pozvanky", name="invitation_show")
     * 
     * @return Symfony\Component\HttpFoundation\Response Show list of all years
     */
    public function index(): Response
    {
        return $this->render('event_invitation/showEventInvitation.html.twig');
    }


    /**
     * Show list of all invitations in year
     * 
     * @Route("/pozvanky/{year}", name="invitation_list_by_Year", requirements={"year"="\d+"})
     * 
     * @param int $year Year
     * @param App\Repository\EventInvitationRepository $eventInvitationRepository
     * @return Symfony\Component\HttpFoundation\Response Show all invitations in year
     */
    public function showEventInvitationsByYear(int $year, EventInvitationRepository $eventInvitationRepository): Response
    {
        /** @var \App\Entity\EventInvitation[] $invitation **/
        $invitations = $eventInvitationRepository->getPreparedByYear($year);
        if(!$invitations) { // 404
            throw $this->createNotFoundException();
        }
  
  		return $this->render('event_invitation/showEventInvitationByYear.html.twig', [
            'yearInUrl' => $year,
            'eventInvitations' => $invitations,
        ]);
    }


    /**
     * Show invitation
     * 
     * @Route("/pozvanky/{year}/{slug}", name="invitation_show_by_Year_by_Slug", requirements={"year"="\d+"})
     * 
     * @param int $year Year
     * @param string $slug Event invitation slug
     * @param App\Repository\EventInvitationRepository $eventInvitationRepository
     * @return Symfony\Component\HttpFoundation\Response Show invitation
     */
    public function showEventInvitationByYearBySlug(int $year, string $slug, EventInvitationRepository $eventInvitationRepository): Response
    {
        /** @var \App\Entity\EventInvitation $invitation **/
        $invitation = $eventInvitationRepository->findByYearSlug($year, $slug);
        if(!$invitation) { // 404
            throw $this->createNotFoundException();
        }

  		return $this->render('event_invitation/showEventInvitationByYearBySlug.html.twig', [
            'yearInUrl' => $year,
            'invitation' => $invitation
        ]);
    }


    /**
     * Show list of invitation with start date in future
     * 
     * @Route("/pozvanky/aktualne", name="invitation_list_upcomming")
     * 
     * @param App\Repository\EventInvitationRepository $eventInvitationRepository
     * @return Symfony\Component\HttpFoundation\Response Show list invitations
     */
    public function showEventInvitationUpcomming(EventInvitationRepository $eventInvitationRepository): Response
    {

        /** @var \App\Entity\EventInvitation[] $invitation **/
        $upcommingInvitations = $eventInvitationRepository->findLatest();

        return $this->render('event_invitation/showEventInvitationUpcomming.html.twig', [
            'upcommingInvitations' => $upcommingInvitations,
        ]);

    }


    /**
     * Show form for invitation start date or if is already set redirect to create invitation
     * 
     * @Route("/pozvanky/{year}/pridat-novu/add", name="invitation_create_from_date", requirements={"year"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     * 
     * @param int $year
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\Response Show form for date or redirect
     */
    public function createInvitationFromDate(int $year, Request $request): Response
    {

        /** @var App\Form\SetDateType $form */
        $form = $this->createForm(SetDateType::class, NULL, [
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
     * 
     * 
     * @Route("/pozvanky/{year}/pridat-novu/{date}/add", name="invitation_create_from_event", requirements={"year"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     * 
     * @param int $year Year
     * @param string $date Event start date
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param App\Repository\EventRepository $eventRepository
     * @param Doctrine\ORM\EntityManagerInterface $entityManager
     * @return Symfony\Component\HttpFoundation\Response Show form or redirect to new invitation
     */
    public function createInvitationFromEvent(int $year, string $date, Request $request, EventRepository $eventRepository, EntityManagerInterface $entityManager): Response
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
        $now = new \DateTime();

        /** @var \App\Entity\Event[] $event **/
        $events = $eventRepository->findBy(['startDate' => $dateTime, 'eventInvitation' => NULL]);

        /** @var \App\Entity\EventInvitation $invitation **/
        $invitation = new EventInvitation();
        if(isset ($events[0])) { //Parent Event exist, get aditionl info from it

            /** @var \App\Entity\Event $firstEvent **/
            $firstEvent = $events[0];

            $invitation->setTitle($firstEvent->getTitle());
            $invitation->setEndDate($firstEvent->getEndDate());
            $invitation->setStartDate($firstEvent->getStartDate());
            if( NULL !== $firstEvent->getSportType() ){
                foreach ($firstEvent->getSportType() as $key => $value) {
                    $invitation->addSportType($firstEvent->getSportType()[$key]);        
                }
            }
            if (NULL !== $firstEvent->getEventInvitation()) {
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

        /** @var App\Form\EventInvitationType $form */
        $form = $this->createForm(EventInvitationType::class, $invitation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var App\Entity\Event $invitation */
            $invitation = $form->getData();

            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($invitation->getTitle());
            $invitation->setSlug(\strval($slug));
            $invitation->setPublishedAt($now);
            $invitation->setCreatedAt($now);
            $invitation->setModifiedAt($now);
            $invitation->setPublish(TRUE);
            $invitation->setCreatedBy($this->getUser());
            
            // remove or update SportTypes for Invitation
            foreach ($originalSportTypes as $sportType) {
                if (FALSE === $invitation->getSportType()->contains($sportType)) {
                    $sportType->removeEventInvitation($invitation);
                    $entityManager->persist($sportType);
                }
            }

            // remove or update Routes for Invitation
            foreach ($originalRoutes as $route) {
                if (FALSE === $invitation->getRoutes()->contains($route)) {
                    $route->removeEventInvitation($invitation);
                    $entityManager->persist($route);
                }
            }

            $entityManager->persist($invitation);
            $entityManager->flush();

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
     * 
     * 
     * @Route("/pozvanky/{year}/{slug}/edit", name="invitation_edit", requirements={"year"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     * 
     * @param int $year Year
     * @param string $slug Event invitation slug
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param App\Repository\EventInvitationRepository $eventInvitationRepository
     * @param Doctrine\ORM\EntityManagerInterface $entityManager
     * @return Symfony\Component\HttpFoundation\Response Show form or redirect to new invitation
     */
    public function editInvitation(int $year, string $slug, Request $request, EventInvitationRepository $eventInvitationRepository, EntityManagerInterface $entityManager): Response
    {

        /** @var \App\Entity\EventInvitation $invitation **/
        if (null === $invitation = $eventInvitationRepository->findByYearSlug($year, $slug)){ // 404
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

        /** @var App\Form\EventInvitationType $form */
        $form = $this->createForm(EventInvitationType::class, $invitation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var \App\Entity\EventInvitation $invitation **/
            $invitation = $form->getData();
            $invitation->setModifiedAt(new \DateTime('now'));
            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($invitation->getTitle());
            $invitation->setSlug(\strval($slug));

            // remove or update SportTypes for Invitation
            foreach ($originalSportTypes as $sportType) {
                if (FALSE === $invitation->getSportType()->contains($sportType)) {
                    $sportType->removeEventInvitation($invitation);
                    $entityManager->persist($sportType);
                }
            }

            // remove or update Routes for Invitation
            foreach ($originalRoutes as $route) {
                if (FALSE === $invitation->getRoutes()->contains($route)) {
                    $route->removeEventInvitation($invitation);
                    $entityManager->persist($route);
                }
            }

            $entityManager->persist($invitation);
            $entityManager->flush();
    
            $this->addFlash(
                'success',
                'Zmeny v pozvánke: „' . $invitation->getTitle() . '“ boli uložené!'
            );

            return $this->redirectToRoute('invitation_show_by_Year_by_Slug', [
                'year' => $year,
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
     * 
     * 
     * @Route("/pozvanky/{year}/{slug}/delete", name="invitation_delete", requirements={"year"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     * 
     * @param int $year Year
     * @param string $slug Event invitation slug
     * @param App\Repository\EventInvitationRepository $eventInvitationRepository
     * @return Symfony\Component\HttpFoundation\Response Show confirmation to delete invitation
     */
    public function prepareDeleteInvitation(int $year, string $slug, EventInvitationRepository $eventInvitationRepository): Response
    {

        /** @var \App\Entity\EventInvitation $invitation **/
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
     * 
     * 
     * @Route("/pozvanky/{year}/{slug}/delete/yes", name="invitation_delete_yes", requirements={"year"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     * 
     * @param int $year Year
     * @param string $slug Event invitation slug
     * @param App\Repository\EventInvitationRepository $eventInvitationRepository
     * @return Symfony\Component\HttpFoundation\Response Redirect to list of invitations for year
     */
    public function deleteInvitation(int $year, string $slug, EventInvitationRepository $eventInvitationRepository): Response
    {

        /** @var \App\Entity\EventInvitation $invitation **/
        $invitation = $eventInvitationRepository->findByYearSlug($year, $slug);
        if(!$invitation) { // 404
            throw $this->createNotFoundException();
        }

        $invitation->removeEvent();
        $invitationTitle = $invitation->getTitle();


        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($invitation);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'Pozvánka: „' . $invitationTitle . '“ bola zmazaná!'
        );

        return $this->redirectToRoute('invitation_list_by_Year', ['year' => $year]);
    }

}