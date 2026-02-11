<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\EventSubscriber;

use Mockery;
use Mockery\MockInterface;
use Shared\EventSubscriber\SecurityHeaderSubscriber;
use Shared\Service\EnvironmentService;
use Shared\Tests\Unit\UnitTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use function array_map;
use function explode;
use function trim;

class SecurityHeaderSubscriberTest extends UnitTestCase
{
    use MatchesSnapshots;

    private EnvironmentService&MockInterface $environmentService;

    protected function setUp(): void
    {
        $this->environmentService = Mockery::mock(EnvironmentService::class);
        $this->environmentService->shouldReceive('isDev')->andReturn(false);
    }

    public function testOnKernelRequestSetsNonce(): void
    {
        $subscriber = new SecurityHeaderSubscriber($this->environmentService);

        $request = new Request();

        $requestEvent = Mockery::mock(RequestEvent::class);
        $requestEvent->expects('getRequest')->andReturn($request);

        $subscriber->onKernelRequest($requestEvent);

        $this->assertNotNull($request->attributes->get('csp_nonce'));
    }

    public function testOnKernelResponseSetsCspHeadersForFrontend(): void
    {
        $subscriber = new SecurityHeaderSubscriber($this->environmentService);

        $request = new Request(attributes: ['csp_nonce' => 'foo']);
        $response = new Response();

        $responseEvent = new ResponseEvent(
            kernel: Mockery::mock(HttpKernelInterface::class),
            request: $request,
            requestType: 1,
            response: $response,
        );

        $subscriber->onKernelResponse($responseEvent);

        $cspHeaders = (string) $response->headers->get('Content-Security-Policy');
        $cspHeaders = explode(';', $cspHeaders);
        $cspHeaders = array_map(trim(...), $cspHeaders);

        $this->assertMatchesYamlSnapshot($cspHeaders);
    }

    public function testOnKernelRequestContainsDevCsp(): void
    {
        $environmentService = Mockery::mock(EnvironmentService::class);
        $environmentService->shouldReceive('isDev')->andReturn(true);
        $subscriber = new SecurityHeaderSubscriber($environmentService);

        $request = Request::create('foobar');
        $request->attributes->set('csp_nonce', $nonce = 'foo');
        $response = new Response();

        $responseEvent = new ResponseEvent(
            kernel: Mockery::mock(HttpKernelInterface::class),
            request: $request,
            requestType: 1,
            response: $response,
        );

        $subscriber->onKernelResponse($responseEvent);

        $cspHeaders = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('http://localhost:8001', $cspHeaders);

        $cspHeaders = explode(';', $cspHeaders);
        $cspHeaders = array_map(trim(...), $cspHeaders);

        $this->assertMatchesYamlSnapshot($cspHeaders);
    }
}
