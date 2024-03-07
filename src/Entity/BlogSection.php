<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BlogSectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\AbstractUnicodeString;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BlogSectionRepository::class)]
#[ORM\Table(name: 'blog_section')]
class BlogSection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\Type('string')]
    #[Assert\NotBlank]
    private ?string $title = null;


    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    #[Assert\Type('string')]
    #[Assert\NotBlank]
    private ?string $slug = null;

    /** @var ?Collection<int, Blog> $blog */
    #[ORM\OneToMany(mappedBy: 'section', targetEntity: Blog::class)]
    private ?Collection $blog;

    public function __construct()
    {
        $this->blog = new ArrayCollection();
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

    public function setSlug(AbstractUnicodeString $slug): self
    {
        $this->slug = strval($slug);

        return $this;
    }

    /** @return ?Collection<int, Blog> $blog */
    public function getBlog(): ?Collection
    {
        return $this->blog;
    }

    public function addBlog(Blog $blog): self
    {
        if (!$this->blog->contains($blog)) {
            $this->blog[] = $blog;
            $blog->setSection($this);
        }

        return $this;
    }

    public function removeBlog(Blog $blog): self
    {
        if ($this->blog->contains($blog)) {
            $this->blog->removeElement($blog);
            // set the owning side to null (unless already changed)
            if ($blog->getSection() === $this) {
                $blog->setSection(null);
            }
        }

        return $this;
    }
}
