<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(type: 'string', length: 190)]
    #[Assert\NotBlank]
    private string $nickName;

    #[ORM\Column(type: 'string', length: 190, unique: true)]
    #[Assert\NotBlank]
    private string $email;

    #[ORM\Column(type: 'json')]
    private ?array $roles = [];

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private string $password;

    /** @var ?Collection<int, EventInvitation> */
    #[ORM\OneToMany(targetEntity: EventInvitation::class, mappedBy: 'createdBy')]
    private ?Collection $eventInvitationsCreatedBy;

    /** @var ?Collection<int, EventChronicle> $eventChroniclesCreatedBy */
    #[ORM\OneToMany(targetEntity: EventChronicle::class, mappedBy: 'createdBy')]
    private ?Collection $eventChroniclesCreatedBy;

    /** @var ?Collection<int, Blog> $blogsCreatedBy */
    #[ORM\OneToMany(targetEntity: Blog::class, mappedBy: 'createdBy')]
    private ?Collection $blogsCreatedBy;

    /** @var ?Collection<int, Event> $eventsCreatedBy */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'createdBy')]
    private ?Collection $eventsCreatedBy;

    #[ORM\Column(type: 'string', length: 190)]
    private string $displayName;

    /** @var ?Collection<int, Blog> $blogsAuthorBy */
    #[ORM\OneToMany(targetEntity: Blog::class, mappedBy: 'authorBy')]
    private ?Collection $blogsAuthorBy;

    /** @var ?Collection<int, Event> $eventsAuthorBy */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'authorBy')]
    private ?Collection $eventsAuthorBy;

    /** @var ?Collection<int, EventInvitation> $eventInvitationsAuthorBy */
    #[ORM\OneToMany(targetEntity: EventInvitation::class, mappedBy: 'authorBy')]
    private ?Collection $eventInvitationsAuthorBy;

    /** @var ?Collection<int, EventChronicle> $eventChroniclesAuthorBy */
    #[ORM\OneToMany(targetEntity: EventChronicle::class, mappedBy: 'authorBy')]
    private ?Collection $eventChroniclesAuthorBy;

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

    public function getUsername(): ?string
    {
        return $this->email;
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
     * The public representation of the user (e.g. a username, an email address, etc.)
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /** @see UserInterface */
    public function getRoles(): array
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

    /** @see PasswordAuthenticatedUserInterface */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /** @see UserInterface */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /** @return ArrayCollection<EventInvitation> */
    public function getEventInvitationsCreatedBy(): Collection
    {
        return $this->eventInvitationsCreatedBy;
    }

    public function addEventInvitationsCreatedBy(EventInvitation $eventInvitationsCreatedBy): self
    {
        if (!$this->eventInvitationsCreatedBy->contains($eventInvitationsCreatedBy)) {
            $this->eventInvitationsCreatedBy[] = $eventInvitationsCreatedBy;
            $eventInvitationsCreatedBy->setCreatedBy($this);
        }

        return $this;
    }

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

    /** @return Collection<int, EventChronicle> */
    public function getEventChroniclesCreatedBy(): Collection
    {
        return $this->eventChroniclesCreatedBy;
    }

    public function addEventChroniclesCreatedBy(EventChronicle $eventChroniclesCreatedBy): self
    {
        if (!$this->eventChroniclesCreatedBy->contains($eventChroniclesCreatedBy)) {
            $this->eventChroniclesCreatedBy[] = $eventChroniclesCreatedBy;
            $eventChroniclesCreatedBy->setCreatedBy($this);
        }

        return $this;
    }

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

    /** @return ?Collection<int, Blog> */
    public function getBlogsCreatedBy(): ?Collection
    {
        return $this->blogsCreatedBy;
    }

    public function addBlogsCreatedBy(Blog $blogsCreatedBy): self
    {
        if (!$this->blogsCreatedBy->contains($blogsCreatedBy)) {
            $this->blogsCreatedBy[] = $blogsCreatedBy;
            $blogsCreatedBy->setCreatedBy($this);
        }

        return $this;
    }

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

    /** @return ?Collection<int, Event> */
    public function getEventsCreatedBy(): ?Collection
    {
        return $this->eventsCreatedBy;
    }

    public function addEventsCreatedBy(Event $eventsCreatedBy): self
    {
        if (!$this->eventsCreatedBy->contains($eventsCreatedBy)) {
            $this->eventsCreatedBy[] = $eventsCreatedBy;
            $eventsCreatedBy->setCreatedBy($this);
        }

        return $this;
    }

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

    /** @return ?Collection<int, Blog> */
    public function getBlogsAuthorBy(): ?Collection
    {
        return $this->blogsAuthorBy;
    }

    public function addBlogsAuthorBy(Blog $blogsAuthorBy): self
    {
        if (!$this->blogsAuthorBy->contains($blogsAuthorBy)) {
            $this->blogsAuthorBy[] = $blogsAuthorBy;
            $blogsAuthorBy->setAuthorBy($this);
        }

        return $this;
    }

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

    /** @return ?Collection<int, Event> */
    public function getEventsAuthorBy(): ?Collection
    {
        return $this->eventsAuthorBy;
    }

    public function addEventsAuthorBy(Event $eventsAuthorBy): self
    {
        if (!$this->eventsAuthorBy->contains($eventsAuthorBy)) {
            $this->eventsAuthorBy[] = $eventsAuthorBy;
            $eventsAuthorBy->setAuthorBy($this);
        }

        return $this;
    }

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

    /** @return ?Collection<int, EventInvitation> */
    public function getEventInvitationsAuthorBy(): ?Collection
    {
        return $this->eventInvitationsAuthorBy;
    }

    public function addEventInvitationsAuthorBy(EventInvitation $eventInvitationsAuthorBy): self
    {
        if (!$this->eventInvitationsAuthorBy->contains($eventInvitationsAuthorBy)) {
            $this->eventInvitationsAuthorBy[] = $eventInvitationsAuthorBy;
            $eventInvitationsAuthorBy->setAuthorBy($this);
        }

        return $this;
    }

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

    /** @return ?Collection<int, EventChronicle> */
    public function getEventChroniclesAuthorBy(): ?Collection
    {
        return $this->eventChroniclesAuthorBy;
    }


    public function addEventChroniclesAuthorBy(EventChronicle $eventChroniclesAuthorBy): self
    {
        if (!$this->eventChroniclesAuthorBy->contains($eventChroniclesAuthorBy)) {
            $this->eventChroniclesAuthorBy[] = $eventChroniclesAuthorBy;
            $eventChroniclesAuthorBy->setAuthorBy($this);
        }

        return $this;
}

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
