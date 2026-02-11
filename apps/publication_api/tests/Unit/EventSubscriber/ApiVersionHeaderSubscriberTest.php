<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\EventSubscriber;

use Mockery;
use PublicationApi\EventSubscriber\ApiVersionHeaderSubscriber;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ApiVersionHeaderSubscriberTest extends UnitTestCase
{
    public function testItAddsVersionHeaderToResponse(): void
    {
        $responseheaderBag = new ResponseHeaderBag();
        $kernel = Mockery::mock(HttpKernelInterface::class);
        $request = Mockery::mock(Request::class);
        $response = Mockery::mock(Response::class);
        $response->headers = $responseheaderBag;

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $apiVersion = '1.2.3';
        $subscriber = new ApiVersionHeaderSubscriber($apiVersion);

        $subscriber->addVersionHeader($event);

        $this->assertSame($apiVersion, $response->headers->get(ApiVersionHeaderSubscriber::HEADER_NAME));
        $this->assertSame($apiVersion, $responseheaderBag->get(ApiVersionHeaderSubscriber::HEADER_NAME));
    }
}
