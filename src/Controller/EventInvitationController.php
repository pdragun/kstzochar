<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\EventInvitation;
use App\Entity\User;
use App\Service\EventContentEditor;
use App\Service\EventContentSnapshot;
use App\Form\EventInvitationType;
use App\Form\SetDateType;
use App\Repository\EventInvitationRepository;
use DateTimeImmutable;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Invitation to event */
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
        methods: ['GET'],
    )]
    public function showEventInvitationsByYear(
        int $year,
        EventInvitationRepository $eventInvitationRepository,
    ): Response {
        $invitations = $eventInvitationRepository->getPreparedByYear($year);
        if ($invitations === []) {
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
        methods: ['GET'],
    )]
    public function showEventInvitationByYearBySlug(
        int $year,
        string $slug,
        EventInvitationRepository $eventInvitationRepository,
    ): Response {
        $invitation = $eventInvitationRepository->findByYearSlug($year, $slug);
        if ($invitation === null) {
            throw $this->createNotFoundException();
        }

  		return $this->render('event_invitation/showEventInvitationByYearBySlug.html.twig', [
            'yearInUrl' => $year,
            'invitation' => $invitation,
        ]);
    }

    /**
     * Show list of invitation with start date in future
     * @return Response Show list invitations
     */
    #[Route('/pozvanky/aktualne', name: 'invitation_list_upcoming', methods: ['GET'])]
    public function showEventInvitationUpcoming(
        EventInvitationRepository $eventInvitationRepository,
    ): Response {
        $upcomingInvitations = $eventInvitationRepository->findLatest();

        return $this->render('event_invitation/showEventInvitationUpcoming.html.twig', [
            'upcomingInvitations' => $upcomingInvitations,
        ]);

    }

    /**
     * Show form for invitation start date or if is already set redirect to create invitation
     * @return RedirectResponse|Response Show form for date or redirect
     */
    #[Route(
        '/pozvanky/{year}/pridat-novu/add',
        name: 'invitation_create_from_date',
        requirements: ['year' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function createInvitationFromDate(int $year, Request $request): RedirectResponse|Response
    {
        $form = $this->createForm(SetDateType::class, null, [
            'save_button_label' => 'Vytvor pozvánku',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $startDate = $form->getData()['startDate'];

            return $this->redirectToRoute('invitation_create_from_event', [
                'year' => $year,
                'date' => $startDate->format('Y-m-d'),
            ]);
        }

  		return $this->render('event_invitation/createFromDate.html.twig', [
            'form' => $form->createView(),
            'yearInUrl' => $year,
            'pageTitle' => 'Vytvoriť novú pozvánku',
            'invitationTitle' => 'Nová pozvánka',
            'actionName' => 'Pridať',
        ]);
    }

    /**
     * Create invitation
     * @return RedirectResponse|Response Show form or redirect to new invitation
     * @throws Exception
     */
    #[Route(
        '/pozvanky/{year}/pridat-novu/{date}/add',
        name: 'invitation_create_from_event',
        requirements: ['year' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function createInvitationFromEvent(
        int $year,
        string $date,
        Request $request,
        EventContentEditor $editor,
        #[CurrentUser] User $user,
    ): RedirectResponse|Response {
        $dateTime = new DateTimeImmutable($date)->setTime(0, 0, 0);
        $invitation = new EventInvitation();
        $editor->prefillFromEvent($invitation, $dateTime);
        $snapshot = EventContentSnapshot::of($invitation);

        $form = $this->createForm(EventInvitationType::class, $invitation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $editor->create($invitation, $snapshot, $user);

            $this->addFlash(
                'success',
                sprintf('Nová pozvánka: „%s“ bola vytvorená a uložená!', $invitation->getTitle()),
            );

            $invitationYear = $invitation->getStartDate()->format('Y');

            return $this->redirectToRoute('invitation_show_by_Year_by_Slug', [
                'year' => $invitationYear,
                'slug' => $invitation->getSlug(),
            ]);
        }

  		return $this->render('event_invitation/createFromEvent.html.twig', [
            'form' => $form->createView(),
            'yearInUrl' => $year,
            'invitationTitle' => 'Nová pozvánka',
            'dateTime' => $dateTime->format('Y-m-d'),
            'actionName' => 'Pridať',
        ]);

    }

    /**
     * Edit invitation
     * @return RedirectResponse|Response Show form or redirect to new invitation
     * @throws NonUniqueResultException
     */
    #[Route(
        '/pozvanky/{year}/{slug}/edit',
        name: 'invitation_edit',
        requirements: ['year' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function editInvitation(
        int $year,
        string $slug,
        Request $request,
        EventInvitationRepository $eventInvitationRepository,
        EventContentEditor $editor,
    ): RedirectResponse|Response {
        $invitation = $eventInvitationRepository->findByYearSlug($year, $slug);
        if ($invitation === null) {
            throw $this->createNotFoundException();
        }
        $snapshot = EventContentSnapshot::of($invitation);

        $form = $this->createForm(EventInvitationType::class, $invitation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $editor->update($invitation, $snapshot);

            $this->addFlash(
                'success',
                sprintf('Zmeny v pozvánke: „%s“ boli uložené!', $invitation->getTitle()),
            );

            return $this->redirectToRoute('invitation_show_by_Year_by_Slug', [
                'year' => $invitation->getStartDate()->format('Y'),
                'slug' => $invitation->getSlug(),
            ]);
        }

  		return $this->render('event_invitation/createFromEvent.html.twig', [
            'form' => $form->createView(),
            'yearInUrl' => $year,
            'invitationTitle' => 'Upraviť túto pozvánku',
            'invitation' => $invitation,
            'dateTime' => $invitation->getStartDate()->format('Y-m-d'),
            'actionName' => 'Upraviť',
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
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function prepareDeleteInvitation(
        int $year,
        string $slug,
        EventInvitationRepository $eventInvitationRepository,
    ): Response {
        $invitation = $eventInvitationRepository->findByYearSlug($year, $slug);
        if ($invitation === null) {
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
     * @throws NonUniqueResultException
     */
    #[Route(
        '/pozvanky/{year}/{slug}/delete/yes',
        name: 'invitation_delete_yes',
        requirements: ['year' => '\d+'],
        methods: ['POST'],
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteInvitation(
        int $year,
        string $slug,
        EventInvitationRepository $eventInvitationRepository,
        Request $request,
        EventContentEditor $editor,
    ): RedirectResponse {
        $invitation = $eventInvitationRepository->findByYearSlug($year, $slug);
        if ($invitation === null) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('delete-' . $invitation->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $invitationTitle = $invitation->getTitle();
        $editor->delete($invitation);

        $this->addFlash(
            'success',
            sprintf('Pozvánka: „%s“ bola zmazaná!', $invitationTitle),
        );

        if ($eventInvitationRepository->getPreparedByYear($year) === []) { // the year list would be a 404
            return $this->redirectToRoute('invitation_show');
        }

        return $this->redirectToRoute('invitation_list_by_Year', ['year' => $year]);
    }
}
