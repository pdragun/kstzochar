<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EventChronicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Defines the properties of the Event Invitation entity to represent the event invitation posts.
 *
 * @author Peter Dragúň jr. <peter.dragun@gmail.com>
 * 
 * @ORM\Entity(repositoryClass=EventChronicleRepository::class)
 * @ORM\Table(name="event_chronicle")
 */
#[ORM\Entity(repositoryClass: EventChronicleRepository::class)]
#[ORM\Table(name: '`event_chronicle`')]
class EventChronicle
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


    /**
     * //string $publishedAt A "Y-m-d H:i:s" formatted value
     * @var \DateTime $publishedAt
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Type("\DateTimeInterface")
     */
    #[ORM\Column(nullable: true)]
    #[Assert\Type('\DateTimeImmutable')]
    private ?\DateTimeImmutable $publishedAt = null;


    /**
     * @var \DateTime $startDate
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @Assert\Type("\DateTimeInterface")
     */
    #[ORM\Column]
    #[Assert\Type('\DateTimeImmutable')]
    private ?\DateTimeImmutable $startDate = null;
    

    /**
     * @var \DateTime $endDate
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Type("\DateTimeInterface")
     */
    #[ORM\Column(nullable: true)]
    #[Assert\Type('\DateTimeImmutable')]
    private ?\DateTimeImmutable $endDate = null;
    

    /**
     * @var \DateTime $createdAt
     *
     * @Assert\Type("\DateTimeImmutable")
     */
    #[ORM\Column]
    #[Assert\Type('\DateTimeImmutable')]
    private ?\DateTimeImmutable $createdAt = null;


   /**
     * @var string $photoAlbumG URL to Google photos album
     */
    #[ORM\Column(type: 'string', length: 190, unique: true)]
    private $photoAlbumG;


    /**
     * @var \App\Entity\User $createdBy
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'eventChroniclesCreatedBy')]
    #[ORM\JoinColumn(nullable: false)]
    private $createdBy;


    /**
     * @var \App\Entity\Event $event
     */
    #[ORM\OneToOne(targetEntity: Event::class, inversedBy: 'eventChronicle', cascade: ['persist', 'remove'])]
    private $event;


    /**
     * @var Collection<int, SportType> $sportType
     */
    #[ORM\ManyToMany(targetEntity: SportType::class, inversedBy: 'eventChronicle')]
    private $sportType;


    #[ORM\Column(type: 'boolean')]
    private ?bool $publish = true;


    #[ORM\Column]
    private ?\DateTimeImmutable $modifiedAt = null;


    /**
     * @var \App\Entity\User $authorBy
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'eventChroniclesAuthorBy')]
    private $authorBy;


    /**
     * @var Collection<int, EventRoute> $routes
     */
    #[ORM\ManyToMany(targetEntity: EventRoute::class, inversedBy: 'eventChronicles', cascade: ['persist'])]
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
    
    public function getEventId(): ?int
    {
        return $this->eventId;
    }
    
    public function setEventId(int $eventId): self
    {
        $this->eventId = $eventId;

        return $this;
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

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): self
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

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }
    
    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }
    
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPhotoAlbumG(): ?string
    {
        return $this->photoAlbumG;
    }

    public function setPhotoAlbumG(?string $photoAlbumG): self
    {
        $this->photoAlbumG = $photoAlbumG;

        return $this;
    }

    /**
     * @return App\Entity\User|null $createdBy
     */
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
       
        $this->event = $event;

        // set (or unset) the owning side of the relation if necessary
        $newEventChronicle = null === $event ? null : $this;
        if ($event->getEventChronicle() !== $newEventChronicle ) {
            $event->setEventChronicle($newEventChronicle);
        }

        return $this;
    }

    public function removeEvent(): self
    {
        if($this->event) {
            $this->event->removeEventChronicle();
            $this->event = \null;
        }

        return $this;
    }

    /**
     * @return ArrayCollection<SportType>
     */
    public function getSportType(): Collection
    {
        return $this->sportType;
    }

    public function addSportType(SportType $sportType): self
    {
        if (!$this->sportType->contains($sportType)) {
            $this->sportType[] = $sportType;
        }

        return $this;
    }

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

    public function getModifiedAt(): ?\DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?\DateTimeImmutable $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    public function getAuthorBy(): ?User
    {
        return $this->authorBy;
    }

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

    public function addRoute(EventRoute $route): self
    {
        if (!$this->routes->contains($route)) {
            $route->setCreatedAt(new \DateTimeImmutable('now')); //Ugly hack :( createdAt can not be NULL
            $this->routes[] = $route;
        }

        return $this;
    }

    public function removeRoute(EventRoute $route): self
    {
        if ($this->routes->contains($route)) {
            $this->routes->removeElement($route);
        }

        return $this;
    }
}