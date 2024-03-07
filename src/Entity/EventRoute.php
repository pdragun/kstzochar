<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EventRouteRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRouteRepository::class)]
#[ORM\Table(name: 'event_route')]
class EventRoute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    #[Assert\Type('string')]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Type('integer')]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?int $length = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\Type('DateTimeImmutable')]
    #[Assert\NotBlank]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Type('string')]
    private ?string $gpxSlug = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Assert\Type('DateTimeImmutable')]
    private ?DateTimeImmutable $eventDate = null;

    /** @var ?Collection<int, EventInvitation> $eventInvitations */
    #[ORM\ManyToMany(targetEntity: EventInvitation::class, mappedBy: 'routes')]
    private ?Collection $eventInvitations;

    /** @var ?Collection<int, EventChronicle> $eventChronicles */
    #[ORM\ManyToMany(targetEntity: EventChronicle::class, mappedBy: 'routes')]
    private ?Collection $eventChronicles;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Type('integer')]
    #[Assert\Positive]
    private ?int $elevation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $gpx = null;

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
    
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
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

    public function getEventAt(): ?DateTimeImmutable
    {
        return $this->eventDate;
    }

    public function setEventAt(DateTimeImmutable $eventDate): self
    {
        $this->eventDate = $eventDate;

        return $this;
    }

    /** @return ?Collection<int, EventInvitation> */
    public function getEventInvitations(): ?Collection
    {
        return $this->eventInvitations;
    }

    public function addEventInvitation(EventInvitation $eventInvitation): self
    {
        if (!$this->eventInvitations->contains($eventInvitation)) {
            $this->eventInvitations[] = $eventInvitation;
            $eventInvitation->addRoute($this);
        }

        return $this;
    }

    public function removeEventInvitation(EventInvitation $eventInvitation): self
    {
        if ($this->eventInvitations->contains($eventInvitation)) {
            $this->eventInvitations->removeElement($eventInvitation);
            $eventInvitation->removeRoute($this);
        }

        return $this;
    }

    public function getEventChronicles(): ?Collection
    {
        return $this->eventChronicles;
    }

    public function addEventChronicle(EventChronicle $eventChronicle): self
    {
        if (!$this->eventChronicles->contains($eventChronicle)) {
            $this->eventChronicles[] = $eventChronicle;
            $eventChronicle->addRoute($this);
        }

        return $this;
    }

    public function removeEventChronicle(EventChronicle $eventChronicle): self
    {
        if ($this->eventChronicles->contains($eventChronicle)) {
            $this->eventChronicles->removeElement($eventChronicle);
            $eventChronicle->removeRoute($this);
        }

        return $this;
    }

    public function getElevation(): ?int
    {
        return $this->elevation;
    }

    public function setElevation(?int $elevation): self
    {
        $this->elevation = $elevation;

        return $this;
    }

    public function getGpx(): ?string
    {
        return $this->gpx;
    }

    public function setGpx(?string $gpx): self
    {
        $this->gpx = $gpx;

        return $this;
    }
 }
