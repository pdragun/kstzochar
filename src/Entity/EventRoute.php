<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EventRouteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * EventRoute
 * 
 * Defines the properties of the Event Route entity to represent routes for various events.
 * Route can be different for invitation or for chronicle even for the same event.
 * 
 * @author Peter Dragúň jr. <peter.dragun@gmail.com>
 * 
 */
#[ORM\Entity(repositoryClass: EventRouteRepository::class)]
#[ORM\Table(name: '`event_route`')]
class EventRoute
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private $title;


    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private $length;


    #[Assert\DateTime]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $createdAt = null;


    #[ORM\Column(type: 'string', length: 190)]
    #[Assert\IsNull]
    private $gpxSlug;


    #[ORM\Column]
    #[Assert\IsNull]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $eventDate = null;

    /**
     * @var Collection<int, EventInvitation> $eventInvitations
     */
    #[ORM\ManyToMany(targetEntity: EventInvitation::class, mappedBy: 'routes')]
    private $eventInvitations;

    /**
     * @var Collection<int, EventChronicle> $eventChronicles
     */
    #[ORM\ManyToMany(targetEntity: EventChronicle::class, mappedBy: 'routes')]
    private $eventChronicles;

    public function __construct()
    {
        $this->eventInvitations = new ArrayCollection();
        $this->eventChronicles = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getTitle(): ?string
    {
        return $this->title;
    }
    
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }
    
    public function setLength(int $length): self
    {
        $this->length = $length;

        return $this;
    }
    
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getGpxSlug(): ?string
    {
        return $this->gpxSlug;
    }

    public function setGpxSlug(string $gpxSlug): self
    {
        $this->gpxSlug = $gpxSlug;

        return $this;
    }

    /**
     * @return Collection<int, EventInvitation>
     */
    public function getEventInvitations(): ?Collection
    {
        return $this->eventInvitations;
    }

    /**
     * @var \App\Entity\EventInvitation $eventInvitation
     */
    public function addEventInvitation(EventInvitation $eventInvitation): self
    {
        if (!$this->eventInvitations->contains($eventInvitation)) {
            $this->eventInvitations[] = $eventInvitation;
            $eventInvitation->addRoute($this);
        }

        return $this;
    }

    /**
     * @var \App\Entity\EventInvitation $eventInvitation
     */
    public function removeEventInvitation(EventInvitation $eventInvitation): self
    {
        if ($this->eventInvitations->contains($eventInvitation)) {
            $this->eventInvitations->removeElement($eventInvitation);
            $eventInvitation->removeRoute($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, EventChronicle>
     */
    public function getEventChronicles(): ?Collection
    {
        return $this->eventChronicles;
    }

    /**
     * @var \App\Entity\EventChronicle $eventChronicle
     */
    public function addEventChronicle(EventChronicle $eventChronicle): self
    {
        if (!$this->eventChronicles->contains($eventChronicle)) {
            $this->eventChronicles[] = $eventChronicle;
            $eventChronicle->addRoute($this);
        }

        return $this;
    }

    /**
     * @var \App\Entity\EventChronicle $eventChronicle
     */
    public function removeEventChronicle(EventChronicle $eventChronicle): self
    {
        if ($this->eventChronicles->contains($eventChronicle)) {
            $this->eventChronicles->removeElement($eventChronicle);
            $eventChronicle->removeRoute($this);
        }

        return $this;
    }
 
}