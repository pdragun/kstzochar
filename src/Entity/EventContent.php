<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\String\AbstractUnicodeString;

/** An invitation or a chronicle of an event, written by the club and addressed by /{section}/{start year}/{slug} */
interface EventContent
{
    public function getId(): ?int;

    public function getTitle(): ?string;

    public function setTitle(string $title): self;

    public function setSlug(AbstractUnicodeString $slug): self;

    public function setPublishedAt(DateTimeImmutable $publishedAt): self;

    public function getStartDate(): ?DateTimeImmutable;

    public function setStartDate(DateTimeImmutable $startDate): self;

    public function setEndDate(?DateTimeImmutable $endDate): self;

    public function setCreatedAt(?DateTimeImmutable $createdAt): self;

    public function setCreatedBy(?User $createdBy): self;

    public function setModifiedAt(?DateTimeImmutable $modifiedAt): self;

    public function setPublish(bool $publish): self;

    public function setEvent(?Event $event): self;

    public function removeEvent(): self;

    /** @return Collection<int, SportType> */
    public function getSportType(): Collection;

    public function addSportType(SportType $sportType): self;

    /** @return Collection<int, EventRoute> */
    public function getRoutes(): Collection;

    public function addRoute(EventRoute $route): self;

    public function removeRoute(EventRoute $route): self;
}
