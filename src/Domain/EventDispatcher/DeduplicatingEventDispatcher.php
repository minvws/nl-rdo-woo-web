<?php

declare(strict_types=1);

namespace Shared\Domain\EventDispatcher;

use Shared\Domain\Event\DeduplicatableEvent;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ResetInterface;

use function array_key_exists;

/**
 * NOTE:
 * See the issue at https://github.com/minvws/nl-rdo-woo-web-private/issues/7561. The necesnecessity for this
 * decorator needs to be re-evaluated in the future after the events that implements DeduplicatableEvent have handlers
 * that prevent unnecessary work.
 */
#[AsDecorator('event_dispatcher')]
final class DeduplicatingEventDispatcher implements EventDispatcherInterface, ResetInterface
{
    /** @var array<string,true> */
    private array $seen = [];

    public function __construct(private readonly EventDispatcherInterface $inner)
    {
    }

    public function dispatch(object $event, ?string $eventName = null): object
    {
        if ($event instanceof DeduplicatableEvent) {
            $key = $event->deduplicationKey();

            if (array_key_exists($key, $this->seen)) {
                return $event;
            }

            $this->seen[$key] = true;
        }

        return $this->inner->dispatch($event, $eventName);
    }

    public function reset(): void
    {
        $this->seen = [];
    }

    /**
     * @param callable|array{0:string,1:string} $listener
     */
    public function addListener(string $eventName, callable|array $listener, int $priority = 0): void
    {
        /*
         * @phpstan-ignore argument.type (The type is widened to include arrays because of
         * \Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher)
         */
        $this->inner->addListener($eventName, $listener, $priority);
    }

    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
        $this->inner->addSubscriber($subscriber);
    }

    public function removeListener(string $eventName, callable $listener): void
    {
        $this->inner->removeListener($eventName, $listener);
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber): void
    {
        $this->inner->removeSubscriber($subscriber);
    }

    public function getListeners(?string $eventName = null): array
    {
        return $this->inner->getListeners($eventName);
    }

    public function getListenerPriority(string $eventName, callable $listener): ?int
    {
        return $this->inner->getListenerPriority($eventName, $listener);
    }

    public function hasListeners(?string $eventName = null): bool
    {
        return $this->inner->hasListeners($eventName);
    }
}
