<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EventInvitationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Defines the properties of the Event entity to represent the event.
 * 
 * Events are in plan per year, invitation and chronicle should be linked to planned event.
 *
 * @author Peter Dragúň jr. <peter.dragun@gmail.com>
 */
#[ORM\Entity(repositoryClass: EventInvitationRepository::class)]
#[ORM\Table(name: '`event_invitation`')]
class EventInvitation
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(type: 'string', length: 190)]
    #[Assert\NotBlank()]
    private $title;


    #[ORM\Column(type: 'string', length: 190)]
    #[Assert\NotBlank()]
    private $slug;


    #[ORM\Column(type: 'string', length: 190)]
    #[Assert\NotBlank(message: 'post.blank_summary')]
    private $summary;


    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'post.blank_summary')]
    #[Assert\Length(min: 10, minMessage: 'post.too_short_content')]
    private $content;


    #[ORM\Column(nullable: true)]
    #[Assert\Type('\DateTimeImmutable')]
    private ?\DateTimeImmutable $publishedAt = null;


    #[ORM\Column]
    #[Assert\Type('\DateTimeImmutable')]
    private ?\DateTimeImmutable $startDate = null;


    #[ORM\Column(nullable: true)]
    #[Assert\Type('\DateTimeImmutable')]
    private ?\DateTimeImmutable $endDate = null;


    #[ORM\Column]
    #[Assert\Type('\DateTimeImmutable')]
    private ?\DateTimeImmutable $createdAt = null;


    /**
     * @var \App\Entity\User $createdBy
     * 
     * @ORM\ManyToOne(targetEntity=App\Entity\User::class, inversedBy="eventInvitationsCreatedBy")
     * @ORM\JoinColumn(nullable=false)
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'eventInvitationsCreatedBy')]
    #[ORM\JoinColumn(nullable: false)]
    private $createdBy;

    /**
     * @var \App\Entity\Event $event
     * 
     * @ORM\OneToOne(targetEntity=Event::class, mappedBy="eventInvitation", cascade={"persist"})
     */
    #[ORM\OneToOne(targetEntity: Event::class, mappedBy: 'eventInvitation', cascade: ['persist'])]
    private $event;

    /**
     * @var Collection<int, SportType> $sportType
     * 
     * @ORM\ManyToMany(targetEntity=SportType::class, inversedBy="eventInvitations")
     */
    #[ORM\ManyToMany(targetEntity: SportType::class, inversedBy: 'eventInvitations')]
    private $sportType;

    /**
     * @var boolean $publish
     * 
     * @ORM\Column(type="boolean")
     */    
    #[ORM\Column(type: 'boolean')]
    private ?bool $publish = true;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Type("\DateTimeInterface")
     */
    #[ORM\Column(nullable: true)]
    #[Assert\Type('\DateTimeImmutable')]
    private ?\DateTimeImmutable $modifiedAt = null;

    /**
     * @var \App\Entity\User $authorBy
     * 
     * @ORM\ManyToOne(targetEntity=App\Entity\User::class, inversedBy="eventInvitationsAuthorBy")
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'eventInvitationsAuthorBy')]
    private $authorBy;

    /**
     * @var Collection<int, EventRoute> $routes
     * @ORM\ManyToMany(targetEntity=EventRoute::class, inversedBy="eventInvitations", cascade={"persist"})
     */
    #[ORM\ManyToMany(targetEntity: EventRoute::class, inversedBy: 'eventInvitations', cascade: ['persist'])]
    private $routes;


    public function __construct()
    {
         $this->sportType = new ArrayCollection();
         $this->routes = new ArrayCollection();
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getPublishedAt(): ?\DateTime
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTime $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }


    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }
    
    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTime $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }
    
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    /**
     * @var App\Entity\User $createdBy
     */
    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Event|null $event
     */
    public function getEvent(): ?Event
    {
        return $this->event;
    }

    /**
     * @var App\Entity\Event $event
     */
    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        // set (or unset) the owning side of the relation if necessary
        $newEventInvitation = null === $event ? null : $this;
        if ($event->getEventInvitation() !== $newEventInvitation) {
            $event->setEventInvitation($newEventInvitation);
        }

        return $this;
    }

    public function removeEvent(): self
    {
        if($this->event) {
            $this->event->removeEventInvitation();
            $this->event = \null;
        }

        return $this;
    }

    /**
     * @return ArrayCollection<SportType>
     */
    public function getSportType(): ?Collection
    {
        return $this->sportType;
    }

    /**
     * @var App\Entity\SportType $sportType
     */
    public function addSportType(SportType $sportType): self
    {
        if (!$this->sportType->contains($sportType)) {
            $this->sportType[] = $sportType;
        }

        return $this;
    }

    /**
     * @var App\Entity\SportType $sportType
     */
    public function removeSportType(SportType $sportType): self
    {
        if ($this->sportType->contains($sportType)) {
            $this->sportType->removeElement($sportType);
        }

        return $this;
    }

    public function getPublish(): ?bool
    {
        return $this->publish;
    }

    public function setPublish(bool $publish): self
    {
        $this->publish = $publish;

        return $this;
    }

    public function getModifiedAt(): ?\DateTime
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?\DateTime $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    public function getAuthorBy(): ?User
    {
        return $this->authorBy;
    }

    /**
     * @param App\Entity\User|null $authorBy
     */
    public function setAuthorBy(?User $authorBy): self
    {
        $this->authorBy = $authorBy;

        return $this;
    }

    /**
     * @return ArrayCollection<EventRoute>
     */
    public function getRoutes(): ?Collection
    {
        return $this->routes;
    }

    /**
     * @var App\Entity\EventRoute $route
     */
    public function addRoute(EventRoute $route): self
    {
        if (!$this->routes->contains($route)) {
            $route->setCreatedAt(new \DateTime('now')); //Ugly hack :( createdAt can not be NULL
            $this->routes[] = $route;
        }

        return $this;
    }

    /**
     * @var App\Entity\EventRoute $route
     */
    public function removeRoute(EventRoute $route): self
    {
        if ($this->routes->contains($route)) {
            $this->routes->removeElement($route);
        }

        return $this;
    }
}