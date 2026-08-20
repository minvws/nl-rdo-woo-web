<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Security\ApplicationId;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\ApplicationId;
use Shared\Service\Security\ApplicationId\ApplicationIdRedirector;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ApplicationIdRedirectorTest extends UnitTestCase
{
    #[DataProvider('applicationModeProvider')]
    public function testRedirectForNonDevFirewall(
        string $path,
        ApplicationId $applicationId,
        int $expectedGetRequestCalls,
    ): void {
        $redirector = new ApplicationIdRedirector($applicationId);

        $event = Mockery::mock(RequestEvent::class);
        $event->expects('getRequest')
            ->times($expectedGetRequestCalls)
            ->andReturn(new Request(
                attributes: ['_firewall_context' => 'some.other.firewall.context'],
                server: ['REQUEST_URI' => $path],
            ));

        $redirector->__invoke($event);
    }

    /**
     * @return array<string,array{
     *     path: string,
     *     applicationId: ApplicationId,
     *     expectedGetRequestCalls: int,
     * }>
     */
    public static function applicationModeProvider(): array
    {
        return [
            'all-should-not-redirect-for-admin-path' => [
                'path' => ApplicationIdRedirector::ADMIN_PATH . '/foo',
                'applicationId' => ApplicationId::SHARED,
                'expectedGetRequestCalls' => 2,
            ],
            'all-should-not-redirect-for-public-path' => [
                'path' => ApplicationIdRedirector::PUBLIC_PATH . '/foo',
                'applicationId' => ApplicationId::SHARED,
                'expectedGetRequestCalls' => 2,
            ],
            'all-should-not-redirect-for-api-path' => [
                'path' => ApplicationIdRedirector::API_PATH . '/foo',
                'applicationId' => ApplicationId::SHARED,
                'expectedGetRequestCalls' => 2,
            ],
            'api-should-not-redirect-for-api-path' => [
                'path' => ApplicationIdRedirector::API_PATH . '/foo',
                'applicationId' => ApplicationId::PUBLICATION_API,
                'expectedGetRequestCalls' => 3,
            ],
            'admin-should-not-redirect-for-admin-path' => [
                'path' => ApplicationIdRedirector::ADMIN_PATH . '/foo',
                'applicationId' => ApplicationId::ADMIN,
                'expectedGetRequestCalls' => 3,
            ],
            'public-should-not-redirect-for-public-path' => [
                'path' => ApplicationIdRedirector::PUBLIC_PATH . '/foo',
                'applicationId' => ApplicationId::PUBLIC,
                'expectedGetRequestCalls' => 3,
            ],
        ];
    }

    #[DataProvider('applicationModeProviderWithRedirect')]
    public function testRedirectForNonDevFirewallWithRedirect(
        string $path,
        ApplicationId $applicationId,
        string $expectedRedirectPath,
    ): void {
        $redirector = new ApplicationIdRedirector($applicationId);

        $event = Mockery::mock(RequestEvent::class);
        $event->expects('getRequest')
            ->times(3)
            ->andReturn(new Request(
                attributes: ['_firewall_context' => 'some.other.firewall.context'],
                server: ['REQUEST_URI' => $path],
            ));

        $event->expects('setResponse')
            ->with(Mockery::on(
                static function (RedirectResponse $response) use ($expectedRedirectPath): bool {
                    return $response->getTargetUrl() === $expectedRedirectPath;
                },
            ));

        $redirector->__invoke($event);
    }

    /**
     * @return array<string,array{
     *     path: string,
     *     applicationId: ApplicationId,
     *     expectedRedirectPath: string,
     * }>
     */
    public static function applicationModeProviderWithRedirect(): array
    {
        return [
            'api-should-redirect-for-admin-path' => [
                'path' => ApplicationIdRedirector::ADMIN_PATH . '/foo',
                'applicationId' => ApplicationId::PUBLICATION_API,
                'expectedRedirectPath' => ApplicationIdRedirector::API_PATH,
            ],
            'api-should-redirect-for-public-path' => [
                'path' => ApplicationIdRedirector::PUBLIC_PATH . '/foo',
                'applicationId' => ApplicationId::PUBLICATION_API,
                'expectedRedirectPath' => ApplicationIdRedirector::API_PATH,
            ],
            'admin-should-redirect-for-api-path' => [
                'path' => ApplicationIdRedirector::API_PATH . '/foo',
                'applicationId' => ApplicationId::ADMIN,
                'expectedRedirectPath' => ApplicationIdRedirector::ADMIN_PATH,
            ],
            'admin-should-redirect-for-public-path' => [
                'path' => ApplicationIdRedirector::PUBLIC_PATH . '/foo',
                'applicationId' => ApplicationId::ADMIN,
                'expectedRedirectPath' => ApplicationIdRedirector::ADMIN_PATH,
            ],
            'public-should-redirect-for-api-path' => [
                'path' => ApplicationIdRedirector::API_PATH . '/foo',
                'applicationId' => ApplicationId::PUBLIC,
                'expectedRedirectPath' => ApplicationIdRedirector::PUBLIC_PATH,
            ],
            'public-should-redirect-for-admin-path' => [
                'path' => ApplicationIdRedirector::ADMIN_PATH . '/foo',
                'applicationId' => ApplicationId::PUBLIC,
                'expectedRedirectPath' => ApplicationIdRedirector::PUBLIC_PATH,
            ],
        ];
    }

    public function testApplicationModeRedirectForNonDevFirewall(): void
    {
        $redirector = new ApplicationIdRedirector(ApplicationId::ADMIN);

        $event = Mockery::mock(RequestEvent::class);
        $event->expects('getRequest')->andReturn(
            new Request(attributes: ['_firewall_context' => 'security.firewall.map.context.dev']),
        );

        $event->shouldNotHaveReceived('setResponse');

        $redirector->__invoke($event);
    }

    public function testApplicationModeRedirectForHealtCheck(): void
    {
        $redirector = new ApplicationIdRedirector(ApplicationId::ADMIN);

        $event = Mockery::mock(RequestEvent::class);
        $event->expects('getRequest')
            ->times(2)
            ->andReturn(
                new Request(
                    attributes: ['_firewall_context' => 'some.other.firewall.context'],
                    server: ['REQUEST_URI' => '/health'],
                ),
            );

        $event->shouldNotHaveReceived('setResponse');

        $redirector->__invoke($event);
    }

    #[DataProvider('getProfilerPaths')]
    public function testApplicationModeRedirectForSymfonyProfilerRoutes(string $path): void
    {
        $redirector = new ApplicationIdRedirector(ApplicationId::ADMIN);

        $event = Mockery::mock(RequestEvent::class);
        $event->expects('getRequest')
            ->times(2)
            ->andReturn(
                new Request(
                    attributes: ['_firewall_context' => 'some.other.firewall.context'],
                    server: ['REQUEST_URI' => $path],
                ),
            );

        $event->shouldNotHaveReceived('setResponse');

        $redirector->__invoke($event);
    }

    /**
     * @return array<string,array{path:string}>
     */
    public static function getProfilerPaths(): array
    {
        return [
            'profiler path' => [
                'path' => '/_profiler',
            ],
            'profiler path with extra parameters' => [
                'path' => '/_profiler/foobar/acme',
            ],
            'wdt_path' => [
                'path' => '/_wdt',
            ],
            'fragment_path' => [
                'path' => '/_fragment',
            ],
        ];
    }
}
