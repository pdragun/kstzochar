<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EventRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'event')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\Type('string')]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\Type('DateTimeImmutable')]
    #[Assert\NotBlank]
    private ?DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Assert\Type('DateTimeImmutable')]
    private ?DateTimeImmutable $endDate = null;

    #[ORM\OneToOne(inversedBy: 'event', targetEntity: EventInvitation::class, cascade: ['persist', 'remove'])]
    private ?EventInvitation $eventInvitation;

    #[ORM\OneToOne(inversedBy: 'event', targetEntity: EventChronicle::class, cascade: ['persist', 'remove'])]
    private ?EventChronicle $eventChronicle;

    #[ORM\OneToOne(inversedBy: 'event', targetEntity: Blog::class, cascade: ['persist', 'remove'])]
    private ?Blog $blog;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'eventsCreatedBy')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    private User $createdBy;

    /** @var Collection<int, SportType> */
    #[ORM\ManyToMany(targetEntity: SportType::class, inversedBy: 'events')]
    #[ORM\JoinTable(name: 'event_sport_type')]
    private Collection $sportType;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Assert\Type('bool')]
    private ?bool $publish = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\Type('DateTimeImmutable')]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Assert\Type('bool')]
    private ?bool $showDate = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Assert\Type('DateTimeImmutable')]
    private ?DateTimeImmutable $modifiedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $content = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Assert\Type('DateTimeImmutable')]
    private ?DateTimeImmutable $publishedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'eventsAuthorBy')]
    private User $authorBy;

    public function __construct()
    {
        $this->sportType = new ArrayCollection();
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

    public function getEventInvitation(): ?EventInvitation
    {
        return $this->eventInvitation;
    }

    public function setEventInvitation(?EventInvitation $eventInvitation): self
    {
        $this->eventInvitation = $eventInvitation;

        return $this;
    }

    public function removeEventInvitation(): self
    {
        $this->eventInvitation = null;

        return $this;

    }

    public function getEventChronicle(): ?EventChronicle
    {
        return $this->eventChronicle;
    }

    public function setEventChronicle(?EventChronicle $eventChronicle): self
    {
        $this->eventChronicle = $eventChronicle;

        return $this;
    }

    public function removeEventChronicle(): self
    {
        $this->eventChronicle = null;

        return $this;
    }

    public function getBlog(): ?Blog
    {
        return $this->blog;
    }

    public function setBlog(?Blog $blog): self
    {
        $this->blog = $blog;

        return $this;
    }

    public function removeBlog(): self
    {
        $this->blog = null;

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

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getShowDate(): ?bool
    {
        return $this->showDate;
    }

    public function setShowDate(bool $showDate): self
    {
        $this->showDate = $showDate;

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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

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
}
