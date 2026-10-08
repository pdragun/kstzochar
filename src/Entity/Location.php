<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/** A place where an event starts (meeting point), reused by invitations */
#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ORM\Table(name: 'location')]
#[UniqueEntity('name', message: 'location.name_exists')]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /** The column collation ignores case and accents, so the unique index does too */
    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name = '';

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $addressLocality = '';

    /** ISO 3166-1 alpha-2 */
    #[ORM\Column(type: Types::STRING, length: 2)]
    #[Assert\NotBlank]
    #[Assert\Country]
    private string $addressCountry = 'SK';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $streetAddress = null;

    #[ORM\Column(type: Types::STRING, length: 16, nullable: true)]
    #[Assert\Length(max: 16)]
    private ?string $postalCode = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $addressRegion = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\Range(min: -90, max: 90)]
    private ?float $latitude = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\Range(min: -180, max: 180)]
    private ?float $longitude = null;

    /** @var Collection<int, EventInvitation> */
    #[ORM\OneToMany(targetEntity: EventInvitation::class, mappedBy: 'location')]
    #[ORM\OrderBy(['startDate' => 'DESC'])]
    private Collection $eventInvitations;

    public function __construct()
    {
        $this->eventInvitations = new ArrayCollection();
    }

    #[Assert\Callback]
    public function validateCoordinates(ExecutionContextInterface $context): void
    {
        if (($this->latitude === null) !== ($this->longitude === null)) {
            $context->buildViolation('location.coordinates_pair')
                ->atPath($this->latitude === null ? 'latitude' : 'longitude')
                ->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = self::normalize($name) ?? '';

        return $this;
    }

    public function getAddressLocality(): string
    {
        return $this->addressLocality;
    }

    public function setAddressLocality(?string $addressLocality): self
    {
        $this->addressLocality = self::normalize($addressLocality) ?? '';

        return $this;
    }

    public function getAddressCountry(): string
    {
        return $this->addressCountry;
    }

    public function setAddressCountry(?string $addressCountry): self
    {
        $this->addressCountry = self::normalize($addressCountry) ?? '';

        return $this;
    }

    public function getStreetAddress(): ?string
    {
        return $this->streetAddress;
    }

    public function setStreetAddress(?string $streetAddress): self
    {
        $this->streetAddress = self::normalize($streetAddress);

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = self::normalize($postalCode);

        return $this;
    }

    public function getAddressRegion(): ?string
    {
        return $this->addressRegion;
    }

    public function setAddressRegion(?string $addressRegion): self
    {
        $this->addressRegion = self::normalize($addressRegion);

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    /** @return Collection<int, EventInvitation> */
    public function getEventInvitations(): Collection
    {
        return $this->eventInvitations;
    }

    /** Trim and collapse repeated whitespace; an empty string becomes null */
    private static function normalize(?string $value): ?string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value ?? ''));

        return $value === '' ? null : $value;
    }
}
