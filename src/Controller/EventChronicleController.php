<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\EventChronicle;
use App\Entity\User;
use App\Service\EventContentEditor;
use App\Service\EventContentSnapshot;
use App\Repository\EventChronicleRepository;
use App\Form\EventChronicleType;
use App\Form\SetDateType;
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

/** Stories about past events */
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
        methods: ['GET'],
    )]
    public function showChronicleByYearSlug(
        int $year,
        string $slug,
        EventChronicleRepository $eventChronicleRepository,
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
        methods: ['GET'],
    )]
    public function showChroniclesByYear(
        int $year,
        EventChronicleRepository $eventChronicleRepository,
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
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function createChronicleFromDate(
        int $year,
        Request $request,
    ): RedirectResponse|Response {

        $form = $this->createForm(SetDateType::class, null, [
            'save_button_label' => 'Vytvor kroniku',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $startDate = $form->getData()['startDate'];

            return $this->redirectToRoute('chronicle_create_from_event', [
                'year' => $year,
                'date' => $startDate->format('Y-m-d'),
            ]);
        }

  		return $this->render('event_chronicle/createFromDate.html.twig', [
            'form' => $form->createView(),
            'yearInUrl' => $year,
            'pageTitle' => 'Vytvoriť novú kroniku',
            'chronicleTitle' => 'Nová kronika',
            'actionName' => 'Pridať',
        ]);
    }

    /**
     * Create chronicle
     * Take start date from previous form, check if exist Event (from plan), if yes set Event data to form.
     * @return RedirectResponse|Response Show form or redirect to new chronicle
     * @throws Exception
     */
    #[Route(
        '/kronika/{year}/pridat-novu/{date}/add',
        name: 'chronicle_create_from_event',
        requirements: ['year' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function createChronicleFromEvent(
        int $year,
        string $date,
        Request $request,
        EventContentEditor $editor,
        #[CurrentUser] User $user,
    ): RedirectResponse|Response {
        $dateTime = new DateTimeImmutable($date)->setTime(0, 0, 0);
        $chronicle = new EventChronicle();
        $editor->prefillFromEvent($chronicle, $dateTime);
        $snapshot = EventContentSnapshot::of($chronicle);

        $form = $this->createForm(EventChronicleType::class, $chronicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $editor->create($chronicle, $snapshot, $user);

            $this->addFlash(
                'success',
                sprintf('Nová kronika: „%s“ bola vytvorená a uložená!', $chronicle->getTitle()),
            );

            $chronicleYear = $chronicle->getStartDate()->format('Y');

            return $this->redirectToRoute('chronicle_show_by_Year_Slug', [
                'year' => $chronicleYear,
                'slug' => $chronicle->getSlug(),
            ]);
        }

  		return $this->render('event_chronicle/createFromEvent.html.twig', [
            'form' => $form->createView(),
            'yearInUrl' => $year,
            'pageTitle' => 'Vytvoriť novú kroniku',
            'chronicleTitle' => 'Nová kronika',
            'dateTime' => $dateTime->format('Y-m-d'),
            'actionName' => 'Pridať',
        ]);
    }

    /**
     * Edit chronicle
     * @return RedirectResponse|Response Show form or redirect to new chronicle
     * @throws NonUniqueResultException
     */
    #[Route(
        '/kronika/{year}/{slug}/edit',
        name: 'chronicle_edit',
        requirements: ['year' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function editChronicle(
        int $year,
        string $slug,
        Request $request,
        EventChronicleRepository $eventChronicleRepository,
        EventContentEditor $editor,
    ): RedirectResponse|Response {
        $chronicle = $eventChronicleRepository->findByYearSlug($year, $slug);
        if ($chronicle === null) {
            throw $this->createNotFoundException();
        }
        $snapshot = EventContentSnapshot::of($chronicle);

        $form = $this->createForm(EventChronicleType::class, $chronicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $editor->update($chronicle, $snapshot);

            $this->addFlash(
                'success',
                sprintf('Zmeny v kronike: „%s“ boli uložené!', $chronicle->getTitle()),
            );

            return $this->redirectToRoute('chronicle_show_by_Year_Slug', [
                'year' => $chronicle->getStartDate()->format('Y'),
                'slug' => $chronicle->getSlug(),
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
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function prepareDeleteChronicle(
        int $year,
        string $slug,
        EventChronicleRepository $eventChronicleRepository,
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
     */
    #[Route(
        '/kronika/{year}/{slug}/delete/yes',
        name: 'chronicle_delete_yes',
        requirements: ['year' => '\d+'],
        methods: ['POST'],
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteChronicle(
        int $year,
        string $slug,
        EventChronicleRepository $eventChronicleRepository,
        Request $request,
        EventContentEditor $editor,
    ): RedirectResponse {
        $chronicle = $eventChronicleRepository->findByYearSlug($year, $slug);
        if ($chronicle === null) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('delete-' . $chronicle->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $chronicleTitle = $chronicle->getTitle();
        $editor->delete($chronicle);

        $this->addFlash(
            'success',
            sprintf('Kronika: „%s“ bola zmazaná!', $chronicleTitle),
        );

        if ($eventChronicleRepository->getPreparedByYear($year) === []) { // the year list would be a 404
            return $this->redirectToRoute('chronicle_show');
        }

        return $this->redirectToRoute('chronicle_list_by_Year', ['year' => $year]);
    }
}
