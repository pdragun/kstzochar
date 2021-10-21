<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\BlogSectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BlogSectionRepository::class)
 */
class BlogSection
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
     * @ORM\Column(type="string", unique=true, length=190)
     */
    private $slug;

    /**
     * @var ArrayCollection<Blog> $blog
     * 
     * @ORM\OneToMany(targetEntity=Blog::class, mappedBy="section")
     */
    private $blog;

    public function __construct()
    {
        $this->blog = new ArrayCollection();
    }

    /**
     * @return int $id
     */
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

    /**
     * @return ArrayCollection<Blog> $blog
     */
    public function getBlog(): ?Collection
    {
        return $this->blog;
    }

    /**
     * @param App\Entity\Blog $blog
     */
    public function addBlog(Blog $blog): self
    {
        if (!$this->blog->contains($blog)) {
            $this->blog[] = $blog;
            $blog->setSection($this);
        }

        return $this;
    }

    /**
     * @param App\Entity\Blog $blog
     */
    public function removeBlog(Blog $blog): self
    {
        if ($this->blog->contains($blog)) {
            $this->blog->removeElement($blog);
            // set the owning side to null (unless already changed)
            if ($blog->getSection() === $this) {
                $blog->setSection(\null);
            }
        }

        return $this;
    }
}