<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;

/** A blog post or a chronicle: written by someone and published on a date */
interface Article
{
    public function getTitle(): ?string;

    public function getSummary(): ?string;

    /** Who wrote it, when someone else entered it */
    public function getAuthorBy(): ?User;

    /** Who entered it */
    public function getCreatedBy(): ?User;

    public function getCreatedAt(): ?DateTimeImmutable;

    public function getPublishedAt(): ?DateTimeImmutable;

    public function getModifiedAt(): ?DateTimeImmutable;
}
