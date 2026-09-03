<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\EventDispatcher;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Event\DeduplicatableEvent;
use Shared\Domain\EventDispatcher\DeduplicatingEventDispatcher;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DeduplicatingEventDispatcherTest extends UnitTestCase
{
    private EventDispatcherInterface&MockInterface $inner;
    private DeduplicatingEventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->inner = Mockery::mock(EventDispatcherInterface::class);

        $this->dispatcher = new DeduplicatingEventDispatcher($this->inner);
    }

    public function testEventWithoutDeduplicationIsAlwaysForwardedToTheInnerDispatcher(): void
    {
        $event = new stdClass();

        $this->inner->expects('dispatch')->with($event, null)->twice()->andReturn($event);

        self::assertSame($event, $this->dispatcher->dispatch($event));
        self::assertSame($event, $this->dispatcher->dispatch($event));
    }

    public function testEventNameIsForwardedToTheInnerDispatcher(): void
    {
        $event = new stdClass();

        $this->inner->expects('dispatch')->with($event, 'foo.bar')->andReturn($event);

        self::assertSame($event, $this->dispatcher->dispatch($event, 'foo.bar'));
    }

    public function testReturnValueOfTheInnerDispatcherIsReturned(): void
    {
        $event = new stdClass();
        $returnedEvent = new stdClass();

        $this->inner->expects('dispatch')->with($event, null)->andReturn($returnedEvent);

        self::assertSame($returnedEvent, $this->dispatcher->dispatch($event));
    }

    public function testDeduplicatableEventIsOnlyDispatchedOnce(): void
    {
        $eventA = $this->createDeduplicatableEvent('key-1');
        $eventB = $this->createDeduplicatableEvent('key-1');

        $this->inner->expects('dispatch')->with($eventA, null)->once()->andReturn($eventA);

        self::assertSame($eventA, $this->dispatcher->dispatch($eventA));
        self::assertSame($eventB, $this->dispatcher->dispatch($eventB));
    }

    public function testTheSameEventInstanceIsOnlyDispatchedOnce(): void
    {
        $event = $this->createDeduplicatableEvent('key-1');

        $this->inner->expects('dispatch')->with($event, null)->once()->andReturn($event);

        self::assertSame($event, $this->dispatcher->dispatch($event));
        self::assertSame($event, $this->dispatcher->dispatch($event));
        self::assertSame($event, $this->dispatcher->dispatch($event));
    }

    public function testDeduplicatableEventsWithDifferentKeysAreAllDispatched(): void
    {
        $eventA = $this->createDeduplicatableEvent('key-1');
        $eventB = $this->createDeduplicatableEvent('key-2');

        $this->inner->expects('dispatch')->with($eventA, null)->once()->andReturn($eventA);
        $this->inner->expects('dispatch')->with($eventB, null)->once()->andReturn($eventB);

        self::assertSame($eventA, $this->dispatcher->dispatch($eventA));
        self::assertSame($eventB, $this->dispatcher->dispatch($eventB));
    }

    public function testDeduplicationIgnoresTheEventName(): void
    {
        $eventA = $this->createDeduplicatableEvent('key-1');
        $eventB = $this->createDeduplicatableEvent('key-1');

        $this->inner->expects('dispatch')->with($eventA, 'foo')->once()->andReturn($eventA);
        $this->inner->expects('dispatch')->with($eventB, 'bar')->never();

        self::assertSame($eventA, $this->dispatcher->dispatch($eventA, 'foo'));
        self::assertSame($eventB, $this->dispatcher->dispatch($eventB, 'bar'));
    }

    public function testResetClearsTheDeduplicationState(): void
    {
        $eventA = $this->createDeduplicatableEvent('key-1');
        $eventB = $this->createDeduplicatableEvent('key-1');

        $this->inner->expects('dispatch')->with($eventA, null)->once()->andReturn($eventA);
        $this->inner->expects('dispatch')->with($eventB, null)->once()->andReturn($eventB);

        self::assertSame($eventA, $this->dispatcher->dispatch($eventA));

        $this->dispatcher->reset();

        self::assertSame($eventB, $this->dispatcher->dispatch($eventB));
    }

    public function testAddListenerIsForwardedToTheInnerDispatcher(): void
    {
        $listener = static fn () => null;

        $this->inner->expects('addListener')->with('foo.bar', $listener, 10);

        $this->dispatcher->addListener('foo.bar', $listener, 10);
    }

    public function testAddListenerUsesADefaultPriority(): void
    {
        $listener = static fn () => null;

        $this->inner->expects('addListener')->with('foo.bar', $listener, 0);

        $this->dispatcher->addListener('foo.bar', $listener);
    }

    public function testAddListenerAcceptsAnArrayListener(): void
    {
        $listener = [self::class, 'onFooBar'];

        $this->inner->expects('addListener')->with('foo.bar', $listener, 0);

        $this->dispatcher->addListener('foo.bar', $listener);
    }

    public function testAddSubscriberIsForwardedToTheInnerDispatcher(): void
    {
        $subscriber = Mockery::mock(EventSubscriberInterface::class);

        $this->inner->expects('addSubscriber')->with($subscriber);

        $this->dispatcher->addSubscriber($subscriber);
    }

    public function testRemoveListenerIsForwardedToTheInnerDispatcher(): void
    {
        $listener = static fn () => null;

        $this->inner->expects('removeListener')->with('foo.bar', $listener);

        $this->dispatcher->removeListener('foo.bar', $listener);
    }

    public function testRemoveSubscriberIsForwardedToTheInnerDispatcher(): void
    {
        $subscriber = Mockery::mock(EventSubscriberInterface::class);

        $this->inner->expects('removeSubscriber')->with($subscriber);

        $this->dispatcher->removeSubscriber($subscriber);
    }

    public function testGetListenersIsForwardedToTheInnerDispatcher(): void
    {
        $listeners = [static fn () => null];

        $this->inner->expects('getListeners')->with('foo.bar')->andReturn($listeners);

        self::assertSame($listeners, $this->dispatcher->getListeners('foo.bar'));
    }

    public function testGetListenersWithoutEventNameIsForwardedToTheInnerDispatcher(): void
    {
        $this->inner->expects('getListeners')->with(null)->andReturn([]);

        self::assertSame([], $this->dispatcher->getListeners());
    }

    public function testGetListenerPriorityIsForwardedToTheInnerDispatcher(): void
    {
        $listener = static fn () => null;

        $this->inner->expects('getListenerPriority')->with('foo.bar', $listener)->andReturn(42);

        self::assertSame(42, $this->dispatcher->getListenerPriority('foo.bar', $listener));
    }

    public function testHasListenersIsForwardedToTheInnerDispatcher(): void
    {
        $this->inner->expects('hasListeners')->with('foo.bar')->andReturnTrue();
        $this->inner->expects('hasListeners')->with(null)->andReturnFalse();

        self::assertTrue($this->dispatcher->hasListeners('foo.bar'));
        self::assertFalse($this->dispatcher->hasListeners());
    }

    public static function onFooBar(): void
    {
    }

    private function createDeduplicatableEvent(string $key): DeduplicatableEvent&MockInterface
    {
        $event = Mockery::mock(DeduplicatableEvent::class);
        $event->allows('deduplicationKey')->andReturn($key);

        return $event;
    }
}
