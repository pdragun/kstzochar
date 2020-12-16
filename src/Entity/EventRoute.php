<?php declare(strict_types=1);

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
 * @ORM\Table(name="event_route")
 * @ORM\Entity(repositoryClass=EventRouteRepository::class)
 */
class EventRoute
{

    /**
     * @var int $id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string $title
     *
     * @ORM\Column(type="text", nullable=false)
     * @Assert\NotBlank
     */
    private $title;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     * @Assert\NotBlank
     * @Assert\Positive
     */
    private $length;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @Assert\DateTime
     * @Assert\NotBlank
     */
    private $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true, length=190)
     * @Assert\IsNull
     */
    private $gpxSlug;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime
     * @Assert\IsNull
     */
    private $eventDate;

    /**
     * @var ArrayCollection<EventInvitation> $eventInvitations
     * 
     * @ORM\ManyToMany(targetEntity=EventInvitation::class, mappedBy="routes")
     */
    private $eventInvitations;

    /**
     * @var ArrayCollection<EventChronicle> $eventChronicles
     * 
     * @ORM\ManyToMany(targetEntity=EventChronicle::class, mappedBy="routes")
     */
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
    
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
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
     * @return ArrayCollection<EventInvitation>
     */
    public function getEventInvitations(): ?Collection
    {
        return $this->eventInvitations;
    }

    /**
     * @var App\Entity\EventInvitation $eventInvitation
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
     * @var App\Entity\EventInvitation $eventInvitation
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
     * @return ArrayCollection<EventChronicle>
     */
    public function getEventChronicles(): ?Collection
    {
        return $this->eventChronicles;
    }

    /**
     * @var App\Entity\EventChronicle $eventChronicle
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
     * @var App\Entity\EventChronicle $eventChronicle
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