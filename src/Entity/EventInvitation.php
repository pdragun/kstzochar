<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EventInvitationRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
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
    #[Assert\NotBlank]
    private string $title;

    #[ORM\Column(type: 'string', length: 190)]
    #[Assert\NotBlank]
    private string $slug;

    #[ORM\Column(type: 'string', length: 190)]
    #[Assert\NotBlank(message: 'post.blank_summary')]
    private string $summary;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'post.blank_summary')]
    #[Assert\Length(min: 10, minMessage: 'post.too_short_content')]
    private string $content;
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\Type('DateTimeImmutable')]
    private ?DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\Type('DateTimeImmutable')]
    private DateTimeImmutable $startDate;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\Type('DateTimeImmutable')]
    private ?DateTimeImmutable $endDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\Type('DateTimeImmutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'eventInvitationsCreatedBy')]
    #[ORM\JoinColumn(nullable: false)]
    private User $createdBy;

    #[ORM\OneToOne(targetEntity: Event::class, mappedBy: 'eventInvitation', cascade: ['persist'])]
    private ?Event $event = null;

    /** @var ?Collection<int, SportType> $sportType */
    #[ORM\ManyToMany(targetEntity: SportType::class, inversedBy: 'eventInvitations')]
    private ?Collection $sportType;

    #[ORM\Column(type: 'boolean')]
    private bool $publish = true;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\Type('DateTimeImmutable')]
    private ?DateTimeImmutable $modifiedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'eventInvitationsAuthorBy')]
    private User $authorBy;

    /** @var ?Collection<int, EventRoute> $routes */
    #[ORM\ManyToMany(targetEntity: EventRoute::class, inversedBy: 'eventInvitations', cascade: ['persist'])]
    private ?Collection $routes;

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

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(DateTimeImmutable $publishedAt): self
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

    public function getStartDate(): ?DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }
    
    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }
    
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

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
            $this->event = null;
        }

        return $this;
    }

    /** @return ?Collection<int, SportType> */
    public function getSportType(): ?Collection
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

    public function getModifiedAt(): ?DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?DateTimeImmutable $modifiedAt): self
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

    public function getRoutes(): ?Collection
    {
        return $this->routes;
    }

    public function addRoute(EventRoute $route): self
    {
        if (!$this->routes->contains($route)) {
            $route->setCreatedAt(new DateTimeImmutable('now')); //Ugly hack :( createdAt can not be NULL
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
