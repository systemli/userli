<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

trait EventListenerAttributes
{
    /**
     * Returns the events a listener class handles, as [event => [method, priority]].
     *
     * @param class-string $class
     *
     * @return array<string, array{string, int}>
     */
    private static function listenedEvents(string $class): array
    {
        $events = [];

        foreach (new ReflectionClass($class)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(AsEventListener::class) as $attribute) {
                $listener = $attribute->newInstance();
                $events[(string) $listener->event] = [$method->getName(), $listener->priority ?? 0];
            }
        }

        return $events;
    }
}
