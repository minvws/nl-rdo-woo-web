<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\SecurityHeaderSubscriber;
use App\Service\Security\ApplicationMode\ApplicationMode;
use Mockery\Adapter\Phpunit\MockeryTestCase;
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

    public function testOnKernelRequestSetsNonce(): void
    {
        $subscriber = new SecurityHeaderSubscriber(ApplicationMode::PUBLIC);

        $request = new Request();

        $requestEvent = \Mockery::mock(RequestEvent::class);
        $requestEvent->expects('getRequest')->andReturn($request);

        $subscriber->onKernelRequest($requestEvent);

        $this->assertNotNull($request->attributes->get('csp_nonce'));
    }

    #[DataProvider('cspHeadersData')]
    public function testOnKernelResponseSetsCspHeadersForFrontend(ApplicationMode $mode): void
    {
        $subscriber = new SecurityHeaderSubscriber($mode);

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

    #[DataProvider('docUrlsData')]
    public function testOnKernelRequestNonceIsNotSetForStyleForApiDocEndpoints(string $uri): void
    {
        $subscriber = new SecurityHeaderSubscriber(ApplicationMode::API);

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
        $this->assertStringContainsString('unsafe-inline', $styleSrc);
    }

    /**
     * @return array<string,array{uri:string}>
     */
    public static function docUrlsData(): array
    {
        return [
            'starts_with' => ['uri' => '/balie/api/docs/foobar'],
            'contains' => ['uri' => '/acme/api/docs/foobar'],
        ];
    }

    #[DataProvider('docRoutesData')]
    public function testOnKernelRequestNonceIsNotSetForStyleForApiDocEndpointsUsingRoute(string $routeName): void
    {
        $subscriber = new SecurityHeaderSubscriber(ApplicationMode::API);

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
        $this->assertStringContainsString('unsafe-inline', $styleSrc);
    }

    /**
     * @return array<string,array{routeName:string}>
     */
    public static function docRoutesData(): array
    {
        return [
            'api_doc' => ['routeName' => 'api_doc'],
            'starts_with' => ['routeName' => 'api_doc_foobar'],
            'ends_with' => ['routeName' => 'foobar_api_doc'],
        ];
    }

    public function testOnKernelRequestNonceIsSetForStyleForNonApiDocEndpoints(): void
    {
        $subscriber = new SecurityHeaderSubscriber(ApplicationMode::API);

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
        $this->assertStringNotContainsString('unsafe-inline', $styleSrc);
    }
}
