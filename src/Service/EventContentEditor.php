<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\EventChronicle;
use App\Entity\EventContent;
use App\Entity\EventInvitation;
use App\Entity\EventRoute;
use App\Entity\SportType;
use App\Entity\User;
use App\Repository\EventChronicleRepository;
use App\Repository\EventInvitationRepository;
use App\Repository\EventRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Cache\CacheItemPoolInterface;

/** Saving and deleting invitations and chronicles, shared by their controllers */
final readonly class EventContentEditor
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheItemPoolInterface $contentCache,
        private SlugGenerator $slugGenerator,
        private EventRepository $eventRepository,
        private EventInvitationRepository $eventInvitationRepository,
        private EventChronicleRepository $eventChronicleRepository,
    ) {
    }

    /**
     * Start a new invitation or chronicle on the date. When a planned event on that date has none yet, take its title,
     * dates and sport types and link the new one to it; a chronicle also starts with the routes of the event's invitation.
     */
    public function prefillFromEvent(EventContent $content, DateTimeImmutable $date): void
    {
        $event = $this->eventRepository->findOneBy([
            'startDate' => $date,
            $content instanceof EventInvitation ? 'eventInvitation' : 'eventChronicle' => null,
        ]);
        if ($event === null) {
            $content->setStartDate($date);

            return;
        }

        $content->setTitle($event->getTitle());
        $content->setStartDate($event->getStartDate());
        $content->setEndDate($event->getEndDate());
        foreach ($event->getSportType() as $sportType) {
            $content->addSportType($sportType);
        }
        if ($content instanceof EventChronicle) {
            foreach ($event->getEventInvitation()?->getRoutes() ?? [] as $route) {
                $content->addRoute($route);
            }
        }
        $content->setEvent($event);
    }

    /** Publish a new invitation or chronicle submitted by the form */
    public function create(EventContent $content, EventContentSnapshot $snapshot, User $createdBy): void
    {
        $now = new DateTimeImmutable();
        $content->setPublishedAt($now);
        $content->setCreatedAt($now);
        $content->setModifiedAt($now);
        $content->setPublish(true);
        $content->setCreatedBy($createdBy);

        $this->save($content, $snapshot);
    }

    /** Save an edited invitation or chronicle submitted by the form */
    public function update(EventContent $content, EventContentSnapshot $snapshot): void
    {
        $content->setModifiedAt(new DateTimeImmutable());

        $this->save($content, $snapshot);
    }

    public function delete(EventContent $content): void
    {
        $content->removeEvent();
        $this->entityManager->remove($content);
        $this->entityManager->flush();

        $this->contentCache->clear();
    }

    private function save(EventContent $content, EventContentSnapshot $snapshot): void
    {
        $year = (int) $content->getStartDate()->format('Y');
        $repository = $content instanceof EventInvitation ? $this->eventInvitationRepository : $this->eventChronicleRepository;
        $content->setSlug($this->slugGenerator->uniqueSlug(
            (string) $content->getTitle(),
            fn (string $slug): bool => $repository->slugExists($year, $slug, $content->getId()),
        ));

        $snapshot->sharedRoutes->copyEditedSharedRoutes($content);

        // remove the links the form removed from the other side as well
        foreach ($snapshot->sportTypes as $sportType) {
            if (!$content->getSportType()->contains($sportType)) {
                $this->unlink($sportType, $content);
            }
        }
        foreach ($snapshot->routes as $route) {
            if (!$content->getRoutes()->contains($route)) {
                $this->unlink($route, $content);
            }
        }

        $this->entityManager->persist($content);
        $this->entityManager->flush();

        $this->contentCache->clear();
    }

    private function unlink(SportType|EventRoute $linked, EventContent $content): void
    {
        match (true) {
            $content instanceof EventInvitation => $linked->removeEventInvitation($content),
            $content instanceof EventChronicle => $linked->removeEventChronicle($content),
            default => throw new LogicException(sprintf('Unknown event content %s.', $content::class)),
        };
        $this->entityManager->persist($linked);
    }
}
