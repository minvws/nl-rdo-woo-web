<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\AuthMatrixEnsureSubscriber;
use App\Service\Security\Authorization\AuthorizationEntryRequestStore;
use App\Service\Security\Authorization\Entry;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

class AuthMatrixEnsureSubscriberTest extends MockeryTestCase
{
    private AuthorizationEntryRequestStore&MockInterface $store;
    private AuthMatrixEnsureSubscriber $subscriber;
    private Request&MockInterface $request;

    protected function setUp(): void
    {
        $this->store = \Mockery::mock(AuthorizationEntryRequestStore::class);
        $this->request = \Mockery::mock(Request::class);
        $this->subscriber = new AuthMatrixEnsureSubscriber($this->store);
    }

    public function testOnlyMainRequestsAreChecked(): void
    {
        $event = new ControllerArgumentsEvent(
            \Mockery::mock(Kernel::class),
            fn () => true,
            [],
            $this->request,
            HttpKernelInterface::SUB_REQUEST,
        );

        $this->expectNotToPerformAssertions();

        $this->subscriber->onKernelControllerArguments($event);
    }

    public function testNonBalieUrlsAreNotChecked(): void
    {
        $this->request->expects('getRequestUri')->andReturn('/contact');

        $event = new ControllerArgumentsEvent(
            \Mockery::mock(Kernel::class),
            fn () => true,
            [],
            $this->request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $this->subscriber->onKernelControllerArguments($event);
    }

    public function testWhitelistedAdminUrlIsNotChecked(): void
    {
        $this->request->expects('getRequestUri')->andReturn('/balie/admin');

        $event = new ControllerArgumentsEvent(
            \Mockery::mock(Kernel::class),
            fn () => true,
            [],
            $this->request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $this->subscriber->onKernelControllerArguments($event);
    }

    public function testBalieUrlWithoutStoredEntriesTriggersAccessDenied(): void
    {
        $this->request->expects('getRequestUri')->andReturn('/balie/dossiers');

        $this->store->expects('getEntries')->andReturn([]);

        $event = new ControllerArgumentsEvent(
            \Mockery::mock(Kernel::class),
            fn () => true,
            [],
            $this->request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $this->expectException(AccessDeniedHttpException::class);

        $this->subscriber->onKernelControllerArguments($event);
    }

    public function testBalieUrlsWithStoredEntriesIsAccepted(): void
    {
        $this->request->expects('getRequestUri')->andReturn('/balie/dossiers');

        $this->store->expects('getEntries')->andReturn([\Mockery::mock(Entry::class)]);

        $event = new ControllerArgumentsEvent(
            \Mockery::mock(Kernel::class),
            fn () => true,
            [],
            $this->request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $this->subscriber->onKernelControllerArguments($event);
    }
}
