<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\EventContent;
use App\Entity\EventRoute;

/**
 * A route can belong to several invitations and chronicles (a chronicle copies the routes of its invitation).
 * The form edits the route entities in place, so a change made in one of them would show up in all of them.
 * Take a snapshot before the form handles the request, then let the edited shared routes be replaced by copies.
 */
final readonly class SharedRoutes
{
    /** @param array<int, array{title: string, length: int, elevation: ?int}> $originalData Keyed by spl_object_id() */
    private function __construct(
        private array $originalData,
    ) {
    }

    /** @param iterable<EventRoute> $routes */
    public static function snapshot(iterable $routes): self
    {
        $originalData = [];
        foreach ($routes as $route) {
            $originalData[spl_object_id($route)] = self::data($route);
        }

        return new self($originalData);
    }

    /**
     * Replace each route that was edited and that also belongs to another invitation or chronicle
     * with an edited copy in the same position, and restore the original route's data
     */
    public function copyEditedSharedRoutes(EventContent $owner): void
    {
        $routes = $owner->getRoutes();
        foreach ($routes->toArray() as $key => $route) {
            $original = $this->originalData[spl_object_id($route)] ?? null;
            if ($original === null || $original === self::data($route) || !self::isShared($route, $owner)) {
                continue;
            }

            $copy = $route->copy();
            $route->setTitle($original['title']);
            $route->setLength($original['length']);
            $route->setElevation($original['elevation']);

            $routes->set($key, $copy); // keep the position, the route list is shown in this order
        }
    }

    /** @return array{title: string, length: int, elevation: ?int} */
    private static function data(EventRoute $route): array
    {
        return [
            'title' => $route->getTitle(),
            'length' => $route->getLength(),
            'elevation' => $route->getElevation(),
        ];
    }

    private static function isShared(EventRoute $route, EventContent $owner): bool
    {
        if ($route->getId() === null) {
            return false;
        }

        return array_any(
            [...$route->getEventInvitations(), ...$route->getEventChronicles()],
            static fn (EventContent $other): bool => $other !== $owner,
        );
    }
}
