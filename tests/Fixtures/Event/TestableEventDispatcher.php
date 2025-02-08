<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Tests\Fixtures\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestableEventDispatcher implements EventDispatcherInterface
{
    private static array $dispatchedEvents = [];

    public function __construct(private readonly EventDispatcherInterface $decorated)
    {
    }

    public function dispatch(object $event, ?string $eventName = null): object
    {
        $eventName ??= $event::class;
        self::$dispatchedEvents[$eventName][] = $event;

        return $this->decorated->dispatch($event, $eventName);
    }

    public function getDispatchedEvents(): array
    {
        return self::$dispatchedEvents;
    }

    public function hasEvent(string $eventName): bool
    {
        return array_key_exists($eventName, self::$dispatchedEvents);
    }

    public function getEventsFor(string $eventName): array
    {
        return self::$dispatchedEvents[$eventName] ?? [];
    }

    public function resetDispatchedEvents(): void
    {
        self::$dispatchedEvents = [];
    }

    public function addListener(string $eventName, callable|array $listener, int $priority = 0): void
    {
        $this->decorated->addListener($eventName, $listener, $priority);
    }

    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
        $this->decorated->addSubscriber($subscriber);
    }

    public function removeListener(string $eventName, callable $listener): void
    {
        $this->decorated->removeListener($eventName, $listener);
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber): void
    {
        $this->decorated->removeSubscriber($subscriber);
    }

    public function getListeners(?string $eventName = null): array
    {
        return $this->decorated->getListeners($eventName);
    }

    public function getListenerPriority(string $eventName, callable $listener): ?int
    {
        return $this->decorated->getListenerPriority($eventName, $listener);
    }

    public function hasListeners(?string $eventName = null): bool
    {
        return $this->decorated->hasListeners($eventName);
    }
}
