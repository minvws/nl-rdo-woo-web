<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\EventSubscriber;

use Mockery\MockInterface;
use Shared\Api\Publication\V1\PublicationV1Api;
use Shared\EventSubscriber\FeaturePublicationV1ApiSubscriber;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class FeaturePublicationV1ApiSubscriberTest extends UnitTestCase
{
    private Request&MockInterface $request;
    private RequestEvent&MockInterface $event;

    protected function setUp(): void
    {
        $this->request = \Mockery::mock(Request::class);
        $this->event = \Mockery::mock(RequestEvent::class);
        $this->event->shouldReceive('getRequest')->andReturn($this->request);
    }

    public function testWhenFeatureIsDisabledItThrowsNotFoundException(): void
    {
        $requestUri = sprintf('%s/some-endpoint', PublicationV1Api::API_PREFIX);

        $this->request->shouldReceive('getPathInfo')->once()->andReturn($requestUri);

        $subscriber = new FeaturePublicationV1ApiSubscriber(hasFeaturePublicationV1: false);

        $this->expectException(NotFoundHttpException::class);

        $subscriber->onRequest($this->event);
    }

    public function testWhenFeatureIsEnabledItsANoOp(): void
    {
        $this->event->shouldNotReceive('getRequest');

        $subscriber = new FeaturePublicationV1ApiSubscriber(hasFeaturePublicationV1: true);

        $subscriber->onRequest($this->event);
    }
}
