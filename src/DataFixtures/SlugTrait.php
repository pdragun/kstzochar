<?php

declare(strict_types=1);

namespace App\DataFixtures;

use Symfony\Component\String\AbstractUnicodeString;
use Symfony\Component\String\Slugger\AsciiSlugger;

trait SlugTrait
{
    public function createSlug(string $slug): AbstractUnicodeString
    {
        $slugger = new AsciiSlugger();

        return $slugger->slug($slug);
    }
}
