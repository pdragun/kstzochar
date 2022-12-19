<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BlogRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\AbstractUnicodeString;

#[ORM\Entity(repositoryClass: BlogRepository::class)]
#[ORM\Table(name: '`blog`')]
class Blog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 190)]
    private ?string $title;

    #[ORM\Column(type: 'string', length: 190)]
    private ?string $summary;

    #[ORM\Column(type: 'string', length: 190)]
    private ?string $slug;

    #[ORM\Column(type: 'text')]
    private ?string $content;

    #[ORM\Column(type: 'boolean')]
    private ?bool $publish;

    #[ORM\ManyToOne(targetEntity: BlogSection::class, inversedBy: 'blog')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BlogSection $section = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'blogsCreatedBy')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\OneToOne(targetEntity: Event::class, mappedBy: 'blog', cascade: ['persist', 'remove'])]
    private ?Event $event = null;

    /** @var ?Collection<int, SportType> $sportType */
    #[ORM\ManyToMany(targetEntity: SportType::class, inversedBy: 'blogs')]
    private ?Collection $sportType;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $modifiedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'blogsAuthorBy')]
    private ?User $authorBy = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $startDate = null;

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

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(AbstractUnicodeString $slug): self
    {
        $this->slug = strval($slug);

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

    public function getPublish(): ?bool
    {
        return $this->publish;
    }

    public function setPublish(bool $publish): self
    {
        $this->publish = $publish;

        return $this;
    }

    public function getSection(): ?BlogSection
    {
        return $this->section;
    }

    public function setSection(?BlogSection $section): self
    {
        $this->section = $section;

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
        $newBlog = null === $event ? null : $this;
        if ($event->getBlog() !== $newBlog) {
            $event->setBlog($newBlog);
        }

        return $this;
    }

    public function removeEvent(): self
    {
        if($this->event) {
            $this->event->removeBlog();
            $this->event = null;
        }

        return $this;
    }

    /** @return ?Collection<int, SportType> $sportType */
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

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

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

    public function getStartDate(): ?DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }
}
