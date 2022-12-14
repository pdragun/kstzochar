<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EventRepository::class)
 */
#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: '`event`')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(type: 'string', length: 190)]
    #[Assert\NotBlank()]
    private $title;


    /**
     * @var \DateTimeImmutable $startDate
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $startDate = null;


    /**
     * @var \DateTimeImmutable $endDate
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endDate = null;


    /**
     * @var \App\Entity\EventInvitation $eventInvitation
     * 
     * @ORM\OneToOne(targetEntity=EventInvitation::class, inversedBy="event", cascade={"persist", "remove"})
     */
    #[OneToOne(targetEntity: EventInvitation::class, inversedBy: 'event', cascade: ['persist', 'remove'])]

    private $eventInvitation;


    /**
     * @var \App\Entity\EventChronicle $eventChronicle
     * 
     * @ORM\OneToOne(targetEntity=EventChronicle::class, inversedBy="event", cascade={"persist", "remove"})
     */
    #[OneToOne(targetEntity: EventChronicle::class, inversedBy: 'event', cascade: ['persist', 'remove'])]

    private $eventChronicle;


    /**
     * @var \App\Entity\Blog $blog
     * 
     * @ORM\OneToOne(targetEntity=Blog::class, inversedBy="event", cascade={"persist", "remove"})
     */
    #[OneToOne(targetEntity: Blog::class, inversedBy: 'event', cascade: ['persist', 'remove'])]

    private $blog;


    /**
     * @var \App\Entity\User $createdBy
     * 
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="eventsCreatedBy")
     * @ORM\JoinColumn(nullable=false)
     */
    #[ManyToOne(targetEntity: User::class, inversedBy: 'eventsCreatedBy')]
    #[JoinColumn(nullable: false)]
    private $createdBy;


    /**
     * @var Collection<int, SportType>
     * 
     * @ORM\ManyToMany(targetEntity=SportType::class, inversedBy="events")
     * @ORM\JoinTable(name="event_sport_type")
     */
    #[ManyToMany(targetEntity: SportType::class, inversedBy: 'events')]
    #[JoinTable(name: 'event_sport_type')]
    private $sportType;


    #[ORM\Column(type: 'boolean')]
    private ?bool $publish = true;


    /**
     * @var \DateTimeImmutable $createdAt
     * 
     * @ORM\Column(type="datetime")
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;


    /**
     * @ORM\Column(type="boolean")
     */
    #[ORM\Column(type: 'boolean')]
    private ?bool $showDate = true;


    /**
     * @var \DateTimeImmutable $modifiedAt
     * 
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $modifiedAt = null;


    /**
     * @var string $content
     * 
     * @ORM\Column(type="text", nullable=true)
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private $content;


    /**
     * @var \DateTimeImmutable $publishedAt
     * 
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;


    /**
     * @var \App\Entity\User
     * 
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="eventsAuthorBy")
     */
    #[ManyToMany(targetEntity: User::class, inversedBy: 'eventsAuthorBy')]
    private $authorBy;


    public function __construct()
    {
        $this->sportType = new ArrayCollection();
    }

    /**
     * @return int $id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string $title
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return \DateTimeImmutable $startDate
     */
    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    /**
     * @param \DateTimeImmutable $startDate
     */
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

    /**
     * @return App\Entity\EventInvitation $eventInvitation
     */
    public function getEventInvitation(): ?EventInvitation
    {
        return $this->eventInvitation;
    }

    /**
     * @param @var App\Entity\EventInvitation $eventInvitation
     */
    public function setEventInvitation(?EventInvitation $eventInvitation): self
    {
        $this->eventInvitation = $eventInvitation;

        return $this;
    }

    public function removeEventInvitation(): self
    {
        $this->eventInvitation = \null;

        return $this;

    }

    /**
     * @return App\Entity\EventChronicle
     */
    public function getEventChronicle(): ?EventChronicle
    {
        return $this->eventChronicle;
    }

    /**
     * @param App\Entity\EventChronicle
     */
    public function setEventChronicle(?EventChronicle $eventChronicle): self
    {
        $this->eventChronicle = $eventChronicle;

        return $this;
    }

    public function removeEventChronicle(): self
    {
        $this->eventChronicle = \null;

        return $this;
    }

    /**
     * @return App\Entity\Blog
     */
    public function getBlog(): ?Blog
    {
        return $this->blog;
    }

    /**
     * @param App\Entity\Blog
     */
    public function setBlog(?Blog $blog): self
    {
        $this->blog = $blog;

        return $this;
    }

    public function removeBlog(): self
    {
        $this->eventBlog = \null;

        return $this;

    }

    /**
     * @return App\Entity\User
     */
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    /**
     * @param App\Entity\User
     */
    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return ArrayCollectionction<SportType>
     */
    public function getSportType(): Collection
    {
        return $this->sportType;
    }

    /**
     * @param App\Entity\SportType $sportType
     */
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return bool $showDate
     */
    public function getShowDate(): ?bool
    {
        return $this->showDate;
    }

    /**
     * @param bool $showDate
     */
    public function setShowDate(bool $showDate): self
    {
        $this->showDate = $showDate;

        return $this;
    }

    /**
     * @return \DateTime $modifiedAt
     */
    public function getModifiedAt(): ?\DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    /**
     * @param \DateTime $modifiedAt
     */
    public function setModifiedAt(?\DateTimeImmutable $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * @return string $content
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return \DateTimeImmutable $publishedAt
     */
    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    /**
     * @param \DateTimeImmutable $publishedAt
     * @return \App\Entity\Event
     */
    public function setPublishedAt(?\DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * @return \App\Entity\User $authorBy
     */
    public function getAuthorBy(): ?User
    {
        return $this->authorBy;
    }

    /**
     * @param \App\Entity\User $authorBy
     * @return \App\Entity\Event
     */
    public function setAuthorBy(?User $authorBy): self
    {
        $this->authorBy = $authorBy;

        return $this;
    }

}