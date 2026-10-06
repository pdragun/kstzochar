<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\String\AbstractUnicodeString;
use Symfony\Component\String\Slugger\AsciiSlugger;

/** Builds a slug from a title that no other entry in the same scope (year, section) uses */
class SlugGenerator
{
    private readonly AsciiSlugger $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger();
    }

    /**
     * Append -2, -3, … to the slug until $exists returns false
     * @param callable(string): bool $exists Whether another entry in the scope already uses the slug
     */
    public function uniqueSlug(string $title, callable $exists): AbstractUnicodeString
    {
        $base = $this->slugger->slug($title);
        $slug = $base;
        for ($i = 2; $exists($slug->toString()); $i++) {
            $slug = $base->append('-' . $i);
        }

        return $slug;
    }
}
