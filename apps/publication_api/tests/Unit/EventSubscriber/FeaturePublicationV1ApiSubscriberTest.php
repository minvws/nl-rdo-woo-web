<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\EventSubscriber;

use Mockery;
use Mockery\MockInterface;
use PublicationApi\EventSubscriber\FeaturePublicationV1ApiSubscriber;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class FeaturePublicationV1ApiSubscriberTest extends UnitTestCase
{
    private RequestEvent&MockInterface $event;

    protected function setUp(): void
    {
        $this->event = Mockery::mock(RequestEvent::class);
    }

    public function testWhenFeatureIsDisabledItThrowsNotFoundException(): void
    {
        $subscriber = new FeaturePublicationV1ApiSubscriber(hasFeaturePublicationV1: false);

        $this->expectException(NotFoundHttpException::class);

        $subscriber->onRequest($this->event);
    }

    public function testWhenFeatureIsEnabledItsANoOp(): void
    {
        $subscriber = new FeaturePublicationV1ApiSubscriber(hasFeaturePublicationV1: true);

        $subscriber->onRequest($this->event);

        // @phpstan-ignore method.alreadyNarrowedType
        $this->asserttrue(true); // If we reach this point, the test passes (no exception was thrown)
    }
}
