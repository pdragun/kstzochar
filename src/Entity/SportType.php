<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SportTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SportTypeRepository::class)]
#[ORM\Table(name: '`sport_type`')]
class SportType
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
    #[Assert\NotBlank()]
    private $description;


    #[ORM\Column(type: 'string', length: 190)]
    #[Assert\NotBlank()]
    private $shortcut;


    #[ORM\Column(type: 'string', length: 190)]
    #[Assert\NotBlank()]
    private $image;

    /**
     * @var Collection<int, Event> $events
     */
    #[ManyToMany(targetEntity: Event::class, mappedBy: 'sportType')]
    private $events;

    /**
     * @var Collection<int, EventChronicle> $eventChronicles
     */
    #[ManyToMany(targetEntity: EventChronicle::class, mappedBy: 'sportType')]
    private $eventChronicles;

    /**
     * @var Collection<int, Blog> $blogs
     */
    #[ManyToMany(targetEntity: Blog::class, mappedBy: 'sportType')]
    private $blogs;

    /**
     * @var Collection<int, EventInvitation> $eventInvitations
     */
    #[ManyToMany(targetEntity: EventInvitation::class, mappedBy: 'sportType')]
    private $eventInvitations;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->eventChronicles = new ArrayCollection();
        $this->blogs = new ArrayCollection();
        $this->eventInvitations = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getShortcut(): ?string
    {
        return $this->shortcut;
    }

    public function setShortcut(string $shortcut): self
    {
        $this->shortcut = $shortcut;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): ?Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events[] = $event;
            $event->addSportType($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): self
    {
        if ($this->events->contains($event)) {
            $this->events->removeElement($event);
            $event->removeSportType($this);
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

    public function addEventChronicle(EventChronicle $eventChronicle): self
    {
        if (!$this->eventChronicles->contains($eventChronicle)) {
            $this->eventChronicles[] = $eventChronicle;
            $eventChronicle->addSportType($this);
        }

        return $this;
    }

    public function removeEventChronicle(EventChronicle $eventChronicle): self
    {
        if ($this->eventChronicles->contains($eventChronicle)) {
            $this->eventChronicles->removeElement($eventChronicle);
            $eventChronicle->removeSportType($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Blog>
     */
    public function getBlogs(): ?Collection
    {
        return $this->blogs;
    }

    public function addBlog(Blog $blog): self
    {
        if (!$this->blogs->contains($blog)) {
            $this->blogs[] = $blog;
            $blog->addSportType($this);
        }

        return $this;
    }

    public function removeBlog(Blog $blog): self
    {
        if ($this->blogs->contains($blog)) {
            $this->blogs->removeElement($blog);
            $blog->removeSportType($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, EventInvitation>
     */
    public function getEventInvitations(): ?Collection
    {
        return $this->eventInvitations;
    }

    public function addEventInvitation(EventInvitation $eventInvitation): self
    {
        if (!$this->eventInvitations->contains($eventInvitation)) {
            $this->eventInvitations[] = $eventInvitation;
            $eventInvitation->addSportType($this);
        }

        return $this;
    }

    public function removeEventInvitation(EventInvitation $eventInvitation): self
    {
        if ($this->eventInvitations->contains($eventInvitation)) {
            $this->eventInvitations->removeElement($eventInvitation);
            $eventInvitation->removeSportType($this);
        }

        return $this;
    }
}