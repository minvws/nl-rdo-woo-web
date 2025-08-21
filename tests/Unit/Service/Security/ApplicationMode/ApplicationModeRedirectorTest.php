<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Security\ApplicationMode;

use App\Service\Security\ApplicationMode\ApplicationMode;
use App\Service\Security\ApplicationMode\ApplicationModeRedirector;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ApplicationModeRedirectorTest extends MockeryTestCase
{
    #[DataProvider('applicationModeProvider')]
    public function testRedirectForNonDevFirewall(
        string $path,
        ApplicationMode $applicationMode,
        ?string $expectedRedirectPath,
    ): void {
        $redirector = new ApplicationModeRedirector($applicationMode);

        $event = \Mockery::mock(RequestEvent::class);
        $event->shouldReceive('getRequest')->andReturn(
            new Request(
                attributes: ['_firewall_context' => 'some.other.firewall.context'],
                server: ['REQUEST_URI' => $path],
            )
        );

        if ($expectedRedirectPath === null) {
            $event->shouldNotHaveReceived('setResponse');
        } else {
            $event->expects('setResponse')->with(\Mockery::on(
                static function (RedirectResponse $response) use ($expectedRedirectPath): bool {
                    return $response->getTargetUrl() === $expectedRedirectPath;
                }
            ));
        }

        $redirector->onKernelRequest($event);
    }

    /**
     * @return array<string,array{
     *     path: string,
     *     applicationMode: ApplicationMode,
     *     expectedRedirectPath: ?string,
     * }>
     */
    public static function applicationModeProvider(): array
    {
        return [
            'all-should-not-redirect-for-admin-path' => [
                'path' => ApplicationModeRedirector::ADMIN_PATH . '/foo',
                'applicationMode' => ApplicationMode::ALL,
                'expectedRedirectPath' => null,
            ],
            'all-should-not-redirect-for-public-path' => [
                'path' => ApplicationModeRedirector::PUBLIC_PATH . '/foo',
                'applicationMode' => ApplicationMode::ALL,
                'expectedRedirectPath' => null,
            ],
            'all-should-not-redirect-for-api-path' => [
                'path' => ApplicationModeRedirector::API_PATH . '/foo',
                'applicationMode' => ApplicationMode::ALL,
                'expectedRedirectPath' => null,
            ],
            'api-should-redirect-for-admin-path' => [
                'path' => ApplicationModeRedirector::ADMIN_PATH . '/foo',
                'applicationMode' => ApplicationMode::API,
                'expectedRedirectPath' => ApplicationModeRedirector::API_PATH,
            ],
            'api-should-redirect-for-public-path' => [
                'path' => ApplicationModeRedirector::PUBLIC_PATH . '/foo',
                'applicationMode' => ApplicationMode::API,
                'expectedRedirectPath' => ApplicationModeRedirector::API_PATH,
            ],
            'api-should-not-redirect-for-api-path' => [
                'path' => ApplicationModeRedirector::API_PATH . '/foo',
                'applicationMode' => ApplicationMode::API,
                'expectedRedirectPath' => null,
            ],
            'admin-should-redirect-for-api-path' => [
                'path' => ApplicationModeRedirector::API_PATH . '/foo',
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedRedirectPath' => ApplicationModeRedirector::ADMIN_PATH,
            ],
            'admin-should-redirect-for-public-path' => [
                'path' => ApplicationModeRedirector::PUBLIC_PATH . '/foo',
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedRedirectPath' => ApplicationModeRedirector::ADMIN_PATH,
            ],
            'admin-should-not-redirect-for-admin-path' => [
                'path' => ApplicationModeRedirector::ADMIN_PATH . '/foo',
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedRedirectPath' => null,
            ],
            'public-should-redirect-for-api-path' => [
                'path' => ApplicationModeRedirector::API_PATH . '/foo',
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedRedirectPath' => ApplicationModeRedirector::PUBLIC_PATH,
            ],
            'public-should-redirect-for-admin-path' => [
                'path' => ApplicationModeRedirector::ADMIN_PATH . '/foo',
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedRedirectPath' => ApplicationModeRedirector::PUBLIC_PATH,
            ],
            'public-should-not-redirect-for-public-path' => [
                'path' => ApplicationModeRedirector::PUBLIC_PATH . '/foo',
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedRedirectPath' => null,
            ],
        ];
    }

    public function testApplicationModeRedirectForNonDevFirewall(): void
    {
        $redirector = new ApplicationModeRedirector(ApplicationMode::ADMIN);

        $event = \Mockery::mock(RequestEvent::class);
        $event->shouldReceive('getRequest')->andReturn(
            new Request(attributes: ['_firewall_context' => 'security.firewall.map.context.dev'])
        );

        $event->shouldNotHaveReceived('setResponse');

        $redirector->onKernelRequest($event);
    }

    public function testApplicationModeRedirectForHealtCheck(): void
    {
        $redirector = new ApplicationModeRedirector(ApplicationMode::ADMIN);

        $event = \Mockery::mock(RequestEvent::class);
        $event->shouldReceive('getRequest')->andReturn(
            new Request(
                attributes: ['_firewall_context' => 'some.other.firewall.context'],
                server: ['REQUEST_URI' => '/health']
            ),
        );

        $event->shouldNotHaveReceived('setResponse');

        $redirector->onKernelRequest($event);
    }
}
