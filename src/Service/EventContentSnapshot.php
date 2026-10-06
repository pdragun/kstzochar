<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\EventContent;
use App\Entity\EventRoute;
use App\Entity\SportType;

/**
 * Links of an invitation or chronicle before the form handles the request.
 * The form edits the entity in place, so EventContentEditor compares against this to find removed links and edited shared routes.
 */
final readonly class EventContentSnapshot
{
    /**
     * @param list<SportType> $sportTypes
     * @param list<EventRoute> $routes
     */
    private function __construct(
        public array $sportTypes,
        public array $routes,
        public SharedRoutes $sharedRoutes,
    ) {
    }

    public static function of(EventContent $content): self
    {
        $routes = array_values($content->getRoutes()->toArray());

        return new self(
            array_values($content->getSportType()->toArray()),
            $routes,
            SharedRoutes::snapshot($routes),
        );
    }
}
