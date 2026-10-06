<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Blog;
use App\Entity\BlogSection;
use App\Entity\SportType;
use App\Entity\User;
use App\Repository\BlogRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;

/** Saving and deleting blogs, the blog counterpart of EventContentEditor */
final readonly class BlogEditor
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheItemPoolInterface $contentCache,
        private SlugGenerator $slugGenerator,
        private BlogRepository $blogRepository,
    ) {
    }

    /** Publish a new blog submitted by the form in the section */
    public function create(Blog $blog, BlogSection $section, User $createdBy): void
    {
        $now = new DateTimeImmutable();
        $blog->setSection($section);
        $blog->setPublishedAt($now);
        $blog->setCreatedAt($now);
        $blog->setModifiedAt($now);
        $blog->setPublish(true);
        $blog->setCreatedBy($createdBy);

        $this->save($blog);
    }

    /**
     * Save an edited blog submitted by the form
     * @param list<SportType> $originalSportTypes The blog's sport types before the form handled the request
     */
    public function update(Blog $blog, array $originalSportTypes): void
    {
        $blog->setModifiedAt(new DateTimeImmutable());

        // remove the links the form removed from the other side as well
        foreach ($originalSportTypes as $sportType) {
            if (!$blog->getSportType()->contains($sportType)) {
                $sportType->removeBlog($blog);
                $this->entityManager->persist($sportType);
            }
        }

        $this->save($blog);
    }

    public function delete(Blog $blog): void
    {
        $blog->removeEvent();
        $this->entityManager->remove($blog);
        $this->entityManager->flush();

        $this->contentCache->clear();
    }

    /** The slug is unique within the section and the year of creation, which are part of the URL */
    private function save(Blog $blog): void
    {
        $sectionId = $blog->getSection()->getId();
        $year = (int) $blog->getCreatedAt()->format('Y');
        $blog->setSlug($this->slugGenerator->uniqueSlug(
            $blog->getTitle(),
            fn (string $slug): bool => $this->blogRepository->slugExists($sectionId, $year, $slug, $blog->getId()),
        ));

        $this->entityManager->persist($blog);
        $this->entityManager->flush();

        $this->contentCache->clear();
    }
}
