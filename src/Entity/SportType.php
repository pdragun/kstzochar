<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\SportTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SportTypeRepository::class)
 */
class SportType
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
     * @var string $slug
     * 
     * @ORM\Column(type="string", length=190)
     */
    private $slug;

    /**
     * @var string $description
     * 
     * @ORM\Column(type="string", length=190)
     */
    private $description;

    /**
     * @var string $shortcut
     * 
     * @ORM\Column(type="string", length=190)
     */
    private $shortcut;

    /**
     * @var string $image
     * 
     * @ORM\Column(type="string", length=190, nullable=true)
     */
    private $image;

    /**
     * @var ArrayCollection<Event> $events
     * 
     * @ORM\ManyToMany(targetEntity=Event::class, mappedBy="sportType")
     */
    private $events;

    /**
     * @var ArrayCollection<EventChronicle> $eventChronicles
     * 
     * @ORM\ManyToMany(targetEntity=EventChronicle::class, mappedBy="sportType")
     */
    private $eventChronicles;

    /**
     * @var ArrayCollection<Blog> $blogs
     * 
     * @ORM\ManyToMany(targetEntity=Blog::class, mappedBy="sportType")
     */
    private $blogs;

    /**
     * @var ArrayCollection<EventInvitation> $eventInvitations
     * 
     * @ORM\ManyToMany(targetEntity=EventInvitation::class, mappedBy="sportType")
     */
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
     * @return ArrayCollection<Event>
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
     * @return ArrayCollection<EventChronicle>
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
     * @return ArrayCollection<Blog>
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
     * @return ArrayCollection<EventInvitation>
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