<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\SecurityHeaderSubscriber;
use App\Service\EnvironmentService;
use App\Service\Security\ApplicationMode\ApplicationMode;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SecurityHeaderSubscriberTest extends MockeryTestCase
{
    use MatchesSnapshots;

    private EnvironmentService&MockInterface $environmentService;

    protected function setUp(): void
    {
        $this->environmentService = \Mockery::mock(EnvironmentService::class);
        $this->environmentService->shouldReceive('isDev')->andReturn(false);
    }

    public function testOnKernelRequestSetsNonce(): void
    {
        $subscriber = new SecurityHeaderSubscriber(ApplicationMode::PUBLIC, $this->environmentService);

        $request = new Request();

        $requestEvent = \Mockery::mock(RequestEvent::class);
        $requestEvent->expects('getRequest')->andReturn($request);

        $subscriber->onKernelRequest($requestEvent);

        $this->assertNotNull($request->attributes->get('csp_nonce'));
    }

    #[DataProvider('cspHeadersData')]
    public function testOnKernelResponseSetsCspHeadersForFrontend(ApplicationMode $mode): void
    {
        $subscriber = new SecurityHeaderSubscriber($mode, $this->environmentService);

        $request = new Request(attributes: ['csp_nonce' => 'foo']);
        $response = new Response();

        $responseEvent = new ResponseEvent(
            kernel: \Mockery::mock(HttpKernelInterface::class),
            request: $request,
            requestType: 1,
            response: $response,
        );

        $subscriber->onKernelResponse($responseEvent);

        $headers = $response->headers->all();
        unset($headers['date']);

        $this->assertMatchesSnapshot($headers);
    }

    /**
     * @return array<string,array{mode:ApplicationMode}>
     */
    public static function cspHeadersData(): array
    {
        return [
            'public' => [
                'mode' => ApplicationMode::PUBLIC,
            ],
            'admin' => [
                'mode' => ApplicationMode::ADMIN,
            ],
            'all' => [
                'mode' => ApplicationMode::ALL,
            ],
            'api' => [
                'mode' => ApplicationMode::API,
            ],
        ];
    }

    public function testOnKernelRequestNonceIsNotSetForStyleForApiDocEndpoint(): void
    {
        $uri = '/balie/api/docs/foobar';
        $subscriber = new SecurityHeaderSubscriber(ApplicationMode::API, $this->environmentService);

        $request = Request::create($uri);
        $request->attributes->set('csp_nonce', $nonce = 'foo');
        $response = new Response();

        $responseEvent = new ResponseEvent(
            kernel: \Mockery::mock(HttpKernelInterface::class),
            request: $request,
            requestType: 1,
            response: $response,
        );

        $subscriber->onKernelResponse($responseEvent);

        $cspHeaders = (string) $response->headers->get('Content-Security-Policy');
        $cspHeaders = explode('; ', $cspHeaders);

        $styleSrc = '';
        foreach ($cspHeaders as $header) {
            if (str_starts_with($header, 'style-src')) {
                $styleSrc = $header;
                break;
            }
        }

        $this->assertStringNotContainsString($nonce, $styleSrc);
        $this->assertStringNotContainsString('http://localhost:8001', $styleSrc);
        $this->assertStringContainsString('unsafe-inline', $styleSrc);
    }

    public function testOnKernelRequestNonceIsNotSetForStyleForApiDocEndpointUsingRoute(): void
    {
        $routeName = 'api_doc';
        $subscriber = new SecurityHeaderSubscriber(ApplicationMode::API, $this->environmentService);

        $request = Request::create('my_endpoint');
        $request->attributes->set('csp_nonce', $nonce = 'foo');
        $request->attributes->set('_route', $routeName);
        $response = new Response();

        $responseEvent = new ResponseEvent(
            kernel: \Mockery::mock(HttpKernelInterface::class),
            request: $request,
            requestType: 1,
            response: $response,
        );

        $subscriber->onKernelResponse($responseEvent);

        $cspHeaders = (string) $response->headers->get('Content-Security-Policy');
        $cspHeaders = explode('; ', $cspHeaders);

        $styleSrc = '';
        foreach ($cspHeaders as $header) {
            if (str_starts_with($header, 'style-src')) {
                $styleSrc = $header;
                break;
            }
        }

        $this->assertStringNotContainsString($nonce, $styleSrc);
        $this->assertStringNotContainsString('http://localhost:8001', $styleSrc);
        $this->assertStringContainsString('unsafe-inline', $styleSrc);
    }

    public function testOnKernelRequestNonceIsSetForStyleForNonApiDocEndpoint(): void
    {
        $subscriber = new SecurityHeaderSubscriber(ApplicationMode::API, $this->environmentService);

        $request = Request::create('foobar');
        $request->attributes->set('csp_nonce', $nonce = 'foo');
        $response = new Response();

        $responseEvent = new ResponseEvent(
            kernel: \Mockery::mock(HttpKernelInterface::class),
            request: $request,
            requestType: 1,
            response: $response,
        );

        $subscriber->onKernelResponse($responseEvent);

        $cspHeaders = (string) $response->headers->get('Content-Security-Policy');
        $cspHeaders = explode('; ', $cspHeaders);

        $styleSrc = '';
        foreach ($cspHeaders as $header) {
            if (str_starts_with($header, 'style-src')) {
                $styleSrc = $header;
                break;
            }
        }

        $this->assertStringContainsString($nonce, $styleSrc);
        $this->assertStringNotContainsString('http://localhost:8001', $styleSrc);
        $this->assertStringNotContainsString('unsafe-inline', $styleSrc);
    }

    public function testOnKernelRequestContainsDevCsp(): void
    {
        $environmentService = \Mockery::mock(EnvironmentService::class);
        $environmentService->shouldReceive('isDev')->andReturn(true);
        $subscriber = new SecurityHeaderSubscriber(ApplicationMode::ALL, $environmentService);

        $request = Request::create('foobar');
        $request->attributes->set('csp_nonce', $nonce = 'foo');
        $response = new Response();

        $responseEvent = new ResponseEvent(
            kernel: \Mockery::mock(HttpKernelInterface::class),
            request: $request,
            requestType: 1,
            response: $response,
        );

        $subscriber->onKernelResponse($responseEvent);

        $cspHeaders = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('http://localhost:8001', $cspHeaders);
    }

    public function testOnKernelRequestAllowFontsFromScalarForScalarDocsEndpoints(): void
    {
        $subscriber = new SecurityHeaderSubscriber(ApplicationMode::API, $this->environmentService);

        $request = Request::create('/api/docs');
        $response = new Response();

        $responseEvent = new ResponseEvent(
            kernel: \Mockery::mock(HttpKernelInterface::class),
            request: $request,
            requestType: 1,
            response: $response,
        );

        $subscriber->onKernelResponse($responseEvent);

        $cspHeaders = (string) $response->headers->get('Content-Security-Policy');
        $cspHeaders = explode('; ', $cspHeaders);

        $fontSrc = '';
        foreach ($cspHeaders as $header) {
            if (str_starts_with($header, 'font-src')) {
                $fontSrc = $header;
                break;
            }
        }

        $this->assertStringContainsString('https://fonts.scalar.com', $fontSrc);
    }

    public function testOnKernelRequestAllowFontsFromScalarForScalarDocsUsingRoute(): void
    {
        $routeName = 'app_api_docs';
        $subscriber = new SecurityHeaderSubscriber(ApplicationMode::API, $this->environmentService);

        $request = Request::create('my_endpoint');
        $request->attributes->set('_route', $routeName);
        $response = new Response();

        $responseEvent = new ResponseEvent(
            kernel: \Mockery::mock(HttpKernelInterface::class),
            request: $request,
            requestType: 1,
            response: $response,
        );

        $subscriber->onKernelResponse($responseEvent);

        $cspHeaders = (string) $response->headers->get('Content-Security-Policy');
        $cspHeaders = explode('; ', $cspHeaders);

        $fontSrc = '';
        foreach ($cspHeaders as $header) {
            if (str_starts_with($header, 'font-src')) {
                $fontSrc = $header;
                break;
            }
        }

        $this->assertStringContainsString('https://fonts.scalar.com', $fontSrc);
    }

    public function testOnKernelRequestDoesNotAllowFontsFromScalarForNonApiOrAllApplicationMode(): void
    {
        $subscriber = new SecurityHeaderSubscriber(ApplicationMode::ADMIN, $this->environmentService);

        $request = Request::create('/api/docs');
        $response = new Response();

        $responseEvent = new ResponseEvent(
            kernel: \Mockery::mock(HttpKernelInterface::class),
            request: $request,
            requestType: 1,
            response: $response,
        );

        $subscriber->onKernelResponse($responseEvent);

        $cspHeaders = (string) $response->headers->get('Content-Security-Policy');
        $cspHeaders = explode('; ', $cspHeaders);

        $fontSrc = '';
        foreach ($cspHeaders as $header) {
            if (str_starts_with($header, 'font-src')) {
                $fontSrc = $header;
                break;
            }
        }

        $this->assertStringNotContainsString('https://fonts.scalar.com', $fontSrc);
    }
}
