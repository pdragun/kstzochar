<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\EventInvitationRepository;
use App\Service\InvitationCalendar;
use DateTimeImmutable;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Invitations as iCalendar files; unpublished invitations are never exported */
class InvitationCalendarController extends AbstractController
{
    /** How long a past trip stays in the subscribed calendar */
    private const string FEED_SINCE = 'today -90 days';

    #[Route('/pozvanky/kalendar.ics', name: 'invitation_ics_feed', methods: ['GET'])]
    public function feed(EventInvitationRepository $eventInvitationRepository, InvitationCalendar $calendar): Response
    {
        $invitations = $eventInvitationRepository->findForCalendar(new DateTimeImmutable(self::FEED_SINCE));

        return $this->calendarResponse($calendar->feed($invitations), 'kst-zochar.ics', HeaderUtils::DISPOSITION_INLINE)
            ->setPublic()
            ->setMaxAge(3600);
    }

    /** @throws NonUniqueResultException */
    #[Route(
        '/pozvanky/{year}/{slug}/kalendar.ics',
        name: 'invitation_ics',
        requirements: ['year' => '\d+'],
        methods: ['GET'],
    )]
    public function single(
        int $year,
        string $slug,
        EventInvitationRepository $eventInvitationRepository,
        InvitationCalendar $calendar,
    ): Response {
        $invitation = $eventInvitationRepository->findByYearSlug($year, $slug);
        if ($invitation === null) {
            throw $this->createNotFoundException();
        }

        return $this->calendarResponse($calendar->single($invitation), $invitation->getSlug() . '.ics', HeaderUtils::DISPOSITION_ATTACHMENT);
    }

    /** $filename must be ASCII; stored slugs are */
    private function calendarResponse(string $ics, string $filename, string $disposition): Response
    {
        return new Response($ics, headers: [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $filename),
        ]);
    }
}
