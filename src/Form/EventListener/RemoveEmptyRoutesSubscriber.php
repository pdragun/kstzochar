<?php

declare(strict_types=1);

namespace App\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Drop submitted routes without a title and a length, the user does not want to add them.
 * Runs before the collection's ResizeFormListener, which then treats them as deleted rows.
 */
final class RemoveEmptyRoutesSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [FormEvents::PRE_SUBMIT => ['preSubmit', 1]];
    }

    public function preSubmit(FormEvent $event): void
    {
        $routes = $event->getData();
        if (!\is_array($routes)) {
            return;
        }

        $event->setData(array_filter(
            $routes,
            static fn (mixed $route): bool => !\is_array($route)
                || !self::isBlank($route['title'] ?? null)
                || !self::isBlank($route['length'] ?? null),
        ));
    }

    private static function isBlank(mixed $value): bool
    {
        return $value === null || (\is_string($value) && trim($value) === '');
    }
}
