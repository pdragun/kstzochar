<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User implements UserInterface
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
     * @var string $nickName
     *
     * @ORM\Column(type="string", length=190)
     * @Assert\NotBlank()
     */
    private $nickName;


    /**
     * @var string $email
     * 
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;


    /**
     * @var array $roles
     * 
     * @ORM\Column(type="json")
     */
    private $roles = [];


    /**
     * @var string The hashed password
     * 
     * @ORM\Column(type="string")
     */
    private $password;


    /**
     * @var ArrayCollection<EventInvitation> $eventInvitationsCreatedBy
     * 
     * @ORM\OneToMany(targetEntity=EventInvitation::class, mappedBy="createdBy")
     */
    private $eventInvitationsCreatedBy;


    /**
     * @var ArrayCollection<EventChronicle> $eventChroniclesCreatedBy
     * 
     * @ORM\OneToMany(targetEntity=EventChronicle::class, mappedBy="createdBy")
     */
    private $eventChroniclesCreatedBy;


    /**
     * @var ArrayCollection<Blog> $blogsCreatedBy
     * 
     * @ORM\OneToMany(targetEntity=Blog::class, mappedBy="createdBy")
     */
    private $blogsCreatedBy;


    /**
     * @var ArrayCollection<Event> $eventsCreatedBy
     * 
     * @ORM\OneToMany(targetEntity=Event::class, mappedBy="createdBy")
     */
    private $eventsCreatedBy;


    /**
     * @ORM\Column(type="string", length=190)
     */
    private $displayName;


    /**
     * @var ArrayCollection<Blog> $blogsAuthorBy
     * 
     * @ORM\OneToMany(targetEntity=Blog::class, mappedBy="authorBy")
     */
    private $blogsAuthorBy;


    /**
     * @var ArrayCollection<Event> $eventsAuthorBy
     * 
     * @ORM\OneToMany(targetEntity=Event::class, mappedBy="authorBy")
     */
    private $eventsAuthorBy;


    /**
     * @var ArrayCollection<EventInvitation> $eventInvitationsAuthorBy
     * 
     * @ORM\OneToMany(targetEntity=EventInvitation::class, mappedBy="authorBy")
     */
    private $eventInvitationsAuthorBy;


    /**
     * @var ArrayCollection<EventChronicle> $eventChroniclesAuthorBy
     * 
     * @ORM\OneToMany(targetEntity=EventChronicle::class, mappedBy="authorBy")
     */
    private $eventChroniclesAuthorBy;


    public function __construct()
    {
        $this->eventInvitationsCreatedBy = new ArrayCollection();
        $this->eventChroniclesCreatedBy = new ArrayCollection();
        $this->blogsCreatedBy = new ArrayCollection();
        $this->eventsCreatedBy = new ArrayCollection();
        $this->blogsAuthorBy = new ArrayCollection();
        $this->eventsAuthorBy = new ArrayCollection();
        $this->eventInvitationsAuthorBy = new ArrayCollection();
        $this->eventChroniclesAuthorBy = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): ?string
    {
        return (string) $this->email;
    }

    public function getNickName(): ?string
    {
        return $this->nickName;
    }

    public function setNickName(string $nickName): self
    {
        $this->nickName = $nickName;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): ?array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): ?string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return ArrayCollection<EventInvitation>
     */
    public function getEventInvitationsCreatedBy(): Collection
    {
        return $this->eventInvitationsCreatedBy;
    }

    /**
     * @param App\Entity\EventInvitation $eventInvitationsCreatedBy
     */
    public function addEventInvitationsCreatedBy(EventInvitation $eventInvitationsCreatedBy): self
    {
        if (!$this->eventInvitationsCreatedBy->contains($eventInvitationsCreatedBy)) {
            $this->eventInvitationsCreatedBy[] = $eventInvitationsCreatedBy;
            $eventInvitationsCreatedBy->setCreatedBy($this);
        }

        return $this;
    }

    /**
     * @param App\Entity\EventInvitation $eventInvitationsCreatedBy
     */
    public function removeEventInvitationsCreatedBy(EventInvitation $eventInvitationsCreatedBy): self
    {
        if ($this->eventInvitationsCreatedBy->contains($eventInvitationsCreatedBy)) {
            $this->eventInvitationsCreatedBy->removeElement($eventInvitationsCreatedBy);
            // set the owning side to null (unless already changed)
            if ($eventInvitationsCreatedBy->getCreatedBy() === $this) {
                $eventInvitationsCreatedBy->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return ArrayCollection<EventChronicle>
     */
    public function getEventChroniclesCreatedBy(): Collection
    {
        return $this->eventChroniclesCreatedBy;
    }

    /**
     * @param App\Entity\EventChronicle $eventChroniclesCreatedBy
     */
    public function addEventChroniclesCreatedBy(EventChronicle $eventChroniclesCreatedBy): self
    {
        if (!$this->eventChroniclesCreatedBy->contains($eventChroniclesCreatedBy)) {
            $this->eventChroniclesCreatedBy[] = $eventChroniclesCreatedBy;
            $eventChroniclesCreatedBy->setCreatedBy($this);
        }

        return $this;
    }

    /**
     * @param App\Entity\EventChronicle $eventChroniclesCreatedBy
     */
    public function removeEventChroniclesCreatedBy(EventChronicle $eventChroniclesCreatedBy): self
    {
        if ($this->eventChroniclesCreatedBy->contains($eventChroniclesCreatedBy)) {
            $this->eventChroniclesCreatedBy->removeElement($eventChroniclesCreatedBy);
            // set the owning side to null (unless already changed)
            if ($eventChroniclesCreatedBy->getCreatedBy() === $this) {
                $eventChroniclesCreatedBy->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return ArrayCollection<Blog>
     */
    public function getBlogsCreatedBy(): ?Collection
    {
        return $this->blogsCreatedBy;
    }

    /**
     * @param App\Entity\Blog $blogsCreatedBy
     */
    public function addBlogsCreatedBy(Blog $blogsCreatedBy): self
    {
        if (!$this->blogsCreatedBy->contains($blogsCreatedBy)) {
            $this->blogsCreatedBy[] = $blogsCreatedBy;
            $blogsCreatedBy->setCreatedBy($this);
        }

        return $this;
    }

    /**
     * @param App\Entity\Blog $blogsCreatedBy
     */
    public function removeBlogsCreatedBy(Blog $blogsCreatedBy): self
    {
        if ($this->blogsCreatedBy->contains($blogsCreatedBy)) {
            $this->blogsCreatedBy->removeElement($blogsCreatedBy);
            // set the owning side to null (unless already changed)
            if ($blogsCreatedBy->getCreatedBy() === $this) {
                $blogsCreatedBy->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return ArrayCollection<Event>
     */
    public function getEventsCreatedBy(): ?Collection
    {
        return $this->eventsCreatedBy;
    }

    /**
     * @param App\Entity\Event $eventsCreatedBy
     */
    public function addEventsCreatedBy(Event $eventsCreatedBy): self
    {
        if (!$this->eventsCreatedBy->contains($eventsCreatedBy)) {
            $this->eventsCreatedBy[] = $eventsCreatedBy;
            $eventsCreatedBy->setCreatedBy($this);
        }

        return $this;
    }

    /**
     * @param App\Entity\Event $eventsCreatedBy
     */
    public function removeEventsCreatedBy(Event $eventsCreatedBy): self
    {
        if ($this->eventsCreatedBy->contains($eventsCreatedBy)) {
            $this->eventsCreatedBy->removeElement($eventsCreatedBy);
            // set the owning side to null (unless already changed)
            if ($eventsCreatedBy->getCreatedBy() === $this) {
                $eventsCreatedBy->setCreatedBy(null);
            }
        }

        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * @return ArrayCollection<Blog>
     */
    public function getBlogsAuthorBy(): ?Collection
    {
        return $this->blogsAuthorBy;
    }

    /**
     * @param App\Entity\Blog $blogsAuthorBy
     */
    public function addBlogsAuthorBy(Blog $blogsAuthorBy): self
    {
        if (!$this->blogsAuthorBy->contains($blogsAuthorBy)) {
            $this->blogsAuthorBy[] = $blogsAuthorBy;
            $blogsAuthorBy->setAuthorBy($this);
        }

        return $this;
    }

    /**
     * @param App\Entity\Blog $blogsAuthorBy
     */
    public function removeBlogsAuthorBy(Blog $blogsAuthorBy): self
    {
        if ($this->blogsAuthorBy->contains($blogsAuthorBy)) {
            $this->blogsAuthorBy->removeElement($blogsAuthorBy);
            // set the owning side to null (unless already changed)
            if ($blogsAuthorBy->getAuthorBy() === $this) {
                $blogsAuthorBy->setAuthorBy(null);
            }
        }

        return $this;
    }

    /**
     * @return ArrayCollection<Event>
     */
    public function getEventsAuthorBy(): ?Collection
    {
        return $this->eventsAuthorBy;
    }

    /**
     * @param App\Entity\Event $eventsAuthorBy
     */
    public function addEventsAuthorBy(Event $eventsAuthorBy): self
    {
        if (!$this->eventsAuthorBy->contains($eventsAuthorBy)) {
            $this->eventsAuthorBy[] = $eventsAuthorBy;
            $eventsAuthorBy->setAuthorBy($this);
        }

        return $this;
    }

    /**
     * @param App\Entity\Event $eventsAuthorBy
     */
    public function removeEventsAuthorBy(Event $eventsAuthorBy): self
    {
        if ($this->eventsAuthorBy->contains($eventsAuthorBy)) {
            $this->eventsAuthorBy->removeElement($eventsAuthorBy);
            // set the owning side to null (unless already changed)
            if ($eventsAuthorBy->getAuthorBy() === $this) {
                $eventsAuthorBy->setAuthorBy(null);
            }
        }

        return $this;
    }

    /**
     * @return ArrayCollection<EventInvitation>
     */
    public function getEventInvitationsAuthorBy(): ?Collection
    {
        return $this->eventInvitationsAuthorBy;
    }

    /**
     * @param App\Entity\EventInvitation $eventInvitationsAuthorBy
     */
    public function addEventInvitationsAuthorBy(EventInvitation $eventInvitationsAuthorBy): self
    {
        if (!$this->eventInvitationsAuthorBy->contains($eventInvitationsAuthorBy)) {
            $this->eventInvitationsAuthorBy[] = $eventInvitationsAuthorBy;
            $eventInvitationsAuthorBy->setAuthorBy($this);
        }

        return $this;
    }

    /**
     * @param App\Entity\EventInvitation $eventInvitationsAuthorBy
     */
    public function removeEventInvitationsAuthorBy(EventInvitation $eventInvitationsAuthorBy): self
    {
        if ($this->eventInvitationsAuthorBy->contains($eventInvitationsAuthorBy)) {
            $this->eventInvitationsAuthorBy->removeElement($eventInvitationsAuthorBy);
            // set the owning side to null (unless already changed)
            if ($eventInvitationsAuthorBy->getAuthorBy() === $this) {
                $eventInvitationsAuthorBy->setAuthorBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<EventChronicle>
     */
    public function getEventChroniclesAuthorBy(): ?Collection
    {
        return $this->eventChroniclesAuthorBy;
    }


    /**
     * @param App\Entity\EventChronicle $eventChroniclesAuthorBy
     */
    public function addEventChroniclesAuthorBy(EventChronicle $eventChroniclesAuthorBy): self
    {
        if (!$this->eventChroniclesAuthorBy->contains($eventChroniclesAuthorBy)) {
            $this->eventChroniclesAuthorBy[] = $eventChroniclesAuthorBy;
            $eventChroniclesAuthorBy->setAuthorBy($this);
        }

        return $this;
    }

    /**
     * @param App\Entity\EventChronicle $eventChroniclesAuthorBy
     */
    public function removeEventChroniclesAuthorBy(EventChronicle $eventChroniclesAuthorBy): self
    {
        if ($this->eventChroniclesAuthorBy->contains($eventChroniclesAuthorBy)) {
            $this->eventChroniclesAuthorBy->removeElement($eventChroniclesAuthorBy);
            // set the owning side to null (unless already changed)
            if ($eventChroniclesAuthorBy->getAuthorBy() === $this) {
                $eventChroniclesAuthorBy->setAuthorBy(null);
            }
        }

        return $this;
    }

}