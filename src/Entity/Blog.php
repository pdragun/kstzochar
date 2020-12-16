<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\BlogRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BlogRepository::class)
 */
class Blog
{
    /**
     * @var int $id
     * 
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;


    /**
     * @var string $title
     * 
     * @ORM\Column(type="string", length=190)
     */
    private $title;


    /**
     * @var string $summary
     * 
     * @ORM\Column(type="string", length=190)
     */
    private $summary;


    /**
     * @var string $slug
     * 
     * @ORM\Column(type="string", length=190)
     */
    private $slug;


    /**
     * @var string $content
     * 
     * @ORM\Column(type="text")
     */
    private $content;


    /**
     * @var bool $publish
     * 
     * @ORM\Column(type="boolean")
     */
    private $publish;


    /**
     * @var \App\Entity\BlogSection $section
     * 
     * @ORM\ManyToOne(targetEntity=BlogSection::class, inversedBy="blog")
     * @ORM\JoinColumn(nullable=false)
     */
    private $section;


    /**
     * @var \App\Entity\User $createdBy
     * 
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="blogsCreatedBy")
     * @ORM\JoinColumn(nullable=false)
     */
    private $createdBy;


    /**
     * @var \App\Entity\Event $event
     * 
     * @ORM\OneToOne(targetEntity=Event::class, mappedBy="blog", cascade={"persist", "remove"})
     */
    private $event;


    /**
     * @var ArrayCollection<SportType> $sportType
     * 
     * @ORM\ManyToMany(targetEntity=SportType::class, inversedBy="blogs")
     */
    private $sportType;


    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;


    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $publishedAt;


    /**
     * @var \DateTime $createdAt
     * 
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $modifiedAt;


    /**
     * @var App\Entity\User|null $authorBy
     * 
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="blogsAuthorBy")
     */
    private $authorBy;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $startDate;

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

    public function getPublish(): ?bool
    {
        return $this->publish;
    }

    public function setPublish(bool $publish): self
    {
        $this->publish = $publish;

        return $this;
    }

    /**
     * @return App\Entity\BlogSection $section
     */
    public function getSection(): ?BlogSection
    {
        return $this->section;
    }

    /**
     * @var App\Entity\BlogSection $section
     */
    public function setSection(BlogSection $section): self
    {
        $this->section = $section;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    /**
     * @var App\Entity\User $createdBy
     */
    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

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
            $this->event = \null;
        }

        return $this;
    }

    /**
     * @return ArrayCollection<SportType> $sportType
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

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPublishedAt(): ?\DateTime
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTime $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

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

    public function setAuthorBy(?User $authorBy): self
    {
        $this->authorBy = $authorBy;

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTime $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }
}